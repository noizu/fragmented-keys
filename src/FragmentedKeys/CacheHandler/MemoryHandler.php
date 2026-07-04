<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\CacheHandler;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;

class MemoryHandler implements CacheHandlerInterface
{
    /** @var array<string, string> */
    private array $cache = [];

    public function groupName(): string
    {
        return 'MemoryHandler';
    }

    public function get(string $key): ?string
    {
        return $this->cache[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        $this->cache[$key] = $value;
    }

    public function getMulti(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->cache[$key])) {
                $result[$key] = $this->cache[$key];
            }
        }
        return $result;
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
