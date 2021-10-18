<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi;

use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

/** @psalm-suppress PropertyNotSetInConstructor */
class CacheItemTest extends TestCase
{
    public function testCacheItem(): void
    {
        $cacheItem = unserialize(serialize(new CacheItem(
            body: 'testBody',
            headers: ['test', 'headers'],
            statusCode: 123,
            reasonPhrase: 'testReasonPhrase',
            protocolVersion: '456',
        )));

        assert($cacheItem instanceof CacheItem);

        self::assertSame(
            'testBody',
            $cacheItem->body(),
        );

        self::assertSame(
            ['test', 'headers'],
            $cacheItem->headers(),
        );

        self::assertSame(
            123,
            $cacheItem->statusCode(),
        );

        self::assertSame(
            'testReasonPhrase',
            $cacheItem->reasonPhrase(),
        );

        self::assertSame(
            '456',
            $cacheItem->protocolVersion(),
        );
    }
}
