<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi\RedisCache;

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use BuzzingPixel\StaticCache\CacheApi\CacheItem;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfoFromRequestFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Redis;

use function array_filter;
use function array_map;
use function assert;
use function mb_strpos;
use function serialize;
use function unserialize;

class RedisCacheApi implements CacheApiContract
{
    public function __construct(
        private Redis $redis,
        private ResponseFactoryInterface $responseFactory,
        private UriCacheInfoFromRequestFactory $uriCacheInfoFromRequestFactory,
    ) {
    }

    public function createCacheFromResponse(
        ResponseInterface $response,
        ServerRequestInterface $request,
    ): void {
        $uriCacheInfo = $this->uriCacheInfoFromRequestFactory->make(
            request: $request,
        );

        $this->redis->set(
            $uriCacheInfo->uriCachePath(),
            serialize(new CacheItem(
                body: (string) $response->getBody(),
                headers: $response->getHeaders(),
                statusCode: $response->getStatusCode(),
                reasonPhrase: $response->getReasonPhrase(),
                protocolVersion: $response->getProtocolVersion(),
            )),
        );
    }

    public function doesCacheExistForRequest(
        ServerRequestInterface $request,
    ): bool {
        $uriCacheInfo = $this->uriCacheInfoFromRequestFactory->make(
            request: $request,
        );

        /** @psalm-suppress MixedAssignment */
        $exists = $this->redis->exists($uriCacheInfo->uriCachePath());

        /**
         * @phpstan-ignore-next-line
         * @psalm-suppress TypeDoesNotContainType
         */
        return $exists === true || $exists > 0;
    }

    public function createResponseFromCache(
        ServerRequestInterface $request,
    ): ResponseInterface {
        $uriCacheInfo = $this->uriCacheInfoFromRequestFactory->make(
            request: $request,
        );

        /** @psalm-suppress MixedAssignment */
        $content = $this->redis->get($uriCacheInfo->uriCachePath());

        $cacheItem = unserialize((string) $content);

        assert($cacheItem instanceof CacheItem);

        $response = $this->responseFactory->createResponse(
            $cacheItem->statusCode(),
            $cacheItem->reasonPhrase(),
        )->withProtocolVersion($cacheItem->protocolVersion());

        /** @psalm-suppress MixedAssignment */
        foreach ($cacheItem->headers() as $key => $val) {
            foreach ($val as $headerVal) {
                /**
                 * @psalm-suppress MixedArgument
                 * @psalm-suppress MixedArgumentTypeCoercion
                 */
                $response = $response->withHeader(
                    $key,
                    $headerVal,
                );
            }
        }

        $response->getBody()->write($cacheItem->body());

        return $response;
    }

    public function clearAllCache(): void
    {
        array_map(
            [$this->redis, 'del'],
            array_filter(
                $this->redis->keys('*'),
                static function ($key): bool {
                    return mb_strpos($key, '/static-page-cache/') === 0;
                }
            ),
        );
    }
}
