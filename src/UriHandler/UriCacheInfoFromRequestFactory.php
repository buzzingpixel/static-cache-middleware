<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\UriHandler;

use Psr\Http\Message\ServerRequestInterface;

use function ltrim;
use function rtrim;

class UriCacheInfoFromRequestFactory
{
    public function make(ServerRequestInterface $request): UriCacheInfo
    {
        $uri = '/' . rtrim(
            ltrim(
                $request->getUri()->getPath(),
                '/',
            ),
            '/',
        );

        $directory = rtrim('/static-page-cache' . $uri, '/');

        return new UriCacheInfo(
            uriCacheDirectory: $directory,
            uriCachePath: $directory . '/index.cache',
        );
    }
}
