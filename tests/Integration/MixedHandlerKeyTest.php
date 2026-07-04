<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests\Integration;

use NoizuLabs\FragmentedKeys\CacheHandler\MemcachedHandler;
use NoizuLabs\FragmentedKeys\CacheHandler\RedisHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class MixedHandlerKeyTest extends TestCase
{
    private RedisHandler $redisHandler;
    private MemcachedHandler $memcachedHandler;
    private \Redis $redis;
    private \Memcached $memcached;

    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('ext-redis not available.');
        }
        if (!extension_loaded('memcached')) {
            self::markTestSkipped('ext-memcached not available.');
        }

        $redisHost = getenv('FK_REDIS_HOST') ?: '127.0.0.1';
        $redisPort = (int) (getenv('FK_REDIS_PORT') ?: 6380);
        $mcHost = getenv('FK_MEMCACHED_HOST') ?: '127.0.0.1';
        $mcPort = (int) (getenv('FK_MEMCACHED_PORT') ?: 11212);

        $this->redis = new \Redis();
        try {
            $this->redis->connect($redisHost, $redisPort, 1.0);
        } catch (\RedisException) {
            self::markTestSkipped("Redis not running on {$redisHost}:{$redisPort}.");
        }

        $this->memcached = new \Memcached();
        $this->memcached->addServer($mcHost, $mcPort);
        $stats = $this->memcached->getStats();
        if ($stats === false || $stats === []) {
            self::markTestSkipped("Memcached not running on {$mcHost}:{$mcPort}.");
        }

        $this->redis->flushDb();
        $this->memcached->flush();

        $this->redisHandler = new RedisHandler($this->redis);
        $this->memcachedHandler = new MemcachedHandler($this->memcached);

        Configuration::reset();
        Configuration::setDefaultCacheHandler($this->redisHandler);
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->flushDb();
        }
        if (isset($this->memcached)) {
            $this->memcached->flush();
        }
    }

    public function testKeyWithMixedHandlers(): void
    {
        $redisTag = new StandardTag('User', '42', handler: $this->redisHandler);
        $mcTag = new StandardTag('City', 'chicago', handler: $this->memcachedHandler);

        $key = new StandardKey('Dashboard', [$redisTag, $mcTag]);
        $k1 = $key->getKeyStr();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $k1);
    }

    public function testMixedKeyStable(): void
    {
        $redisTag = new StandardTag('User', '42', handler: $this->redisHandler);
        $mcTag = new StandardTag('City', 'chicago', handler: $this->memcachedHandler);

        $key = new StandardKey('Dashboard', [$redisTag, $mcTag]);
        $k1 = $key->getKeyStr();
        $k2 = $key->getKeyStr();
        self::assertSame($k1, $k2);
    }

    public function testMixedKeyChangesOnRedisIncrement(): void
    {
        $redisTag = new StandardTag('User', '42', handler: $this->redisHandler);
        $mcTag = new StandardTag('City', 'chicago', handler: $this->memcachedHandler);

        $key = new StandardKey('Dashboard', [$redisTag, $mcTag]);
        $before = $key->getKeyStr();
        $redisTag->increment();
        $after = $key->getKeyStr();
        self::assertNotSame($before, $after);
    }

    public function testMixedKeyChangesOnMemcachedIncrement(): void
    {
        $redisTag = new StandardTag('User', '42', handler: $this->redisHandler);
        $mcTag = new StandardTag('City', 'chicago', handler: $this->memcachedHandler);

        $key = new StandardKey('Dashboard', [$redisTag, $mcTag]);
        $before = $key->getKeyStr();
        $mcTag->increment();
        $after = $key->getKeyStr();
        self::assertNotSame($before, $after);
    }

    public function testDelegationGroupsCorrectly(): void
    {
        $redisTag = new StandardTag('User', '42', handler: $this->redisHandler);
        $mcTag = new StandardTag('City', 'chicago', handler: $this->memcachedHandler);

        self::assertTrue($redisTag->delegateCacheQuery('RedisHandler'));
        self::assertFalse($redisTag->delegateCacheQuery('MemcachedHandler'));
        self::assertTrue($mcTag->delegateCacheQuery('MemcachedHandler'));
        self::assertFalse($mcTag->delegateCacheQuery('RedisHandler'));
    }
}
