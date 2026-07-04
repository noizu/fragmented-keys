<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests\Integration;

use NoizuLabs\FragmentedKeys\CacheHandler\RedisHandler;
use PHPUnit\Framework\TestCase;

final class RedisHandlerTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->flushDb();
        }
    }

    public function testGroupName(): void
    {
        self::assertSame('RedisHandler', $this->handler->groupName());
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        self::assertNull($this->handler->get('nonexistent'));
    }

    public function testSetAndGet(): void
    {
        $this->handler->set('key1', 'value1');
        self::assertSame('value1', $this->handler->get('key1'));
    }

    public function testSetWithTtl(): void
    {
        $this->handler->set('ttl_key', 'value', 1);
        self::assertSame('value', $this->handler->get('ttl_key'));
        sleep(2);
        self::assertNull($this->handler->get('ttl_key'));
    }

    public function testGetMulti(): void
    {
        $this->handler->set('a', '1');
        $this->handler->set('c', '3');

        $result = $this->handler->getMulti(['a', 'b', 'c']);
        self::assertSame(['a' => '1', 'c' => '3'], $result);
    }

    public function testGetMultiEmpty(): void
    {
        self::assertSame([], $this->handler->getMulti([]));
    }

    public function testOverwrite(): void
    {
        $this->handler->set('key1', 'first');
        $this->handler->set('key1', 'second');
        self::assertSame('second', $this->handler->get('key1'));
    }

    public function testNumericValues(): void
    {
        $this->handler->set('version', '1748000000000.1');
        self::assertSame('1748000000000.1', $this->handler->get('version'));
    }
}
