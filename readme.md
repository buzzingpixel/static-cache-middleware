# Static Cache Middleware Changelog

Very often, entire page responses can be cached the first time it is loaded and that cache can subsequently be served instead of building the page from the various PHP and Database components on the next request. While this sort of cache can be accomplished in a variety of ways, this package does it through a PSR middleware. There are currently also two drivers available: Redis and File. You can also implement the CacheApiContract to provide your own.

## Here's how to use it:

1. In your project, run `composer require buzzingpixel/static-cache-middleware`
2. Configure your DI to serve the StaticCacheMiddleware with the two constructor parameters of whether to enable the static cache at all (this is nice for passing in an env variable), and the driver you wish to use. See the [examples](/examples).
3. When crafting a response, set `EnableStaticCache` to string of `'true'`.

```php
$response = $this->responseFactory->createResponse()
    ->withHeader('EnableStaticCache', 'true');
```
