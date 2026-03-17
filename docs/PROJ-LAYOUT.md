# Project Layout

PHP library for fragmented cache key management and invalidation (Memcache/Memcached/APC).

```
fragmented-keys/
├── src/                                # Library source (PSR-0 autoload)
│   └── NoizuLabs/FragmentedKeys/       # Main namespace → [layout/src.md](layout/src.md)
│       ├── CacheHandler/               #   Cache backend adapters
│       ├── Key/                         #   Key implementations
│       ├── Tag/                         #   Tag implementations
│       ├── Configuration.php            #   Global config (DI, prefix, default handler)
│       ├── ICacheHandler.php            #   Cache handler interface
│       ├── IKey.php                     #   Key interface
│       ├── IKeyRing.php                 #   KeyRing interface
│       ├── ITag.php                     #   Tag interface
│       ├── KeyRing.php                  #   KeyRing — defines composite keys from tags
│       └── Tag.php                      #   Base tag class
├── test/                               # PHPUnit tests
│   ├── bootstrap.php                   #   Test bootstrap / autoloader setup
│   └── FragmentedKeysTest.php          #   Main test suite
├── .gitignore                          # Ignores vendor/
├── composer.json                       # Package: noizu-labs/fragmented-keys
├── composer.lock                       # Locked dependencies
├── license                             # MIT license
└── README.md                           # Project documentation
```

## Key Files Requiring Setup

| File | Action |
|------|--------|
| `composer.json` | Run `composer install` to install dependencies |
| `Configuration.php` | Call `Configuration::setDefaultCacheHandler()` and `::setGlobalPrefix()` before use |
