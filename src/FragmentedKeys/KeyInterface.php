<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

interface KeyInterface
{
    public function addTag(TagInterface $tag): void;

    public function getKeyStr(bool $hash = true): string;
}
