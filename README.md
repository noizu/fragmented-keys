FragmentedKey
===========

tl;dr;
A php library for managing cache invalidation by tracking tag-value pair versions on memcache, apc or other storage device and generating derived cache keys based on those values. 

For a Java port of this library please see (https://github.com/noizu/fragmented-keys-4java)

Overview
----------
Fragmented Keys provide a straightforward way to manage and invalidate composite cache keys. 

It does this by persisting in memcache (or other back-end persistance layer of your choice)  tag-instance versionining information. When constructing composite/fragmented keys these tags and their versions are used to generate the final composite key. 

Thus if you wanted to tie something to to the granularity of say a specific thing like username you can do the following:

```php
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;

$globalGreetingTag = new StandardTag("Global.Greeting", "global");
$userUserNameTag = new StandardTag("User.Username", $userId);
$keyObj = new StandardKey(
    "CacheDataThatInvalidatesWhenUserNamesAreChanged",
    [$userUserNameTag, $globalGreetingTag],
);
$cacheKey = $keyObj->getKeyStr();
```

You can then Invalidate only items linked to your user's username by calling:
```php
$userUserNameTag = new StandardTag("User.Username", $userId);
$userUserNameTag->increment();
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
$globalGreetingTag->increment();
```
  
but amke sure your database is ready for it. 
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
The code depends on a Redis, Memcached or APCu handle being available and configured along with a global prefix to
avoid collisions.

```php
use NoizuLabs\FragmentedKeys\CacheHandler\APCuHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\MemcachedHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;

$m = new \Memcached();
Configuration::setDefaultCacheHandler(new MemcachedHandler($m));
Configuration::setGlobalPrefix("MyApp");

// you may override the handler per tag by calling
$tag->setCacheHandler($alternativeHandler);

// or by passing a handler to the constructor.
$tag = new StandardTag("Users", "1234", null, new APCuHandler());
```


Components
=================

Cache Handlers
-------
```php
use NoizuLabs\FragmentedKeys\CacheHandler\APCuHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\MemcachedHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\RedisHandler;

$apcuHandler     = new APCuHandler();
$inMemoryHandler = new MemoryHandler();
$memcachedHandler = new MemcachedHandler(new \Memcached());
$redisHandler    = new RedisHandler(new \Redis());
```

Tags
--------

Tags are a logical grouping that you would under certain circumstance invalidate assocaited cached data. 

A User:$id pair,  a Site:$siteId, etc. This library takes they tag-instance pairs and appends @version fields to them so that when you want to invalidate a large swatch of related items you don't need to send dozens of invalidate requests to memcache, or apc. You just make a single $tag->increment() call and any associated keys that us that tag-instance (User:$userId) will generate new keys; 


| Tag Class    | Description|
|--------------|------------|
| StandardTag  | Basic tag. Persists version to the specified cache handler.                                                       |
| DelayedTag   | Tag with a built-in grace window. For `delaySeconds` after an increment, readers keep seeing the previous version, letting you serve cached content that only rolls over every N seconds/minutes/hours. |
| ConstantTag  | Tag with a constant associated version, set once at construction time. Useful for incorporating non-versioned tag-instance details in large composite keys. |

Via a `KeyRing`, select the type with the `'type'` option (`'standard'`, `'delayed'`, or `'constant'`); `DelayedTag` also reads a `'delay_seconds'` option.

Key Rings
---------
Key rings help may your life easier by letting you define common key structures one and then reuse him in your code as needed. 
You can tweak settings in your config, or even define custom keys that always include some additional tag s *with out requiring your cache caller to manually include them!*

Example
```php
<?php
use NoizuLabs\FragmentedKeys\CacheHandler\MemcachedHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\KeyRing;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;

    //=================================================
    // Config stuff you only need to do this once
    //=================================================
    /* Somewhere in your bootstrap or wherever you instantiate a KeyRing or KeyRing-derived class */
    $cacheHandlers = [
        'memcache' => new MemcachedHandler($this->container['memcache']),
        'memory'   => new MemoryHandler(),
    ];
    $globalOptions = [
        'type' => 'standard',
    ];
    $tagOptions = [
        'universe' => ['type' => 'constant', 'version' => 5],
    ];
    $ring = new KeyRing($globalOptions, $tagOptions, 'memcache', $cacheHandlers);

    /* define your keys */
    $ring->defineKey("Users", ['universe', ['tag' => 'planet', 'cache_handler' => 'memory', 'version' => null, 'type' => 'standard'], 'city']);


    //==============================================
    // Need to check for some cached data? Generating your key now takes one line instead of 5;
    //===============================================
    $userKey = $ring->getUsersKeyObj('MilkyWay', 'Earth', 'Chicago')->getKeyStr();
    $users = $memcache->get($userKey);
    if (!$users) {
        $users = query("select * from users where universe='MilkyWay' AND planet='Earth' AND city='Chicago'");
        $memcache->set($userKey, $users);
    }

    /* Invalidate them */
    $universeTag = new StandardTag('universe', 'MilkyWay');
    $universeTag->increment();


    //===============================
    // Manual (no KeyRing) equivalent
    //===============================
    $universeTag = new ConstantTag("universe", "MilkyWay", 5);
    $worldTag = new StandardTag("planet", "Earth");
    $cityTag = new StandardTag("city", "Chicago", null, new MemoryHandler());
    $key = new StandardKey("Users", [$universeTag, $worldTag, $cityTag]);
    $userKey = $key->getKeyStr();
    $users = $memcache->get($userKey);
    if (!$users) {
        $users = query("select * from users where universe='MilkyWay' AND planet='Earth' AND city='Chicago'");
        $memcache->set($userKey, $users);
    }

    /* Invalidate them */
    $universeTag->increment();
```

*The ability to auto include params isnt fully backed into the config process yet but you can emulate it easily by extending the base keyring class and doing the following

```php
use NoizuLabs\FragmentedKeys\KeyInterface;
use NoizuLabs\FragmentedKeys\KeyRing;

class MyGames extends KeyRing
{
    public function getGameDescriptionKeyObj(string $gameId): KeyInterface
    {
         /* Define Key in the usual manner  */

         /* . . . */

         $gameSiteId = $this->pimpleContainer['gameSite']; 
         $userId = $this->getUserId(); 
         return $this->getKeyObj("GameDescription", [$gameId, $gameSiteId, $userId]);
    }
}

// Now your cache code looks simple
$gameDescCacheKey = $ring->getGameDescriptionKeyObj($gameId)->getKeyStr(); 

```
