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
The code depends on Memcache being available and set on a global pimple container named $container. 

```php
global $container;
$container = new \Pimple();
$m = new \Memcache;
$conn = $m->connect('127.0.0.1', '11211');
$container['memcache'] = $m;
```
