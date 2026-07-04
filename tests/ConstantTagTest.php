<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use PHPUnit\Framework\TestCase;

final class ConstantTagTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        Configuration::setDefaultCacheHandler(new MemoryHandler());
    }

    public function testDefaultVersion(): void
    {
        $tag = new ConstantTag('Schema', 'v1');
        self::assertSame(1, $tag->getTagVersion());
    }

    public function testCustomVersion(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 5);
        self::assertSame(5, $tag->getTagVersion());
    }

    public function testIncrementIsNoop(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 3);
        $tag->increment();
        self::assertSame(3, $tag->getTagVersion());
    }

    public function testResetIsNoop(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 3);
        $tag->resetTagVersion();
        self::assertSame(3, $tag->getTagVersion());
    }

    public function testSetVersionIsNoop(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 3);
        $tag->setTagVersion(999, true);
        self::assertSame(3, $tag->getTagVersion());
    }

    public function testDelegateCacheQueryAlwaysFalse(): void
    {
        $tag = new ConstantTag('Schema', 'v1');
        self::assertFalse($tag->delegateCacheQuery('MemoryHandler'));
        self::assertFalse($tag->delegateCacheQuery('RedisHandler'));
    }

    public function testFullTag(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 2);
        self::assertSame('Schema_v1DefaultPrefix:v2', $tag->getFullTag());
    }
}
