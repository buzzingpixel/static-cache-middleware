<?php

declare(strict_types=1);

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use BuzzingPixel\StaticCache\CacheApi\FileCache\FileCacheApiForFlysystem1;
use BuzzingPixel\StaticCache\StaticCacheMiddleware;
use BuzzingPixel\StaticCache\UriHandler\UriCacheInfoFromRequestFactory;
use DI\ContainerBuilder;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

use function DI\autowire;

$containerBuilder = (new ContainerBuilder())
    ->useAnnotations(true)
    ->useAutowiring(true)
    ->ignorePhpDocErrors(true)
    ->addDefinitions([
        FileCacheApiForFlysystem1::class => static function (
            ContainerInterface $di,
        ): FileCacheApiForFlysystem1 {
            /** @psalm-suppress MixedArgument */
            return new FileCacheApiForFlysystem1(
                filesystem: new Filesystem(
                    new Local('/path/to/desired/location'),
                ),
                responseFactory: $di->get(ResponseFactoryInterface::class),
                uriCacheInfoFromRequestFactory: $di->get(
                    UriCacheInfoFromRequestFactory::class,
                ),
            );
        },
        CacheApiContract::class => autowire(
            FileCacheApiForFlysystem1::class,
        ),
        StaticCacheMiddleware::class => autowire(
            StaticCacheMiddleware::class
        )->constructorParameter(
            'enabled',
            (bool) getenv('STATIC_CACHE_ENABLED'),
        ),
    ]);

/** @noinspection PhpUnhandledExceptionInspection */
return $containerBuilder->build();
