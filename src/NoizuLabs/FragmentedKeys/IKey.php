<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The various key groups are merged together to form our final cache key.
 */
interface IKey
{
    /*
     * Append Tag to Key.  
     */
    public function AddKeyGroup(\NoizuLabs\FragmentedKeys\ITag $tag);
    
    /*
     * Retrieve Key
     */
    public function getKey();
}
