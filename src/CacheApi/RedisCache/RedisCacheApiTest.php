<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi\RedisCache;

use BuzzingPixel\StaticCache\CacheApi\CacheItem;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfo;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfoFromRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Redis;

use function assert;
use function serialize;
use function unserialize;

/** @psalm-suppress PropertyNotSetInConstructor */
class RedisCacheApiTest extends TestCase
{
    private Redis $redisStub;

    /** @var mixed[] */
    private array $redisCalls = [];

    private bool $redisExistsReturn = false;

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

    /**
     * @psalm-suppress MixedArrayAssignment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->redisExistsReturn = false;

        $this->redisStub = $this->createMock(Redis::class);

        $this->redisStub->method('set')->willReturnCallback(
            function (string $key, string $value): bool {
                $this->redisCalls[] = [
                    'method' => 'set',
                    'key' => $key,
                    'value' => $value,
                ];

                return true;
            }
        );

        $this->redisStub->method('exists')->willReturnCallback(
            function (string $key): bool {
                $this->redisCalls[] = [
                    'method' => 'exists',
                    'key' => $key,
                ];

                return $this->redisExistsReturn;
            }
        );

        $this->redisStub->method('get')->willReturnCallback(
            function (string $key): string {
                $this->redisCalls[] = [
                    'method' => 'get',
                    'key' => $key,
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

        $this->redisStub->method('del')->willReturnCallback(
            function (string $key1): int {
                $this->redisCalls[] = [
                    'method' => 'del',
                    'key1' => $key1,
                ];

                return 123;
            }
        );

        $this->redisStub->method('keys')->willReturnCallback(
            function (string $pattern): array {
                $this->redisCalls[] = [
                    'method' => 'keys',
                    'pattern' => $pattern,
                ];

                return [
                    'key1',
                    'key2',
                    'foo-bar/static-page-cache/key-1',
                    '/static-page-cache/key-1',
                    '/static-page-cache/key-2',
                ];
            }
        );

        $this->responseFactoryCalls = [];

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

        $this->uriCacheInfoFromRequestFactoryCalls = [];

        $this->uriCacheInfoFromRequestFactoryStub = $this->createMock(
            UriCacheInfoFromRequestFactory::class,
        );

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
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testCreateCacheFromResponse(): void
    {
        $cacheApi = new RedisCacheApi(
            redis: $this->redisStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $cacheApi->createCacheFromResponse(
            $this->responseStub,
            $this->requestStub,
        );

        self::assertCount(
            1,
            $this->redisCalls,
        );

        self::assertSame(
            'set',
            $this->redisCalls[0]['method'],
        );

        self::assertSame(
            'testCachePath',
            $this->redisCalls[0]['key'],
        );

        $cacheItem = unserialize($this->redisCalls[0]['value']);

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
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    public function testDoesCacheExistForRequestWhenTrue(): void
    {
        $this->redisExistsReturn = true;

        $cacheApi = new RedisCacheApi(
            redis: $this->redisStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        self::assertTrue($cacheApi->doesCacheExistForRequest(
            $this->requestStub,
        ));

        self::assertCount(1, $this->redisCalls);

        self::assertSame(
            'exists',
            $this->redisCalls[0]['method'],
        );

        self::assertSame(
            'testCachePath',
            $this->redisCalls[0]['key'],
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
        $this->redisExistsReturn = false;

        $cacheApi = new RedisCacheApi(
            redis: $this->redisStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        self::assertFalse($cacheApi->doesCacheExistForRequest(
            $this->requestStub,
        ));

        self::assertCount(1, $this->redisCalls);

        self::assertSame(
            'exists',
            $this->redisCalls[0]['method'],
        );

        self::assertSame(
            'testCachePath',
            $this->redisCalls[0]['key'],
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
    public function testCreateResponseFromCache(): void
    {
        $cacheApi = new RedisCacheApi(
            redis: $this->redisStub,
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
            $this->redisCalls,
        );

        self::assertSame(
            'testCachePath',
            $this->redisCalls[0]['key'],
        );

        self::assertSame(
            'get',
            $this->redisCalls[0]['method'],
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
        $cacheApi = new RedisCacheApi(
            redis: $this->redisStub,
            responseFactory: $this->responseFactoryStub,
            uriCacheInfoFromRequestFactory: $this->uriCacheInfoFromRequestFactoryStub,
        );

        $cacheApi->clearAllCache();

        self::assertSame(
            [
                [
                    'method' => 'keys',
                    'pattern' => '*',
                ],
                [
                    'method' => 'del',
                    'key1' => '/static-page-cache/key-1',
                ],
                [
                    'method' => 'del',
                    'key1' => '/static-page-cache/key-2',
                ],
            ],
            $this->redisCalls
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
