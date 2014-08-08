<?php
namespace NoizuLabs\FragmentedKeys\CacheHandler;


class Memcached implements \NoizuLabs\FragmentedKeys\ICacheHandler {
    /*@var $handle \Memcached */
    protected $handle;
    protected $lastFetch = null;
    
    public function __construct(\Memcached $handle) {
        $this->handle = $handle;        
    }
    
    public function getGroupName() {
        return __CLASS__;
    }
    
    public function get($key) {
        $t = $this->handle->get($key);
        return $t;
    }

    public function set($key, $value, $expiration = null) {
        return $this->handle->set($key, $value, $expiration);        
    }
    
    public function getMulti(array $keys) {
        return  $this->handle->getMulti($keys);
    }    
}