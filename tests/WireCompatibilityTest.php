<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class WireCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        Configuration::setDefaultCacheHandler(new MemoryHandler());
    }

    public function testSingleConstantTagRawFormat(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 2);
        $key = new StandardKey('Profile', [$tag]);
        $raw = $key->getKeyStr(false);

        self::assertSame('Profile_:tSchema_v1DefaultPrefix:v2', $raw);
    }

    public function testSingleConstantTagMd5(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 2);
        $key = new StandardKey('Profile', [$tag]);

        $expected = md5('Profile_:tSchema_v1DefaultPrefix:v2');
        self::assertSame($expected, $key->getKeyStr());
    }

    public function testTwoTagsWithKnownVersions(): void
    {
        $tag1 = new ConstantTag('User', '42', 1000);
        $tag2 = new ConstantTag('City', 'chicago', 5);
        $key = new StandardKey('Dashboard', [$tag1, $tag2]);
        $raw = $key->getKeyStr(false);

        self::assertSame(
            'Dashboard_:tUser_42DefaultPrefix:v1000:tCity_chicagoDefaultPrefix:v5',
            $raw,
        );
    }

    public function testTwoTagsMd5(): void
    {
        $tag1 = new ConstantTag('User', '42', 1000);
        $tag2 = new ConstantTag('City', 'chicago', 5);
        $key = new StandardKey('Dashboard', [$tag1, $tag2]);

        $expected = md5('Dashboard_:tUser_42DefaultPrefix:v1000:tCity_chicagoDefaultPrefix:v5');
        self::assertSame($expected, $key->getKeyStr());
    }

    public function testGroupIdRawFormat(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 1);
        $key = new StandardKey('Dashboard', [$tag], 'admin');
        $raw = $key->getKeyStr(false);

        self::assertSame('Dashboard_admin:tSchema_v1DefaultPrefix:v1', $raw);
    }

    public function testGroupIdMd5(): void
    {
        $tag = new ConstantTag('Schema', 'v1', 1);
        $key = new StandardKey('Dashboard', [$tag], 'admin');

        $expected = md5('Dashboard_admin:tSchema_v1DefaultPrefix:v1');
        self::assertSame($expected, $key->getKeyStr());
    }

    public function testCustomPrefixRawFormat(): void
    {
        $tag = new ConstantTag('User', '42', 3, prefix: 'MyApp');
        $key = new StandardKey('Profile', [$tag]);
        $raw = $key->getKeyStr(false);

        self::assertSame('Profile_:tUser_42MyApp:v3', $raw);
    }

    public function testTagOrderMatters(): void
    {
        $tagA = new ConstantTag('User', '42', 1);
        $tagB = new ConstantTag('City', 'chicago', 2);

        $key1 = new StandardKey('Test', [$tagA, $tagB]);
        $key2 = new StandardKey('Test', [$tagB, $tagA]);

        self::assertNotSame($key1->getKeyStr(), $key2->getKeyStr());
    }

    public function testStandardTagWithPersistedVersion(): void
    {
        $tag = new StandardTag('User', '42');
        $tag->setTagVersion(1748000000000, persist: true);
        $key = new StandardKey('Profile', [$tag]);
        $raw = $key->getKeyStr(false);

        self::assertSame('Profile_:tUser_42DefaultPrefix:v1748000000000', $raw);
    }

    public function testEmptyInstanceTag(): void
    {
        $tag = new ConstantTag('Global', '', 1);
        $key = new StandardKey('App', [$tag]);
        $raw = $key->getKeyStr(false);

        self::assertSame('App_:tGlobal_DefaultPrefix:v1', $raw);
    }
}
