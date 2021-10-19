<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\UriHandler;

class UriCacheInfo
{
    public function __construct(
        private string $uriCacheDirectory,
        private string $uriCachePath,
    ) {
    }

    public function uriCacheDirectory(): string
    {
        return $this->uriCacheDirectory;
    }

    public function uriCachePath(): string
    {
        return $this->uriCachePath;
    }
}
