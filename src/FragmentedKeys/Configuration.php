<?php

declare(strict_types=1);

namespace NoizuLabs\FragmentedKeys;

final class Configuration
{
    private static ?CacheHandlerInterface $defaultCacheHandler = null;
    private static string $globalPrefix = 'DefaultPrefix';

    public static function setDefaultCacheHandler(CacheHandlerInterface $handler): void
    {
        self::$defaultCacheHandler = $handler;
    }

    public static function getDefaultCacheHandler(): CacheHandlerInterface
    {
        if (self::$defaultCacheHandler === null) {
            throw new \RuntimeException('Default cache handler has not been configured. Call Configuration::setDefaultCacheHandler() first.');
        }
        return self::$defaultCacheHandler;
    }

    public static function setGlobalPrefix(string $prefix): void
    {
        self::$globalPrefix = $prefix;
    }

    public static function getGlobalPrefix(): string
    {
        return self::$globalPrefix;
    }

    public static function reset(): void
    {
        self::$defaultCacheHandler = null;
        self::$globalPrefix = 'DefaultPrefix';
    }
}
