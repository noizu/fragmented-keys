<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

interface KeyRingInterface
{
    /**
     * @param list<string|array<string, mixed>> $params
     * @param array<string, mixed> $globals
     */
    public function defineKey(string $key, array $params, array $globals = []): void;

    /**
     * @param list<string> $tagValues
     */
    public function getKeyObj(string $key, array $tagValues): KeyInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function tag(string $tag, string $instance, array $options = []): TagInterface;
}
