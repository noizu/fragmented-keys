<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests\Integration;

use NoizuLabs\FragmentedKeys\CacheHandler\APCuHandler;
use PHPUnit\Framework\TestCase;

final class APCuHandlerTest extends TestCase
{
    private APCuHandler $handler;

    protected function setUp(): void
    {
        if (!extension_loaded('apcu')) {
            self::markTestSkipped('ext-apcu not available.');
        }
        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped('APCu CLI mode not enabled (set apc.enable_cli=1).');
        }

        apcu_clear_cache();
        $this->handler = new APCuHandler();
    }

    protected function tearDown(): void
    {
        if (extension_loaded('apcu')) {
            apcu_clear_cache();
        }
    }

    public function testGroupName(): void
    {
        self::assertSame('APCuHandler', $this->handler->groupName());
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
