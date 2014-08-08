FragmentedKey
===========

tl;dr;
A php library for managing cache invalidation by tracking tag-value pair versions on memcache, apc or other storage device and generating derived cache keys based on those values. 


Overview
----------
Fragmented Keys provide a straightforward way to manage and invalidate composite cache keys. 

It does this by persisting in memcache (or other back-end persistance layer of your choice)  tag-instance versionining information. When constructing composite/fragmented keys these tags and their versions are used to generate the final composite key. 

Thus if you wanted to tie something to to the granularity of say a specific thing like username you can do write do the following:

```php
  $GlobalGreetingTag = new Tag\Standard("Global.Greeting", "global");
  $UserUserNameTag = new Tag\Standard("User.Username", $userId);
  $theKey = new Key\Standard(
      "CacheDataTheInvalidateSWhenUserNamesAreChanges", 
      "KeyInstanceId", 
      array($UserUserNameTag, $GlobalGreetingTag));
  $cacheKey = $theKey->getKey(); 
```

You could then Invalidate only items linked to your user's username by calling:

```php
  $UserUserNameTag = new Tag\Standard("User.Username", $userId);
  $UserUserNameTag->Increment(); 
```


Behind the scenes this looks something like blocking out everything below the [Targeted User] bucket. 
```  
  AllKey
    \ 
     \-[ ]Global.Greeting
            \
             \-[ ]Other User Cached Greeting
              \
               \-[x]Targeted User
```  
    
Or you could just go crazy and invalidate all keys that rely on Global.Greating

```php
  $GlobalGreetingTag->Increment(); 
```
  
I hope your database is ready for it. 
```  
  AllKey
    \ 
     \-[x]Global.Greeting
            \
             \-[x]Other User Cached Greeting
              \
               \-[x]Target User
```    

Setup & Installation 
==

Installation
---
This project is available on composer, just add noizu-labs/fragmented-keys to your required list. 
    "require": {
        "noizu-labs/fragmented-keys": "dev-master",
    }

Setup
-----
The code depends on a Memcached, Memcache or APC handle being available and configured along with a global prefix to 
avoid collisions. 

```php

$m = new \Memcached;
\NoizuLabs\FragmentedKeys\Configuration\setGlobalCacheHandler(new \NoizuLabs\FragmentedKeys\CacheHandler\Memcached($m));
\NoizuLabs\FragmentedKeys\Configuration\setGlobalPrefix("MyApp");

//you may override the handler per tag by calling 
$tag->setCacheHandler($alternativeHandler); 

//or by including a handler in you constructor. 
$tag = new Tag\Standard("Users", 1234, null, CacheHandler\Apc());

```


Components
=================

Cache Handlers
-------
```php
$apcHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Apc();
$inMemoryHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memory();
$memcachedHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memcached(new Memcached());
$memcacheHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memcache(new Memcache());
```

Tags
--------

Tags are a logical grouping that you would under certain circumstance invalidate assocaited cached data. 

A User:$id pair,  a Site:$siteId, etc. This library takes they tag-instance pairs and appends @version fields to them so that when you want to invalidate a large swatch of related items you don't need to send dozens of invalidate requests to memcache, or apc. You just make a single $tag->increment() call and any associated keys that us that tag-instance (User:$userId) will generate new keys; 


| Tag Class | Description|
|-----------|------------|
| Standard  | Basic Key. Persists version to specified cache handler                                                            |
| Delayed*   | Basic Key with built in Delay. Only internal key versions greater than the specified delay will cause a new  version to be returned. Allowing you to pull cached content that only updates every hours, 30 seconds, etc. |
| Constant    | Key with Constant associated Version. E.g. version can only be set once at construction time. useful for incorporating non version tag-instance details in large composite keys | 

*Delayed is not yet implemented

Key Rings
---------
Key rings help may your life easier by letting you define common key structures one and then reuse him in your code as needed. 
You can tweak settings in your config, or even define custom keys that always include some additional tag s *with out requiring your cache caller to manually include them!*

Example
```php
<?php
    //=================================================
    // Config stuff you only need to do this once
    //=================================================
    /* Somewhere in you bootstrap or wherever you instiatiate a KeyRing or KeyRing derived Class */
    $cacheHandlers = array(
        'memcache' => new \NoizuLabs\FragmentedKeys\CacheHandler\Memcached($this->container['memcache']),
        'memory' => new \NoizuLabs\FragmentedKeys\CacheHandler\Memory()
        );
    $globalOptions = array(
      'type' => 'standard'  
    );
    $tagOptions = array(
        'universe' => array('type' => 'constant', 'version' => 5)
    );
    $ring = new FragmentedKeys\KeyRing($globalOptions,  $tagOptions, 'memcache', $cacheHandlers);

    /* define you keys */
    $ring->DefineKey("Users", array('universe', array('tag' => 'planet' , 'cacheHandler' => 'memory', 'version' => null, 'type'=>'standard'), 'city'));


    //==============================================
    // Need to check for some cached data? Generating you key now takes one line instead of 5;
    //===============================================
    $users = $ring->getUsersKeyObj('MilkyWay', 'Earth', 'Chicago')->getKeyStr();
    $users = $memcache->get($userKey);
    if(!users) {
          $users = query("select * from users where universe='MilkyWay' AND planet='Earth' AND 'city' => 'Chicago'");
          $memcache->set($userKey, $users);
    }
    
    /* Invalidate them */
    $universeTag = new Tag\Standard('universe', 'MilkyWay'); 
    $universeTag->increment(); 


    //===============================
    // Old Method
    //===============================
    $universeTag = new Tag\Constant("universe", "MilkyWay",5);
    $worldTag = new Tag\Standard("planet", "Earth");
    $cityTag = new Tag\Standard("city", "Chicago", null, new FragmentedKeys\CacheHandler\Memory());
    $key = new Key\Standard("Users", array($universeTag, $worldTag, $cityTag); 
    $userKey = $key->getKeyStr();
    $users = $memcache->get($userKey);
    if(!users) {
          $users = query("select * from users where universe='MilkyWay' AND planet='Earth' AND 'city' => 'Chicago'");
          $memcache->set($userKey, $users);
    }
    
    /* Invalidate them */
    $universeTag->increment(); 
```

*The ability to auto include params isnt fully backed into the config process yet but you can emulate it easily by extending the base keyring class and doing the following

```php
class MyGames extends NoizuLabs\FragmentedKeys\KeyRing {
    public getGameDescriptionKeyObj($gameId) {
         /* Define Key in the usual manner  */

         /* . . . */

         $gameSiteId = $this->pimpleContainer['gameSite']; 
         $userId = $this->getUserId(); 
         return $this->getKeyObj("GameDescription", array( $gameId, $gameSiteId, $userId, ... etc.));
    }
}

// Now your cache code looks simple
$gameDescCacheKey = $ring->getGameDescriptionKeyObj($gameId)->getKeyStr(); 

```
