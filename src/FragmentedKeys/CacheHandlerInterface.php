<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

interface CacheHandlerInterface
{
    public function groupName(): string;

    public function get(string $key): ?string;

    public function set(string $key, string $value, ?int $ttl = null): void;

    /**
     * @param list<string> $keys
     * @return array<string, string>
     */
    public function getMulti(array $keys): array;
}
