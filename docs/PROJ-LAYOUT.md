# Project Layout

PHP 8.4 library for fragmented cache key management and invalidation (Redis/Memcached/APCu).

```
fragmented-keys/
├── src/                                # Library source (PSR-4 autoload)
│   └── FragmentedKeys/                 # Namespace NoizuLabs\FragmentedKeys → [layout/src.md](layout/src.md)
│       ├── CacheHandler/               #   Cache backend adapters (APCu, Memcached, Memory, Redis)
│       ├── Key/                         #   Key implementations (StandardKey)
│       ├── Tag/                         #   Tag implementations (Standard, Constant, Delayed)
│       ├── CacheHandlerInterface.php    #   Cache handler interface
│       ├── Configuration.php            #   Global config (prefix, default handler)
│       ├── KeyInterface.php             #   Key interface
│       ├── KeyRing.php                  #   KeyRing — defines composite keys from tags
│       ├── KeyRingInterface.php         #   KeyRing interface
│       └── TagInterface.php             #   Tag interface
├── tests/                              # PHPUnit tests (PSR-4: NoizuLabs\FragmentedKeys\Tests)
│   ├── Integration/                    #   Redis/Memcached/APCu/mixed handler + key tests
│   └── *Test.php                       #   Unit tests (tags, keys, key ring, wire compat)
├── docs/                               # Project documentation
│   ├── PROJ-LAYOUT.md                  #   This file — navigable structure map
│   ├── PROJ-LAYOUT.summary.md          #   Tree-only companion for tools/agents
│   └── layout/src.md                   #   Detailed src/ breakdown
├── .github/workflows/ci.yml            # CI: tests + coverage gate (redis/memcached services)
├── Makefile                            # test / coverage / coverage-check / publish targets
├── phpunit.xml                         # Test suites (Unit, Integration) + backend env
├── phpstan.neon                        # Static analysis (level 8)
├── .php-cs-fixer.php                   # Code style (@PER-CS2.0, strict types)
├── .tool-versions                      # asdf/mise — pins PHP 8.4.21
├── composer.json                       # Package: noizu-labs/fragmented-keys
├── composer.lock                       # Locked dependencies
├── license                             # MIT license
└── README.md                           # Project documentation
```

## Key Files Requiring Setup

| File | Action |
|------|--------|
| `.tool-versions` | Run `asdf install` (or `mise install`) to get PHP 8.4.21 |
| `composer.json` | Run `composer install` to install dependencies |
| `Configuration.php` | Call `Configuration::setDefaultCacheHandler()` and `::setGlobalPrefix()` before use |
| Coverage driver | Install `pcov` (or `xdebug`) to run `make coverage` |
