<?php
namespace NoizuLabs\FragmentedKeys\CacheHandler;


class Memory implements \NoizuLabs\FragmentedKeys\ICacheHandler {
    protected $cache = array();
        
    public function __construct() {
    }
    
    public function get($key) {
        return isset($this->cache[$key]) ? $this->cache[$key] : false;
    }
  
    public function getGroupName() {
        return __CLASS__;
    }

    public function set($key, $value, $expiration = 0) {
        $this->cache[$key] = $value;
    }

    public function getMulti(array $keys) {
        return array_intersect_key($this->cache, $keys);
    }
}