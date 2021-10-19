<?php

declare(strict_types=1);

use BuzzingPixel\StaticCache\CacheApi\CacheApiContract;
use BuzzingPixel\StaticCache\CacheApi\RedisCache\RedisCacheApi;
use BuzzingPixel\StaticCache\StaticCacheMiddleware;
use DI\ContainerBuilder;

use function DI\autowire;

$containerBuilder = (new ContainerBuilder())
    ->useAnnotations(true)
    ->useAutowiring(true)
    ->ignorePhpDocErrors(true)
    ->addDefinitions([
        Redis::class => static function (): Redis {
            $redis = new Redis();

            $redis->connect((string) getenv('REDIS_HOST'));

            // Do whatever else is needed to set up Redis

            return $redis;
        },
        CacheApiContract::class => autowire(RedisCacheApi::class),
        StaticCacheMiddleware::class => autowire(
            StaticCacheMiddleware::class
        )->constructorParameter(
            'enabled',
            (bool) getenv('STATIC_CACHE_ENABLED'),
        ),
    ]);

/** @noinspection PhpUnhandledExceptionInspection */
return $containerBuilder->build();
