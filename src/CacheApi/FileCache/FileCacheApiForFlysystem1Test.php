<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi\FileCache;

use BuzzingPixel\StaticCache\CacheApi\CacheItem;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfo;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfoFromRequestFactory;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function assert;
use function serialize;
use function unserialize;

/** @psalm-suppress PropertyNotSetInConstructor */
class FileCacheApiForFlysystem1Test extends TestCase
{
    private FilesystemInterface $filesystemStub;

    private bool $fileSystemHasReturn = false;

    /** @var mixed[] */
    private array $filesystemCalls = [];

    private ResponseFactoryInterface $responseFactoryStub;

    /** @var mixed[] */
    private array $responseFactoryCalls = [];

    private UriCacheInfoFromRequestFactory $uriCacheInfoFromRequestFactoryStub;

    /** @var mixed[] */
    private array $uriCacheInfoFromRequestFactoryCalls = [];

    private StreamInterface $responseBody;

    /** @var mixed[] */
    private array $responseBodyCalls = [];

    private ResponseInterface $responseStub;

    /** @var mixed[] */
    private array $responseCalls = [];

    private ServerRequestInterface $requestStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystemHasReturn = false;

        $this->filesystemStub = $this->createMock(
            FilesystemInterface::class,
        );

        $this->filesystemStub->method('deleteDir')
            ->willReturnCallback(
                function (string $dirname): bool {
                    $this->filesystemCalls[] = [
                        'dirname' => $dirname,
                        'method' => 'deleteDir',
                    ];

                    return $this->fileSystemHasReturn;
                }
            );

        $this->filesystemStub->method('has')->willReturnCallback(
            function (string $path): bool {
                $this->filesystemCalls[] = [
                    'path' => $path,
                    'method' => 'has',
                ];

                return $this->fileSystemHasReturn;
            }
        );

        $this->filesystemStub->method('createDir')->willReturnCallback(
            function (string $path): bool {
                $this->filesystemCalls[] = [
                    'path' => $path,
                    'method' => 'createDir',
                ];

                return $this->fileSystemHasReturn;
            }
        );

        $this->filesystemStub->method('write')->willReturnCallback(
            function (string $path, string $contents): bool {
                $this->filesystemCalls[] = [
                    'path' => $path,
                    'contents' => $contents,
                    'method' => 'write',
                ];

                return true;
            }
        );

        $this->filesystemStub->method('read')->willReturnCallback(
            function (string $path): string {
                $this->filesystemCalls[] = [
                    'path' => $path,
                    'method' => 'read',
                ];

                return serialize(new CacheItem(
                    body: 'testBody',
                    headers: [
                        'header1' => [
                            'test1',
                            'test2',
                        ],
                    ],
                    statusCode: 456,
                    reasonPhrase: 'testReason',
                    protocolVersion: '845',
                ));
            }
        );

        $this->responseFactoryStub = $this->createMock(
            ResponseFactoryInterface::class,
        );

        $this->responseFactoryStub->method('createResponse')
            ->willReturnCallback(
                function (
                    int $code = 200,
                    string $reasonPhrase = '',
                ): ResponseInterface {
                    $this->responseFactoryCalls[] = [
                        'method' => 'createResponse',
                        'code' => $code,
                        'reasonPhrase' => $reasonPhrase,
                    ];

                    return $this->responseStub;
                }
            );

        $this->uriCacheInfoFromRequestFactoryStub = $this->createMock(
            UriCacheInfoFromRequestFactory::class,
        );

        $this->uriCacheInfoFromRequestFactoryCalls = [];

        $this->uriCacheInfoFromRequestFactoryStub->method('make')
            ->willReturnCallback(
                function (
                    ServerRequestInterface $request
                ): UriCacheInfo {
                    $this->uriCacheInfoFromRequestFactoryCalls[] = [
                        'method' => 'make',
                        'request' => $request,
                    ];

                    return new UriCacheInfo(
                        uriCacheDirectory: 'testDirectory',
                        uriCachePath: 'testCachePath',
                    );
                }
            );

        $this->responseBodyCalls = [];

        $this->responseBody = $this->createMock(
            StreamInterface::class,
        );

        $this->responseBody->method('__toString')->willReturn(
            'testBody',
        );

        $this->responseBody->method('write')->willReturnCallback(
            function (string $string): int {
                $this->responseBodyCalls[] = [
                    'method' => 'write',
                    'string' => $string,
                ];

                return 945;
            }
        );

        $this->responseCalls = [];

        $this->responseStub = $this->createMock(
            ResponseInterface::class,
        );

        $this->responseStub->method('getBody')->willReturn(
            $this->responseBody,
        );

        $this->responseStub->method('withProtocolVersion')
            ->willReturnCallback(
                function (string $version): ResponseInterface {
                    $this->responseCalls[] = [
                        'method' => 'withProtocolVersion',
                        'version' => $version,
                    ];

                    return $this->responseStub;
                }
            );

        $this->responseStub->method('withHeader')
            ->willReturnCallback(
                function (string $name, string $value): ResponseInterface {
                    $this->responseCalls[] = [
                        'method' => 'withHeader',
                        'name' => $name,
                        'value' => $value,
                    ];

                    return $this->responseStub;
                }
            );

        $this->responseStub->method('getHeaders')->willReturn(
            ['test', 'header'],
        );

        $this->responseStub->method('getStatusCode')->willReturn(
            987,
        );

        $this->responseStub->method('getReasonPhrase')->willReturn(
            'testReasonPhrase',
        );

        $this->responseStub->method('getProtocolVersion')->willReturn(
            'testProtocolVersion',
        );

        $this->requestStub = $this->createMock(
            ServerRequestInterface::class,
        );
    }

    /**
     * @throws FileExistsException
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testCreateCacheFromResponseWhenFilesystemHasDir(): void
    {
        $this->fileSystemHasReturn = true;

        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $cacheApi->createCacheFromResponse(
            $this->responseStub,
            $this->requestStub,
        );

        self::assertCount(
            2,
            $this->filesystemCalls,
        );

        self::assertSame(
            'testDirectory',
            $this->filesystemCalls[0]['path']
        );

        self::assertSame(
            'has',
            $this->filesystemCalls[0]['method']
        );

        self::assertSame(
            'testCachePath',
            $this->filesystemCalls[1]['path']
        );

        self::assertSame(
            'write',
            $this->filesystemCalls[1]['method']
        );

        $cacheItem = unserialize($this->filesystemCalls[1]['contents']);

        assert($cacheItem instanceof CacheItem);

        self::assertSame(
            'testBody',
            $cacheItem->body(),
        );

        self::assertSame(
            ['test', 'header'],
            $cacheItem->headers(),
        );

        self::assertSame(
            987,
            $cacheItem->statusCode(),
        );

        self::assertSame(
            'testReasonPhrase',
            $cacheItem->reasonPhrase(),
        );

        self::assertSame(
            'testProtocolVersion',
            $cacheItem->protocolVersion(),
        );

        self::assertCount(
            0,
            $this->responseFactoryCalls,
        );

        self::assertCount(
            1,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertSame(
            'make',
            $this->uriCacheInfoFromRequestFactoryCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->uriCacheInfoFromRequestFactoryCalls[0]['request'],
        );

        self::assertCount(
            0,
            $this->responseBodyCalls,
        );

        self::assertCount(0, $this->responseCalls);
    }

    /**
     * @throws FileExistsException
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress MixedAssignment
     */
    public function testCreateCacheFromResponseWhenFilesystemDoesNotHaveDir(): void
    {
        $this->fileSystemHasReturn = false;

        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $cacheApi->createCacheFromResponse(
            $this->responseStub,
            $this->requestStub,
        );

        self::assertCount(
            3,
            $this->filesystemCalls,
        );

        self::assertSame(
            'testDirectory',
            $this->filesystemCalls[0]['path']
        );

        self::assertSame(
            'has',
            $this->filesystemCalls[0]['method']
        );

        self::assertSame(
            'testDirectory',
            $this->filesystemCalls[1]['path']
        );

        self::assertSame(
            'createDir',
            $this->filesystemCalls[1]['method']
        );

        self::assertSame(
            'testCachePath',
            $this->filesystemCalls[2]['path']
        );

        self::assertSame(
            'write',
            $this->filesystemCalls[2]['method']
        );

        $cacheItem = unserialize($this->filesystemCalls[2]['contents']);

        self::assertSame(
            'testBody',
            $cacheItem->body(),
        );

        self::assertSame(
            ['test', 'header'],
            $cacheItem->headers(),
        );

        self::assertSame(
            987,
            $cacheItem->statusCode(),
        );

        self::assertSame(
            'testReasonPhrase',
            $cacheItem->reasonPhrase(),
        );

        self::assertSame(
            'testProtocolVersion',
            $cacheItem->protocolVersion(),
        );

        self::assertCount(
            0,
            $this->responseFactoryCalls,
        );

        self::assertCount(
            1,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertSame(
            'make',
            $this->uriCacheInfoFromRequestFactoryCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->uriCacheInfoFromRequestFactoryCalls[0]['request'],
        );

        self::assertCount(
            0,
            $this->responseBodyCalls,
        );

        self::assertCount(0, $this->responseCalls);
    }

    /**
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testDoesCacheExistForRequestWhenTrue(): void
    {
        $this->fileSystemHasReturn = true;

        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        self::assertTrue($cacheApi->doesCacheExistForRequest(
            $this->requestStub,
        ));

        self::assertCount(1, $this->filesystemCalls);

        self::assertSame(
            'testCachePath',
            $this->filesystemCalls[0]['path'],
        );

        self::assertSame(
            'has',
            $this->filesystemCalls[0]['method'],
        );

        self::assertCount(
            0,
            $this->responseFactoryCalls,
        );

        self::assertCount(
            1,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertSame(
            'make',
            $this->uriCacheInfoFromRequestFactoryCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->uriCacheInfoFromRequestFactoryCalls[0]['request'],
        );

        self::assertCount(
            0,
            $this->responseBodyCalls,
        );

        self::assertCount(0, $this->responseCalls);
    }

    /**
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testDoesCacheExistForRequestWhenFalse(): void
    {
        $this->fileSystemHasReturn = false;

        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        self::assertFalse($cacheApi->doesCacheExistForRequest(
            $this->requestStub,
        ));

        self::assertCount(1, $this->filesystemCalls);

        self::assertSame(
            'testCachePath',
            $this->filesystemCalls[0]['path'],
        );

        self::assertSame(
            'has',
            $this->filesystemCalls[0]['method'],
        );

        self::assertCount(
            0,
            $this->responseFactoryCalls,
        );

        self::assertCount(
            1,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertSame(
            'make',
            $this->uriCacheInfoFromRequestFactoryCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->uriCacheInfoFromRequestFactoryCalls[0]['request'],
        );

        self::assertCount(
            0,
            $this->responseBodyCalls,
        );

        self::assertCount(0, $this->responseCalls);
    }

    /**
     * @throws FileNotFoundException
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testCreateResponseFromCache(): void
    {
        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $response = $cacheApi->createResponseFromCache(
            request: $this->requestStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(
            1,
            $this->filesystemCalls,
        );

        self::assertSame(
            'testCachePath',
            $this->filesystemCalls[0]['path'],
        );

        self::assertSame(
            'read',
            $this->filesystemCalls[0]['method'],
        );

        self::assertCount(
            1,
            $this->responseFactoryCalls,
        );

        self::assertSame(
            'createResponse',
            $this->responseFactoryCalls[0]['method']
        );

        self::assertSame(
            456,
            $this->responseFactoryCalls[0]['code']
        );

        self::assertSame(
            'testReason',
            $this->responseFactoryCalls[0]['reasonPhrase']
        );

        self::assertCount(
            1,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertSame(
            'make',
            $this->uriCacheInfoFromRequestFactoryCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->uriCacheInfoFromRequestFactoryCalls[0]['request'],
        );

        self::assertCount(
            1,
            $this->responseBodyCalls,
        );

        self::assertSame(
            'write',
            $this->responseBodyCalls[0]['method'],
        );

        self::assertSame(
            'testBody',
            $this->responseBodyCalls[0]['string'],
        );

        self::assertCount(
            3,
            $this->responseCalls,
        );

        self::assertSame(
            'withProtocolVersion',
            $this->responseCalls[0]['method'],
        );

        self::assertSame(
            '845',
            $this->responseCalls[0]['version'],
        );

        self::assertSame(
            'withHeader',
            $this->responseCalls[1]['method'],
        );

        self::assertSame(
            'header1',
            $this->responseCalls[1]['name'],
        );

        self::assertSame(
            'test1',
            $this->responseCalls[1]['value'],
        );

        self::assertSame(
            'withHeader',
            $this->responseCalls[2]['method'],
        );

        self::assertSame(
            'header1',
            $this->responseCalls[2]['name'],
        );

        self::assertSame(
            'test2',
            $this->responseCalls[2]['value'],
        );
    }

    /**
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testClearAllCache(): void
    {
        $cacheApi = new FileCacheApiForFlysystem1(
            filesystem: $this->filesystemStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $cacheApi->clearAllCache();

        self::assertCount(
            1,
            $this->filesystemCalls,
        );

        self::assertSame(
            '/static-page-cache',
            $this->filesystemCalls[0]['dirname'],
        );

        self::assertSame(
            'deleteDir',
            $this->filesystemCalls[0]['method'],
        );

        self::assertCount(
            0,
            $this->responseFactoryCalls,
        );

        self::assertCount(
            0,
            $this->uriCacheInfoFromRequestFactoryCalls,
        );

        self::assertCount(
            0,
            $this->responseBodyCalls,
        );

        self::assertCount(
            0,
            $this->responseCalls,
        );
    }
}
