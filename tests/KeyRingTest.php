<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Key\StandardKey;
use NoizuLabs\FragmentedKeys\KeyRing;
use NoizuLabs\FragmentedKeys\Tag\ConstantTag;
use NoizuLabs\FragmentedKeys\Tag\DelayedTag;
use NoizuLabs\FragmentedKeys\Tag\StandardTag;
use PHPUnit\Framework\TestCase;

final class KeyRingTest extends TestCase
{
    private MemoryHandler $handler;
    private KeyRing $ring;

    protected function setUp(): void
    {
        Configuration::reset();
        $this->handler = new MemoryHandler();
        Configuration::setDefaultCacheHandler($this->handler);

        $this->ring = new KeyRing(
            globalOptions: [],
            globalTagOptions: ['universe' => ['type' => 'constant', 'version' => 1]],
            defaultCacheHandler: 'memory',
            cacheHandlers: ['memory' => $this->handler],
        );
    }

    public function testDefineAndGetKeyObj(): void
    {
        $this->ring->defineKey('Users', ['universe', 'planet', 'city']);
        $key = $this->ring->getKeyObj('Users', ['v1', 'earth', 'chicago']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key->getKeyStr());
    }

    public function testKeyObjIsStable(): void
    {
        $this->ring->defineKey('Users', ['universe', 'city']);
        $k1 = $this->ring->getKeyObj('Users', ['v1', 'chicago'])->getKeyStr();
        $k2 = $this->ring->getKeyObj('Users', ['v1', 'chicago'])->getKeyStr();
        self::assertSame($k1, $k2);
    }

    public function testConstantTagViaOptions(): void
    {
        $this->ring->defineKey('Users', ['universe', 'city']);
        $key = $this->ring->getKeyObj('Users', ['v1', 'chicago']);
        $raw = $key->getKeyStr(false);
        self::assertStringContainsString('universe_v1', $raw);
        self::assertStringContainsString(':v1', $raw);
    }

    public function testMagicCallAccessor(): void
    {
        $this->ring->defineKey('Users', ['universe', 'city']);
        $key = $this->ring->getUsersKeyObj('v1', 'chicago');
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key->getKeyStr());
    }

    public function testMagicCallMatchesGetKeyObj(): void
    {
        $this->ring->defineKey('Users', ['universe', 'city']);
        $k1 = $this->ring->getKeyObj('Users', ['v1', 'chicago'])->getKeyStr();
        $k2 = $this->ring->getUsersKeyObj('v1', 'chicago')->getKeyStr();
        self::assertSame($k1, $k2);
    }

    public function testWrongTagCountThrows(): void
    {
        $this->ring->defineKey('Users', ['universe', 'city']);
        $this->expectException(\InvalidArgumentException::class);
        $this->ring->getKeyObj('Users', ['v1']);
    }

    public function testUndefinedKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->ring->getKeyObj('NonExistent', ['v1']);
    }

    public function testBadMagicCallThrows(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->ring->someRandomMethod();
    }

    public function testPerParamOverrides(): void
    {
        $this->ring->defineKey('Mixed', [
            'city',
            ['tag' => 'schema', 'type' => 'constant', 'version' => 5],
        ]);
        $key = $this->ring->getKeyObj('Mixed', ['chicago', 'v5']);
        $raw = $key->getKeyStr(false);
        self::assertStringContainsString(':v5', $raw);
    }

    public function testTagFactory(): void
    {
        $tag = $this->ring->tag('universe', 'v1');
        self::assertInstanceOf(ConstantTag::class, $tag);
        self::assertSame(1, $tag->getTagVersion());
    }

    public function testTagFactoryStandard(): void
    {
        $tag = $this->ring->tag('city', 'chicago');
        self::assertInstanceOf(StandardTag::class, $tag);
    }

    public function testTagFactoryDelayed(): void
    {
        $tag = $this->ring->tag('session', 'abc', ['type' => 'delayed']);
        self::assertInstanceOf(DelayedTag::class, $tag);
    }

    public function testDelayedTagHonorsDelaySecondsOption(): void
    {
        // A newer version written just now stays hidden behind the grace window.
        $this->handler->set('session_abcDefaultPrefix', '5');
        $this->handler->set('session_abcDefaultPrefix:updated_at', (string) microtime(true));

        $tag = $this->ring->tag('session', 'abc', ['type' => 'delayed', 'delay_seconds' => 60.0]);
        self::assertSame(4, $tag->getTagVersion());
    }

    public function testKeyRingMatchesManualConstruction(): void
    {
        $this->ring->defineKey('Profile', [
            ['tag' => 'schema', 'type' => 'constant', 'version' => 2],
            'user',
        ]);
        $ringKey = $this->ring->getKeyObj('Profile', ['v2', '42']);

        $manualTag1 = new ConstantTag('schema', 'v2', 2, $this->handler);
        $manualTag2 = new StandardTag('user', '42', handler: $this->handler);
        $manualKey = new StandardKey('Profile', [$manualTag1, $manualTag2]);

        self::assertSame($manualKey->getKeyStr(), $ringKey->getKeyStr());
    }

    public function testSetAndGetGlobalOptions(): void
    {
        $this->ring->setGlobalOptions(['type' => 'standard']);
        self::assertSame(['type' => 'standard'], $this->ring->getGlobalOptions());
    }

    public function testSetAndGetTagOptions(): void
    {
        $this->ring->setTagOptions('city', ['prefix' => 'Geo']);
        self::assertSame(['prefix' => 'Geo'], $this->ring->getTagOptions('city'));
    }
}
