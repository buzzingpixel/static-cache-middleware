<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache;

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function mb_strtolower;

class StaticCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private bool $enabled,
        private CacheApiContract $cacheApi,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (
            ! $this->enabled ||
            ! in_array(
                mb_strtolower($request->getMethod()),
                [
                    'get',
                    'head',
                ],
                true,
            )
        ) {
            return $handler->handle($request);
        }

        $cached = $this->cacheApi->doesCacheExistForRequest(
            request: $request,
        );

        return $cached ?
            $this->cacheApi->createResponseFromCache(request: $request) :
            $this->handle(request: $request, handler: $handler);
    }

    private function handle(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        $enableStaticCache = $response->getHeader(
            'EnableStaticCache'
        )[0] ?? false;

        if ($enableStaticCache !== 'true') {
            return $response;
        }

        $this->cacheApi->createCacheFromResponse(
            response: $response,
            request: $request,
        );

        return $this->cacheApi->createResponseFromCache(
            request: $request,
        );
    }
}
