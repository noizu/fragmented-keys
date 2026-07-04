<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tag;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\TagInterface;

class StandardTag implements TagInterface
{
    private const string VERSION_SEPARATOR = ':v';
    private const string INDEX_SEPARATOR = '_';

    private ?int $version;
    private CacheHandlerInterface $cacheHandler;
    private readonly string $prefix;

    public function __construct(
        private readonly string $tag,
        private readonly string $instance = '',
        ?int $version = null,
        ?CacheHandlerInterface $handler = null,
        ?string $prefix = null,
    ) {
        $this->version = $version;
        $this->cacheHandler = $handler ?? Configuration::getDefaultCacheHandler();
        $this->prefix = $prefix ?? Configuration::getGlobalPrefix();
    }

    public function getTagName(): string
    {
        return $this->tag . self::INDEX_SEPARATOR . $this->instance . $this->prefix;
    }

    public function getTagVersion(): int
    {
        if ($this->version === null) {
            $stored = $this->cacheHandler->get($this->getTagName());
            if ($stored !== null) {
                $this->version = (int) $stored;
            } else {
                $this->resetTagVersion();
            }
        }
        assert($this->version !== null);
        return $this->version;
    }

    public function getFullTag(): string
    {
        return $this->getTagName() . self::VERSION_SEPARATOR . $this->getTagVersion();
    }

    public function increment(): void
    {
        $this->version = $this->getTagVersion() + 1;
        $this->storeVersion();
    }

    public function resetTagVersion(): void
    {
        $this->version = self::freshVersion();
        $this->storeVersion();
    }

    public function setTagVersion(int $version, bool $persist = false): void
    {
        $this->version = $version;
        if ($persist) {
            $this->storeVersion();
        }
    }

    public function getCacheHandler(): CacheHandlerInterface
    {
        return $this->cacheHandler;
    }

    public function setCacheHandler(CacheHandlerInterface $handler): void
    {
        $this->cacheHandler = $handler;
    }

    public function delegateCacheQuery(string $group): bool
    {
        return $this->cacheHandler->groupName() === $group;
    }

    /**
     * A fresh, monotonically increasing reset version: microseconds since the
     * epoch as an exact int64. Integers round-trip through cache storage without
     * the mantissa/precision loss a float version would suffer at this scale.
     */
    private static function freshVersion(): int
    {
        return (int) round(microtime(true) * 1_000_000);
    }

    private function storeVersion(): void
    {
        assert($this->version !== null);
        $this->cacheHandler->set($this->getTagName(), (string) $this->version);
    }
}
