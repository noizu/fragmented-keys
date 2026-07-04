<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tag;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\TagInterface;

class ConstantTag implements TagInterface
{
    private const string VERSION_SEPARATOR = ':v';
    private const string INDEX_SEPARATOR = '_';

    private readonly CacheHandlerInterface $cacheHandler;
    private readonly string $prefix;

    public function __construct(
        private readonly string $tag,
        private readonly string $instance = '',
        private readonly int $version = 1,
        ?CacheHandlerInterface $handler = null,
        ?string $prefix = null,
    ) {
        $this->cacheHandler = $handler ?? Configuration::getDefaultCacheHandler();
        $this->prefix = $prefix ?? Configuration::getGlobalPrefix();
    }

    public function getTagName(): string
    {
        return $this->tag . self::INDEX_SEPARATOR . $this->instance . $this->prefix;
    }

    public function getTagVersion(): int
    {
        return $this->version;
    }

    public function getFullTag(): string
    {
        return $this->getTagName() . self::VERSION_SEPARATOR . $this->version;
    }

    public function increment(): void
    {
        // no-op
    }

    public function resetTagVersion(): void
    {
        // no-op
    }

    public function setTagVersion(int $version, bool $persist = false): void
    {
        // no-op
    }

    public function getCacheHandler(): CacheHandlerInterface
    {
        return $this->cacheHandler;
    }

    public function setCacheHandler(CacheHandlerInterface $handler): void
    {
        // no-op: constant tags are fully immutable
    }

    public function delegateCacheQuery(string $group): bool
    {
        return false;
    }
}
