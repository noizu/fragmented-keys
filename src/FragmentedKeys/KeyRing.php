<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\DelayedTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;

class KeyRing implements KeyRingInterface
{
    /** @var array<string, mixed> */
    private array $globalOptions;

    /** @var array<string, array<string, mixed>> */
    private array $globalTagOptions;

    private ?string $defaultCacheHandler;

    /** @var array<string, CacheHandlerInterface> */
    private array $cacheHandlers;

    private ?string $defaultPrefix;

    /** @var array<string, array{params: list<string|array<string, mixed>>, globals: array<string, mixed>}> */
    private array $keyDefinitions = [];

    /**
     * @param array<string, mixed> $globalOptions
     * @param array<string, array<string, mixed>> $globalTagOptions
     * @param array<string, CacheHandlerInterface> $cacheHandlers
     */
    public function __construct(
        array $globalOptions = [],
        array $globalTagOptions = [],
        ?string $defaultCacheHandler = null,
        array $cacheHandlers = [],
        ?string $defaultPrefix = null,
    ) {
        $this->globalOptions = $globalOptions;
        $this->globalTagOptions = $globalTagOptions;
        $this->defaultCacheHandler = $defaultCacheHandler;
        $this->cacheHandlers = $cacheHandlers;
        $this->defaultPrefix = $defaultPrefix;
    }

    public function defineKey(string $key, array $params, array $globals = []): void
    {
        $this->keyDefinitions[$key] = [
            'params' => $params,
            'globals' => $globals,
        ];
    }

    public function getKeyObj(string $key, array $tagValues): KeyInterface
    {
        if (!isset($this->keyDefinitions[$key])) {
            throw new \InvalidArgumentException("Key '{$key}' has not been defined.");
        }

        $definition = $this->keyDefinitions[$key];
        $params = $definition['params'];
        $keyGlobals = $definition['globals'];

        if (count($tagValues) !== count($params)) {
            throw new \InvalidArgumentException(
                sprintf('Expected %d tag values for key "%s", got %d.', count($params), $key, count($tagValues)),
            );
        }

        $tags = [];
        foreach ($params as $i => $param) {
            $tagName = is_string($param) ? $param : ($param['tag'] ?? '');
            $paramOptions = is_array($param) ? $param : [];
            $merged = array_merge($this->globalOptions, $this->globalTagOptions[$tagName] ?? [], $keyGlobals, $paramOptions);
            $tags[] = $this->tag($tagName, $tagValues[$i], $merged);
        }

        return new StandardKey($key, $tags);
    }

    public function tag(string $tag, string $instance, array $options = []): TagInterface
    {
        $merged = array_merge($this->globalOptions, $this->globalTagOptions[$tag] ?? [], $options);

        $type = $merged['type'] ?? 'standard';
        $version = isset($merged['version']) ? (int) $merged['version'] : null;
        $prefix = $merged['prefix'] ?? $this->defaultPrefix;
        $delaySeconds = isset($merged['delay_seconds']) ? (float) $merged['delay_seconds'] : 60.0;

        $handler = null;
        $handlerName = $merged['cache_handler'] ?? $this->defaultCacheHandler;
        if ($handlerName !== null && isset($this->cacheHandlers[$handlerName])) {
            $handler = $this->cacheHandlers[$handlerName];
        }

        return match ($type) {
            'constant' => new ConstantTag($tag, $instance, $version ?? 1, $handler, $prefix),
            'delayed' => new DelayedTag($tag, $instance, $delaySeconds, $version, $handler, $prefix),
            default => new StandardTag($tag, $instance, $version, $handler, $prefix),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setTagOptions(string $tag, array $options): void
    {
        $this->globalTagOptions[$tag] = $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTagOptions(string $tag): array
    {
        return $this->globalTagOptions[$tag] ?? [];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setGlobalOptions(array $options): void
    {
        $this->globalOptions = $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobalOptions(): array
    {
        return $this->globalOptions;
    }

    /**
     * @param list<mixed> $args
     */
    public function __call(string $name, array $args): mixed
    {
        if (str_starts_with($name, 'get') && str_ends_with($name, 'KeyObj')) {
            $keyName = substr($name, 3, -6);
            $keyName = str_replace('_', '', $keyName);

            foreach (array_keys($this->keyDefinitions) as $defined) {
                if (strcasecmp(str_replace('_', '', $defined), $keyName) === 0) {
                    return $this->getKeyObj($defined, $args);
                }
            }

            throw new \BadMethodCallException("No key definition matches '{$keyName}'.");
        }

        throw new \BadMethodCallException("Method '{$name}' does not exist.");
    }
}
