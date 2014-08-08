<?php
namespace NoizuLabs\FragmentedKeys\CacheHandler;


class Apc implements \NoizuLabs\FragmentedKeys\ICacheHandler {
    public function __construct() {
        if(!(extension_loaded('apc') && ini_get('apc.enabled')))
        {
            trigger_error("You must enable APC on your system before using an FragmentedKey backed by NoizuLabs\FragmentedKeys\CacheHandle\Apc", E_USER_ERROR);
        }
    }
    
    public function get($key) {
        return apc_fetch($key);
    }
  
    public function getGroupName() {
        return __CLASS__;
    }

    public function set($key, $value, $expiration = 0) {
        return apc_store($key, $value, $expiration);
    }

    public function getMulti(array $keys) {
        return apc_fetch($keys);
    }
}