# Project Architecture — Summary

## Overview

PHP 8.4 library for version-based cache invalidation. A composite cache key is
built from independently versioned **tags**; the key string embeds each tag's
current version. Incrementing a tag's version invalidates every derived key that
uses it in a single O(1) write — no scans or bulk deletes; orphaned entries fall
off via TTL/LRU. The library stores only tag→version counters in a pluggable
backend (Redis / Memcached / APCu / in-memory) and computes key strings.

## Core Components

- **StandardKey** (`KeyInterface`) — composite key; resolves tag versions, emits md5 key string.
- **StandardTag** (`TagInterface`) — named versioned dimension (`tag_instance`); persists version.
- **ConstantTag** — immutable fixed-version tag; no cache lookup, no-op mutations.
- **DelayedTag** — grace window; serves previous version for `delaySeconds` after an increment.
- **KeyRing** (`KeyRingInterface`) — factory/registry; define key templates, mint keys/tags with merged options.
- **CacheHandlerInterface** — backend adapter: `groupName`, `get`, `set`, `getMulti`.
- **Configuration** — process-global default handler + global key prefix.

## Read Path

`KeyRing` (or direct construction) yields a `StandardKey`. `getKeyStr()` groups
tags by handler `groupName()`, batch-fetches versions with one `getMulti()` per
backend, applies versions (resetting missing tags), concatenates
`label:t{tag}:v{version}…`, returns md5 hash.

## Invalidation Path

Build the tag and call `increment()`: read current version, +1, persist. Future
`getKeyStr()` calls including that tag emit a new hash, orphaning prior entries.
No dependent keys are enumerated or deleted.

## Option Resolution (KeyRing)

Merge precedence: `globalOptions` → `globalTagOptions[tag]` → key `globals` →
per-param options. `type` picks the tag class; `cache_handler`, `version`,
`prefix`, `delay_seconds` tune it. `get{Name}KeyObj()` maps to defined templates.

## Technology Stack

PHP ^8.4 (strict types). `ext-json` required; redis/memcached/apcu suggested.
PSR-4 `NoizuLabs\FragmentedKeys\` → `src/FragmentedKeys/`. PHPUnit 11 (Unit +
Integration), PHPStan level 8, php-cs-fixer (@PER-CS2.0). CI on GitHub Actions
with redis + memcached services and a coverage gate.

## Key Decisions

- Invalidate by version bump, not deletion (O(1), TTL/LRU reclaims orphans).
- Batched version resolution grouped by backend; ConstantTag opts out of lookups.
- Mixed backends per key — tags in one key may live on different handlers.
- Reset versions are microsecond int64 timestamps (monotonic, no float precision loss).
- Global-static `Configuration` defaults, overridable per tag.
