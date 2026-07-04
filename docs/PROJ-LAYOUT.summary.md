```
fragmented-keys/
├── src/FragmentedKeys/           # namespace NoizuLabs\FragmentedKeys (PSR-4)
│   ├── CacheHandler/             # APCuHandler, MemcachedHandler, MemoryHandler, RedisHandler
│   ├── Key/                      # StandardKey
│   ├── Tag/                      # ConstantTag, DelayedTag, StandardTag
│   ├── CacheHandlerInterface.php
│   ├── Configuration.php
│   ├── KeyInterface.php
│   ├── KeyRing.php
│   ├── KeyRingInterface.php
│   └── TagInterface.php
├── tests/                        # Unit + Integration/
├── docs/                         # PROJ-LAYOUT.md, PROJ-LAYOUT.summary.md, layout/src.md
├── .github/workflows/ci.yml
├── Makefile
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.php
├── .tool-versions                # PHP 8.4.21 (asdf/mise)
├── composer.json
├── composer.lock
├── license
└── README.md
```
