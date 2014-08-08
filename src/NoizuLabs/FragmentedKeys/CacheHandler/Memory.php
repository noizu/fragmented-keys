<?php
namespace NoizuLabs\FragmentedKeys\CacheHandler;


class Memory implements \NoizuLabs\FragmentedKeys\ICacheHandler {
    static protected $cache = array();
        
    public function __construct() {
        
    }
    
    public function get($key) {        
        return isset(self::$cache[$key]) ? self::$cache[$key] : false;
    }
  
    public function getGroupName() {
        return __CLASS__;
    }

    public function set($key, $value, $expiration = 0) {
        self::$cache[$key] = $value;
    }

    public function getMulti(array $keys) {        
        $akeys = array(); 
        foreach($keys as $k) {
            $akeys[$k] = true; 
        }
        return array_intersect_key(self::$cache, $akeys);
    }
}