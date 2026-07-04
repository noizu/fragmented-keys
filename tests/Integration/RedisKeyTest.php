<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests\Integration;

use NoizuLabs\FragmentedKeys\CacheHandler\RedisHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class RedisKeyTest extends TestCase
{
    private RedisHandler $handler;
    private \Redis $redis;

    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('ext-redis not available.');
        }

        $host = getenv('FK_REDIS_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('FK_REDIS_PORT') ?: 6380);

        $this->redis = new \Redis();
        try {
            $this->redis->connect($host, $port, 1.0);
        } catch (\RedisException) {
            self::markTestSkipped("Redis not running on {$host}:{$port}.");
        }

        $this->redis->flushDb();
        $this->handler = new RedisHandler($this->redis);
        Configuration::reset();
        Configuration::setDefaultCacheHandler($this->handler);
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->flushDb();
        }
    }

    public function testKeyStableWithoutIncrement(): void
    {
        $tag = new StandardTag('User', '42');
        $key = new StandardKey('Dashboard', [$tag]);
        $k1 = $key->getKeyStr();
        $k2 = $key->getKeyStr();
        self::assertSame($k1, $k2);
    }

    public function testKeyChangesAfterIncrement(): void
    {
        $tag = new StandardTag('User', '42');
        $key = new StandardKey('Dashboard', [$tag]);
        $before = $key->getKeyStr();
        $tag->increment();
        $after = $key->getKeyStr();
        self::assertNotSame($before, $after);
    }

    public function testConstantTagNeverChangesKey(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 1);
        $key = new StandardKey('Dashboard', [$tag]);
        $before = $key->getKeyStr();
        $tag->increment();
        $after = $key->getKeyStr();
        self::assertSame($before, $after);
    }

    public function testMultipleTagsWithRedis(): void
    {
        $tag1 = new StandardTag('User', '42');
        $tag2 = new StandardTag('City', 'chicago');
        $key = new StandardKey('Dashboard', [$tag1, $tag2]);

        $k1 = $key->getKeyStr();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $k1);

        $tag1->increment();
        $k2 = $key->getKeyStr();
        self::assertNotSame($k1, $k2);
    }

    public function testVersionSurvivesNewTagInstance(): void
    {
        $tag1 = new StandardTag('User', '42');
        $v1 = $tag1->getTagVersion();
        $tag1->increment();
        $v2 = $tag1->getTagVersion();

        $tag2 = new StandardTag('User', '42');
        $v3 = $tag2->getTagVersion();
        self::assertSame($v2, $v3);
    }

    public function testIncrementIsolation(): void
    {
        $tag1 = new StandardTag('User', '1');
        $tag2 = new StandardTag('User', '2');

        $key1 = new StandardKey('Profile', [$tag1]);
        $k1Before = $key1->getKeyStr();

        $tag2->increment();

        $k1After = $key1->getKeyStr();
        self::assertSame($k1Before, $k1After);
    }
}
