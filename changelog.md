# Static Cache Middleware Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.0.1 - 2021-10-29
### Fixed

- Fixed a bug where the redis driver clear all static cache would not, in fact, clear any of the static cache

## 1.0.0 - 2021-10-19
### Added

- This is the initial release of Static Cache Middleware.
- Static Cache Middleware should (probably) be the first middleware run in your stack. It caches the entire response if the StaticCache header is present in the response then serves that cached response if it exists on the next request.
