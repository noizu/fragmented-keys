<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use PHPUnit\Framework\TestCase;

final class MemoryHandlerTest extends TestCase
{
    private MemoryHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MemoryHandler();
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

    public function testGetMultiReturnsOnlyFoundKeys(): void
    {
        $this->handler->set('a', '1');
        $this->handler->set('c', '3');

        $result = $this->handler->getMulti(['a', 'b', 'c']);

        self::assertSame(['a' => '1', 'c' => '3'], $result);
    }

    public function testGroupName(): void
    {
        self::assertSame('MemoryHandler', $this->handler->groupName());
    }

    public function testClear(): void
    {
        $this->handler->set('key1', 'value1');
        $this->handler->clear();
        self::assertNull($this->handler->get('key1'));
    }

    public function testInstanceIsolation(): void
    {
        $handler2 = new MemoryHandler();
        $this->handler->set('key1', 'value1');
        self::assertNull($handler2->get('key1'));
    }
}
