<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tag;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\TagInterface;

class DelayedTag implements TagInterface
{
    private const string VERSION_SEPARATOR = ':v';
    private const string INDEX_SEPARATOR = '_';
    private const string UPDATED_AT_SUFFIX = ':updated_at';

    private ?int $version;
    private ?int $pendingVersion = null;
    private CacheHandlerInterface $cacheHandler;
    private readonly string $prefix;

    public function __construct(
        private readonly string $tag,
        private readonly string $instance = '',
        private readonly float $delaySeconds = 60.0,
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
            $tagName = $this->getTagName();
            $stored = $this->cacheHandler->get($tagName);
            if ($stored !== null) {
                $this->version = (int) $stored;
                $updatedAt = $this->cacheHandler->get($tagName . self::UPDATED_AT_SUFFIX);
                if ($updatedAt !== null) {
                    $elapsed = microtime(true) - (float) $updatedAt;
                    if ($elapsed < $this->delaySeconds) {
                        $this->pendingVersion = $this->version;
                        $previousVersion = $this->version - 1;
                        if ($previousVersion > 0) {
                            $this->version = $previousVersion;
                        }
                    }
                }
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
        $currentVersion = $this->resolveActualVersion();
        $this->version = $currentVersion + 1;
        $this->pendingVersion = $this->version;
        $this->storeVersion();
        $this->cacheHandler->set(
            $this->getTagName() . self::UPDATED_AT_SUFFIX,
            (string) microtime(true),
        );
        $this->version = $currentVersion;
    }

    public function resetTagVersion(): void
    {
        $this->version = self::freshVersion();
        $this->pendingVersion = null;
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

    private function resolveActualVersion(): int
    {
        if ($this->pendingVersion !== null) {
            return $this->pendingVersion;
        }
        $stored = $this->cacheHandler->get($this->getTagName());
        if ($stored !== null) {
            return (int) $stored;
        }
        return $this->getTagVersion();
    }

    /**
     * A fresh, monotonically increasing reset version: microseconds since the
     * epoch as an exact int64 (see StandardTag::freshVersion).
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
