<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CacheApiContract
{
    public function createCacheFromResponse(
        ResponseInterface $response,
        ServerRequestInterface $request
    ): void;

    public function doesCacheExistForRequest(
        ServerRequestInterface $request
    ): bool;

    public function createResponseFromCache(
        ServerRequestInterface $request
    ): ResponseInterface;

    public function clearAllCache(): void;
}
