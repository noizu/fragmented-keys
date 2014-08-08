FragmentedKey
===========

Fragmented Keys provide a straightforward way to manage and invalidate fragemented memcache keys. 

It does this by persisting in memcache (or other back-end of your discretion) various tag versionining information. When constructing composite/fragmented keys tags and their versions are used to generate the final key. 

Thus if you wanted to tie something to to the granularity of say a specific thing like username you can construct a key such as, 

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
  $UserUserNameTag->Increment(); 
```

```  
  AllKey
    \ 
     \-[ ]Global.Greeting
            \
             \-[ ]Other User Cached Greeting
              \
               \-[x]Target User
```  
    
Or you could just go crazy and invalidate all keys that rely on Global.Greating

```php
  $GlobalGreetingTag->Increment(); 
```
  
```  
  AllKey
    \ 
     \-[x]Global.Greeting
            \
             \-[x]Other User Cached Greeting
              \
               \-[x]Target User
```    

Setup Note
==
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



Cache Handlers
========================
```
$apcHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Apc();
$inMemoryHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memory();
$memcachedHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memcached(new Memcached());
$memcacheHandler = \NoizuLabs\FragmentedKeys\CacheHandler\Memcache(new Memcache());
```

Tag Typs
===================

| Tag Class | Description|
|-----------|------------|
| Standard  | Basic Key. Persists version to specified cache handler                                                            |
| Delayed*   | Basic Key with built in Delay. Only internal key versions greater than the specified delay will cause a new  version to be returned. Allowing you to pull cached content that only updates every hours, 30 seconds, etc. |
| Constant    | Key with Constant associated Version. E.g. version can only be set once at construction time. useful for incorporating non version tag-instance details in large composite keys | 

*Delayed is not yet implemented

Key Rings
===================
Key rings help may your life easier by letting you define common key structures one and then reuse him in your code as needed. 
You can tweak settings in your config, or even define custom keys that always include some additional tags* with out requiring your cache caller to manually include them*

Example
```

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

```
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