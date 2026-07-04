<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\CacheHandler;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;

class RedisHandler implements CacheHandlerInterface
{
    public function __construct(
        private readonly \Redis $redis,
    ) {}

    public function groupName(): string
    {
        return 'RedisHandler';
    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);
        if ($value === false) {
            return null;
        }
        return (string) $value;
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        if ($ttl !== null) {
            $this->redis->setex($key, $ttl, $value);
        } else {
            $this->redis->set($key, $value);
        }
    }

    public function getMulti(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        /** @var list<string|false> $values */
        $values = $this->redis->mget($keys);
        $result = [];
        foreach ($values as $i => $value) {
            if ($value !== false && isset($keys[$i])) {
                $result[$keys[$i]] = (string) $value;
            }
        }
        return $result;
    }
}
