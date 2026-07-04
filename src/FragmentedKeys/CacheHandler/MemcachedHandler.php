<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\CacheHandler;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;

class MemcachedHandler implements CacheHandlerInterface
{
    public function __construct(
        private readonly \Memcached $memcached,
    ) {}

    public function groupName(): string
    {
        return 'MemcachedHandler';
    }

    public function get(string $key): ?string
    {
        $value = $this->memcached->get($key);
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return null;
        }
        return (string) $value;
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        $this->memcached->set($key, $value, $ttl ?? 0);
    }

    public function getMulti(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        $values = $this->memcached->getMulti($keys);
        if ($values === false) {
            return [];
        }
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = (string) $value;
        }
        return $result;
    }
}
