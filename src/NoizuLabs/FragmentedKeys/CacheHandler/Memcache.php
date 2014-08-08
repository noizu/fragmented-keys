<?php
namespace NoizuLabs\FragmentedKeys\CacheHandler;


class Memcache implements \NoizuLabs\FragmentedKeys\ICacheHandler {
    /*@var $handle \Memcache */
    protected $handle;
    
    public function __construct(\Memcache $handle) {
        $this->handle = $handle;        
    }

    public function getGroupName() {
        return __CLASS__;
    }
    
    public function get($key) {
        return $this->handle->get($key);        
    }

    public function set($key, $value, $expiration = null) {
        return $this->handle->set($key, $value, $expiration);        
    }
    
    public function getMulti(array $keys) {
        return  $this->handle->get($keys);
    }    
}