<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\UriHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/** @psalm-suppress PropertyNotSetInConstructor */
class UriCacheInfoFromRequestFactoryTest extends TestCase
{
    public function testMakeVariation1(): void
    {
        $uriStub = $this->createMock(UriInterface::class);

        $uriStub->method('getPath')->willReturn('/test/path/');

        $requestStub = $this->createMock(
            ServerRequestInterface::class,
        );

        $requestStub->method('getUri')->willReturn(
            $uriStub,
        );

        $factory = new UriCacheInfoFromRequestFactory();

        $cacheInfo = $factory->make(request: $requestStub);

        self::assertSame(
            '/static-page-cache/test/path',
            $cacheInfo->uriCacheDirectory(),
        );

        self::assertSame(
            '/static-page-cache/test/path/index.cache',
            $cacheInfo->uriCachePath(),
        );
    }

    public function testMakeVariation2(): void
    {
        $uriStub = $this->createMock(UriInterface::class);

        $uriStub->method('getPath')->willReturn('test/path');

        $requestStub = $this->createMock(
            ServerRequestInterface::class,
        );

        $requestStub->method('getUri')->willReturn(
            $uriStub,
        );

        $factory = new UriCacheInfoFromRequestFactory();

        $cacheInfo = $factory->make(request: $requestStub);

        self::assertSame(
            '/static-page-cache/test/path',
            $cacheInfo->uriCacheDirectory(),
        );

        self::assertSame(
            '/static-page-cache/test/path/index.cache',
            $cacheInfo->uriCachePath(),
        );
    }

    public function testMakeVariation3(): void
    {
        $uriStub = $this->createMock(UriInterface::class);

        $uriStub->method('getPath')->willReturn('/');

        $requestStub = $this->createMock(
            ServerRequestInterface::class,
        );

        $requestStub->method('getUri')->willReturn(
            $uriStub,
        );

        $factory = new UriCacheInfoFromRequestFactory();

        $cacheInfo = $factory->make(request: $requestStub);

        self::assertSame(
            '/static-page-cache',
            $cacheInfo->uriCacheDirectory(),
        );

        self::assertSame(
            '/static-page-cache/index.cache',
            $cacheInfo->uriCachePath(),
        );
    }

    public function testMakeVariation4(): void
    {
        $uriStub = $this->createMock(UriInterface::class);

        $uriStub->method('getPath')->willReturn('');

        $requestStub = $this->createMock(
            ServerRequestInterface::class,
        );

        $requestStub->method('getUri')->willReturn(
            $uriStub,
        );

        $factory = new UriCacheInfoFromRequestFactory();

        $cacheInfo = $factory->make(request: $requestStub);

        self::assertSame(
            '/static-page-cache',
            $cacheInfo->uriCacheDirectory(),
        );

        self::assertSame(
            '/static-page-cache/index.cache',
            $cacheInfo->uriCachePath(),
        );
    }
}
