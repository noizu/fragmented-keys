<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\CacheHandler;

use NoizuLabs\FragmentedKeys\CacheHandlerInterface;

class APCuHandler implements CacheHandlerInterface
{
    public function groupName(): string
    {
        return 'APCuHandler';
    }

    public function get(string $key): ?string
    {
        $value = apcu_fetch($key, $success);
        if (!$success) {
            return null;
        }
        return (string) $value;
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        apcu_store($key, $value, $ttl ?? 0);
    }

    public function getMulti(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        $values = apcu_fetch($keys, $success);
        if (!$success || !is_array($values)) {
            return [];
        }
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = (string) $value;
        }
        return $result;
    }
}
