<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class StandardKeyTest extends TestCase
{
    private MemoryHandler $handler;

    protected function setUp(): void
    {
        Configuration::reset();
        $this->handler = new MemoryHandler();
        Configuration::setDefaultCacheHandler($this->handler);
    }

    public function testKeyStrIsMd5(): void
    {
        $tag = new StandardTag('User', '42');
        $key = new StandardKey('Dashboard', [$tag]);
        $result = $key->getKeyStr();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function testRawKeyFormat(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 2);
        $key = new StandardKey('Profile', [$tag]);
        $raw = $key->getKeyStr(false);
        self::assertStringStartsWith('Profile_:tSchema_v1DefaultPrefix:v2', $raw);
    }

    public function testKeyIsStableWithoutIncrement(): void
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

    public function testConstantTagDoesNotChangeKey(): void
    {
        $constTag = new ConstantTag('Schema', 'v1', 1);
        $key = new StandardKey('Dashboard', [$constTag]);
        $before = $key->getKeyStr();
        $constTag->increment();
        $after = $key->getKeyStr();
        self::assertSame($before, $after);
    }

    public function testKeyWithMultipleTags(): void
    {
        $tag1 = new StandardTag('User', '42');
        $tag2 = new StandardTag('City', 'chicago');
        $key = new StandardKey('Dashboard', [$tag1, $tag2]);
        $result = $key->getKeyStr();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function testKeyWithSingleTag(): void
    {
        $tag = new StandardTag('User', '1');
        $key = new StandardKey('Simple', [$tag]);
        $result = $key->getKeyStr();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function testAddTag(): void
    {
        $tag1 = new StandardTag('User', '42');
        $key = new StandardKey('Dashboard', [$tag1]);
        $before = $key->getKeyStr();

        $tag2 = new StandardTag('City', 'chicago');
        $key->addTag($tag2);
        $after = $key->getKeyStr();
        self::assertNotSame($before, $after);
    }

    public function testGroupId(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 1);
        $key1 = new StandardKey('Dashboard', [$tag], 'admin');
        $key2 = new StandardKey('Dashboard', [$tag], 'user');
        self::assertNotSame($key1->getKeyStr(), $key2->getKeyStr());
    }

    public function testIncrementingOneTagDoesNotAffectKeyOfOther(): void
    {
        $tag1 = new StandardTag('User', '1');
        $tag2 = new StandardTag('User', '2');
        $key1 = new StandardKey('Profile', [$tag1]);
        $key2 = new StandardKey('Profile', [$tag2]);

        $k1Before = $key1->getKeyStr();
        $tag2->increment();
        $k1After = $key1->getKeyStr();
        self::assertSame($k1Before, $k1After);
    }
}
