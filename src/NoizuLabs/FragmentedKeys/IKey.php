<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The various key groups are merged together to form our final cache key.
 */
interface IKey
{
    public function __construct($key, $keyId, array $tags);
    public function AddKeyGroup(ITag $tag);
    public function getKey();
}
