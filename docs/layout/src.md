# src/FragmentedKeys/

Core library namespace for the fragmented key system.
Namespace `NoizuLabs\FragmentedKeys` (PSR-4, mapped to `src/FragmentedKeys/`).

## Interfaces

| File | Purpose |
|------|---------|
| `CacheHandlerInterface.php` | Contract for cache backends: `groupName`, `get`, `set`, `getMulti` |
| `KeyInterface.php` | Contract for composite keys: `addTag`, `getKeyStr` |
| `KeyRingInterface.php` | Contract for key factories: `defineKey`, `getKeyObj`, `tag` |
| `TagInterface.php` | Contract for versioned tags: version management, increment, reset, cache handler |

## Core Classes

| File | Purpose |
|------|---------|
| `Configuration.php` | Static config — default cache handler & global key prefix |
| `KeyRing.php` | Defines composite cache keys from arrays of tags with global/per-tag options |

## CacheHandler/

Backend adapters implementing `CacheHandlerInterface`:

| File | Backend |
|------|---------|
| `APCuHandler.php` | APCu extension |
| `MemcachedHandler.php` | `Memcached` extension |
| `MemoryHandler.php` | In-memory array (testing) |
| `RedisHandler.php` | `Redis` extension |

## Key/

| File | Purpose |
|------|---------|
| `StandardKey.php` | Default key implementation — assembles final cache key from tag versions |

## Tag/

| File | Purpose |
|------|---------|
| `StandardTag.php` | Default tag — stores/retrieves version from cache handler |
| `ConstantTag.php` | Fixed-version tag — version never changes (no cache lookup) |
| `DelayedTag.php` | Tag with a grace window — serves the previous version for `delaySeconds` after an increment |
