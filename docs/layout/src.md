# src/NoizuLabs/FragmentedKeys/

Core library namespace for the fragmented key system.

## Interfaces

| File | Purpose |
|------|---------|
| `ICacheHandler.php` | Contract for cache backends: `get`, `set`, `getMulti`, `getGroupName` |
| `IKey.php` | Contract for composite keys: `AddKeyGroup`, `getKey` |
| `IKeyRing.php` | Contract for key factories: `DefineKey`, tag/global options |
| `ITag.php` | Contract for versioned tags: version management, increment, reset |

## Core Classes

| File | Purpose |
|------|---------|
| `Configuration.php` | Static config — default cache handler & global key prefix (supports Pimple DI backwards compat) |
| `KeyRing.php` | Defines composite cache keys from arrays of tags with global/per-tag options |
| `Tag.php` | Base tag — versioned cache key segment stored in cache backend |

## CacheHandler/

Backend adapters implementing `ICacheHandler`:

| File | Backend |
|------|---------|
| `Apc.php` | APC/APCu extension |
| `Memcache.php` | `Memcache` extension (legacy) |
| `Memcached.php` | `Memcached` extension |
| `Memory.php` | In-memory array (testing) |

## Key/

| File | Purpose |
|------|---------|
| `Standard.php` | Default key implementation — assembles final cache key from tag versions |

## Tag/

| File | Purpose |
|------|---------|
| `Standard.php` | Default tag — stores/retrieves version from cache handler |
| `Constant.php` | Fixed-version tag — version never changes (no cache lookup) |
