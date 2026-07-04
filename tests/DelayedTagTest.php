<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys\Tests;

use NoizuLabs\FragmentedKeys\CacheHandler\MemoryHandler;
use NoizuLabs\FragmentedKeys\Configuration;
use NoizuLabs\FragmentedKeys\Tag\DelayedTag;
use PHPUnit\Framework\TestCase;

final class DelayedTagTest extends TestCase
{
    private const string TAG_NAME = 'User_42DefaultPrefix';
    private const string UPDATED_AT = 'User_42DefaultPrefix:updated_at';

    private MemoryHandler $handler;

    protected function setUp(): void
    {
        Configuration::reset();
        $this->handler = new MemoryHandler();
        Configuration::setDefaultCacheHandler($this->handler);
    }

    public function testTagNameFormat(): void
    {
        $tag = new DelayedTag('User', '42');
        self::assertSame(self::TAG_NAME, $tag->getTagName());
    }

    public function testWithinGraceWindowServesPreviousVersion(): void
    {
        // A newer version exists but was written moments ago; readers should keep
        // serving the previous version until the delay window elapses.
        $this->handler->set(self::TAG_NAME, '5');
        $this->handler->set(self::UPDATED_AT, (string) microtime(true));

        $tag = new DelayedTag('User', '42', delaySeconds: 60.0);
        self::assertSame(4, $tag->getTagVersion());
    }

    public function testAfterGraceWindowServesCurrentVersion(): void
    {
        $this->handler->set(self::TAG_NAME, '5');
        $this->handler->set(self::UPDATED_AT, (string) (microtime(true) - 100.0));

        $tag = new DelayedTag('User', '42', delaySeconds: 60.0);
        self::assertSame(5, $tag->getTagVersion());
    }

    public function testWithoutUpdatedAtMarkerServesStoredVersion(): void
    {
        // No updated_at marker means no active grace window.
        $this->handler->set(self::TAG_NAME, '5');

        $tag = new DelayedTag('User', '42', delaySeconds: 60.0);
        self::assertSame(5, $tag->getTagVersion());
    }

    public function testIncrementRecordsUpdatedAtAndBumpsStoredVersion(): void
    {
        $this->handler->set(self::TAG_NAME, '5');

        $tag = new DelayedTag('User', '42', delaySeconds: 60.0);
        $tag->increment();

        self::assertSame(6, (int) $this->handler->get(self::TAG_NAME));
        self::assertNotNull($this->handler->get(self::UPDATED_AT));

        // A fresh reader is now inside the grace window and still sees the old version.
        $reader = new DelayedTag('User', '42', delaySeconds: 60.0);
        self::assertSame(5, $reader->getTagVersion());
    }

    public function testResetClearsGraceAndChangesVersion(): void
    {
        $this->handler->set(self::TAG_NAME, '5');
        $this->handler->set(self::UPDATED_AT, (string) microtime(true));

        $tag = new DelayedTag('User', '42', delaySeconds: 60.0);
        $tag->resetTagVersion();

        // Reset writes a fresh high version and drops the pending grace state.
        self::assertGreaterThan(5, $tag->getTagVersion());
    }

    public function testGetFullTagIncludesVersion(): void
    {
        $tag = new DelayedTag('User', '42');
        self::assertStringContainsString(self::TAG_NAME . ':v', $tag->getFullTag());
    }

    public function testDelegateCacheQueryMatchesOwnHandler(): void
    {
        $tag = new DelayedTag('User', '42');
        self::assertTrue($tag->delegateCacheQuery('MemoryHandler'));
        self::assertFalse($tag->delegateCacheQuery('RedisHandler'));
    }
}
