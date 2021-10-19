<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi\FileCache;

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use BuzzingPixel\StaticCache\CacheApi\CacheItem;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfoFromRequestFactory;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;
use function serialize;
use function unserialize;

class FileCacheApiForFlysystem1 implements CacheApiContract
{
    public function __construct(
        private FilesystemInterface $filesystem,
        private ResponseFactoryInterface $responseFactory,
        private UriCacheInfoFromRequestFactory $uriCacheInfoFromRequestFactory,
    ) {
    }

    /**
     * @throws FileExistsException
     */
    public function createCacheFromResponse(
        ResponseInterface $response,
        ServerRequestInterface $request,
    ): void {
        $uriCacheInfo = $this->uriCacheInfoFromRequestFactory->make(
            request: $request,
        );

        if (
            ! $this->filesystem->has(
                $uriCacheInfo->uriCacheDirectory()
            )
        ) {
            $this->filesystem->createDir(
                $uriCacheInfo->uriCacheDirectory(),
            );
        }

        $this->filesystem->write(
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

        return $this->filesystem->has($uriCacheInfo->uriCachePath());
    }

    /**
     * @throws FileNotFoundException
     */
    public function createResponseFromCache(
        ServerRequestInterface $request
    ): ResponseInterface {
        $uriCacheInfo = $this->uriCacheInfoFromRequestFactory->make(
            request: $request,
        );

        $content = $this->filesystem->read($uriCacheInfo->uriCachePath());

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
        $this->filesystem->deleteDir('/static-page-cache');
    }
}
