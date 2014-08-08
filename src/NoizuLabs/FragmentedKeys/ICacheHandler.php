<?php
namespace NoizuLabs\FragmentedKeys;

/**
 * The Key Group is the basic tag + version_info node which our fragmented
 * key system relies on.
 */
interface ICacheHandler
{
    /**
     * Constant value used to differentiate between handles when performing multi-gets inside of Key class.
     */
    public function getGroupName();
    
    /**
     * fetch value from cache
     */
    public function get($key);
    
    /**
     * set value in cache. 
     */
    public function set($key, $value, $period);
    
    /**
     * fetch multiple values from cache. 
     */
    public function getMulti(array $keys);
}