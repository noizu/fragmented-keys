<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests\Integration;

use NoizuLabs\FragmentedKeys\CacheHandler\MemcachedHandler;
use PHPUnit\Framework\TestCase;

final class MemcachedHandlerTest extends TestCase
{
    private MemcachedHandler $handler;
    private \Memcached $memcached;

    protected function setUp(): void
    {
        if (!extension_loaded('memcached')) {
            self::markTestSkipped('ext-memcached not available.');
        }

        $host = getenv('FK_MEMCACHED_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('FK_MEMCACHED_PORT') ?: 11212);

        $this->memcached = new \Memcached();
        $this->memcached->addServer($host, $port);

        $stats = $this->memcached->getStats();
        if ($stats === false || $stats === []) {
            self::markTestSkipped("Memcached not running on {$host}:{$port}.");
        }

        $this->memcached->flush();
        $this->handler = new MemcachedHandler($this->memcached);
    }

    protected function tearDown(): void
    {
        if (isset($this->memcached)) {
            $this->memcached->flush();
        }
    }

    public function testGroupName(): void
    {
        self::assertSame('MemcachedHandler', $this->handler->groupName());
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
