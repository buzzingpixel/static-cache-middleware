<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache;

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @psalm-suppress PropertyNotSetInConstructor */
class StaticCacheMiddlewareTest extends TestCase
{
    /**
     * @var ResponseInterface&MockObject
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    private ResponseInterface $responseStub;

    private CacheApiContract $cacheApiStub;

    /** @var mixed[] */
    private array $cacheApiCalls = [];

    private bool $cacheApiExistReturn = false;

    private ServerRequestInterface $requestStub;

    private string $requestStubMethodReturn = 'PUT';

    private RequestHandlerInterface $handlerStub;

    /** @var mixed[] */
    private array $handlerCalls = [];

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheApiCalls = [];

        $this->cacheApiExistReturn = false;

        $this->requestStubMethodReturn = 'PUT';

        $this->handlerCalls = [];

        $this->responseStub = $this->createMock(
            ResponseInterface::class,
        );

        $this->cacheApiStub = $this->createMock(
            CacheApiContract::class
        );

        $this->cacheApiStub->method('doesCacheExistForRequest')
            ->willReturnCallback(
                function (ServerRequestInterface $request): bool {
                    $this->cacheApiCalls[] = [
                        'method' => 'doesCacheExistForRequest',
                        'request' => $request,
                    ];

                    return $this->cacheApiExistReturn;
                }
            );

        $this->cacheApiStub->method('createResponseFromCache')
            ->willReturnCallback(
                function (
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    $this->cacheApiCalls[] = [
                        'method' => 'createResponseFromCache',
                        'request' => $request,
                    ];

                    return $this->responseStub;
                }
            );

        $this->cacheApiStub->method('createCacheFromResponse')
            ->willReturnCallback(
                function (
                    ResponseInterface $response,
                    ServerRequestInterface $request,
                ): void {
                    $this->cacheApiCalls[] = [
                        'method' => 'createResponseFromCache',
                        'response' => $response,
                        'request' => $request,
                    ];
                }
            );

        $this->requestStub = $this->createMock(
            ServerRequestInterface::class,
        );

        $this->requestStub->method('getMethod')
            ->willReturnCallback(function (): string {
                return $this->requestStubMethodReturn;
            });

        $this->handlerStub = $this->createMock(
            RequestHandlerInterface::class,
        );

        $this->handlerStub->method('handle')->willReturnCallback(
            function (
                ServerRequestInterface $request,
            ): ResponseInterface {
                $this->handlerCalls[] = [
                    'method' => 'handle',
                    'request' => $request,
                ];

                return $this->responseStub;
            }
        );
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testProcessWhenDisabled(): void
    {
        $middleware = new StaticCacheMiddleware(
            enabled: false,
            cacheApi: $this->cacheApiStub,
        );

        $response = $middleware->process(
            $this->requestStub,
            $this->handlerStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(0, $this->cacheApiCalls);

        self::assertCount(1, $this->handlerCalls);

        self::assertSame(
            'handle',
            $this->handlerCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->handlerCalls[0]['request'],
        );
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testProcessWhenNotGetOrHead(): void
    {
        $middleware = new StaticCacheMiddleware(
            enabled: true,
            cacheApi: $this->cacheApiStub,
        );

        $response = $middleware->process(
            $this->requestStub,
            $this->handlerStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(0, $this->cacheApiCalls);

        self::assertCount(1, $this->handlerCalls);

        self::assertSame(
            'handle',
            $this->handlerCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->handlerCalls[0]['request'],
        );
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testProcessWhenCached(): void
    {
        $this->requestStubMethodReturn = 'HEAD';

        $this->cacheApiExistReturn = true;

        $middleware = new StaticCacheMiddleware(
            enabled: true,
            cacheApi: $this->cacheApiStub,
        );

        $response = $middleware->process(
            $this->requestStub,
            $this->handlerStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(2, $this->cacheApiCalls);

        self::assertSame(
            'doesCacheExistForRequest',
            $this->cacheApiCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[0]['request'],
        );

        self::assertSame(
            'createResponseFromCache',
            $this->cacheApiCalls[1]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[1]['request'],
        );

        self::assertCount(0, $this->handlerCalls);
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testProcessWhenNoHeader(): void
    {
        $this->requestStubMethodReturn = 'GET';

        $this->cacheApiExistReturn = false;

        $middleware = new StaticCacheMiddleware(
            enabled: true,
            cacheApi: $this->cacheApiStub,
        );

        $response = $middleware->process(
            $this->requestStub,
            $this->handlerStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(1, $this->cacheApiCalls);

        self::assertSame(
            'doesCacheExistForRequest',
            $this->cacheApiCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[0]['request'],
        );

        self::assertCount(1, $this->handlerCalls);

        self::assertSame(
            'handle',
            $this->handlerCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->handlerCalls[0]['request'],
        );
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testProcessFinal(): void
    {
        $this->responseStub->method('getHeader')
            ->with(self::equalTo('EnableStaticCache'))
            ->willReturn(['true']);

        $this->requestStubMethodReturn = 'GET';

        $this->cacheApiExistReturn = false;

        $middleware = new StaticCacheMiddleware(
            enabled: true,
            cacheApi: $this->cacheApiStub,
        );

        $response = $middleware->process(
            $this->requestStub,
            $this->handlerStub,
        );

        self::assertSame(
            $this->responseStub,
            $response,
        );

        self::assertCount(3, $this->cacheApiCalls);

        self::assertSame(
            'doesCacheExistForRequest',
            $this->cacheApiCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[0]['request'],
        );

        self::assertSame(
            'createResponseFromCache',
            $this->cacheApiCalls[1]['method'],
        );

        self::assertSame(
            $this->responseStub,
            $this->cacheApiCalls[1]['response'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[1]['request'],
        );

        self::assertSame(
            'createResponseFromCache',
            $this->cacheApiCalls[2]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->cacheApiCalls[2]['request'],
        );

        self::assertCount(1, $this->handlerCalls);

        self::assertSame(
            'handle',
            $this->handlerCalls[0]['method'],
        );

        self::assertSame(
            $this->requestStub,
            $this->handlerCalls[0]['request'],
        );
    }
}
