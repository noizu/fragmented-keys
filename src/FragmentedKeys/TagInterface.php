<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

interface TagInterface
{
    public function getTagName(): string;

    public function getTagVersion(): int;

    public function getFullTag(): string;

    public function increment(): void;

    public function resetTagVersion(): void;

    public function setTagVersion(int $version, bool $persist = false): void;

    public function getCacheHandler(): CacheHandlerInterface;

    public function setCacheHandler(CacheHandlerInterface $handler): void;

    public function delegateCacheQuery(string $group): bool;
}
