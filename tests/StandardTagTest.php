<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class StandardTagTest extends TestCase
{
    private MemoryHandler $handler;

    protected function setUp(): void
    {
        Configuration::reset();
        $this->handler = new MemoryHandler();
        Configuration::setDefaultCacheHandler($this->handler);
    }

    public function testTagNameFormat(): void
    {
        $tag = new StandardTag('User', '42');
        self::assertSame('User_42DefaultPrefix', $tag->getTagName());
    }

    public function testVersionIsStableWithoutIncrement(): void
    {
        $tag = new StandardTag('User', '42');
        $v1 = $tag->getTagVersion();
        $v2 = $tag->getTagVersion();
        self::assertSame($v1, $v2);
    }

    public function testIncrementChangesVersion(): void
    {
        // A fresh tag resets to a microsecond-scale integer version; increment
        // yields a strictly greater, distinct version.
        $tag = new StandardTag('User', '42');
        $before = $tag->getTagVersion();
        $tag->increment();
        $after = $tag->getTagVersion();
        self::assertNotSame($before, $after);
        self::assertGreaterThan($before, $after);
    }

    public function testIncrementAddsOne(): void
    {
        $tag = new StandardTag('User', '42', version: 1);
        $tag->increment();
        self::assertSame(2, $tag->getTagVersion());
    }

    public function testResetChangesVersion(): void
    {
        $tag = new StandardTag('User', '42');
        $before = $tag->getTagVersion();
        usleep(1000);
        $tag->resetTagVersion();
        $after = $tag->getTagVersion();
        self::assertNotSame($before, $after);
    }

    public function testDifferentEntitiesProduceDifferentTags(): void
    {
        $tag1 = new StandardTag('User', '1');
        $tag2 = new StandardTag('User', '2');
        self::assertNotSame($tag1->getTagName(), $tag2->getTagName());
    }

    public function testDifferentTagNamesProduceDifferentTags(): void
    {
        $tag1 = new StandardTag('User', '42');
        $tag2 = new StandardTag('City', '42');
        self::assertNotSame($tag1->getTagName(), $tag2->getTagName());
    }

    public function testIncrementIsolationAcrossEntities(): void
    {
        $tag1 = new StandardTag('User', '1');
        $tag2 = new StandardTag('User', '2');
        $v1Before = $tag1->getTagVersion();
        $tag2->increment();
        $v1After = $tag1->getTagVersion();
        self::assertSame($v1Before, $v1After);
    }

    public function testIncrementIsolationAcrossTagNames(): void
    {
        $tag1 = new StandardTag('User', '42');
        $tag2 = new StandardTag('City', '42');
        $v1Before = $tag1->getTagVersion();
        $tag2->increment();
        $v1After = $tag1->getTagVersion();
        self::assertSame($v1Before, $v1After);
    }

    public function testDelegateCacheQueryMatchesOwnHandler(): void
    {
        $tag = new StandardTag('User', '42');
        self::assertTrue($tag->delegateCacheQuery('MemoryHandler'));
        self::assertFalse($tag->delegateCacheQuery('RedisHandler'));
    }

    public function testCustomPrefix(): void
    {
        $tag = new StandardTag('User', '42', prefix: 'MyApp');
        self::assertSame('User_42MyApp', $tag->getTagName());
    }

    public function testGetFullTagIncludesVersion(): void
    {
        $tag = new StandardTag('User', '42');
        $full = $tag->getFullTag();
        self::assertStringContainsString('User_42DefaultPrefix:v', $full);
    }
}
