<?php

declare(strict_types=1);

namespace BuzzingPixel\StaticCache\CacheApi;

// phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification

class CacheItem
{
    /**
     * @param mixed[] $headers
     */
    public function __construct(
        private string $body,
        private array $headers = [],
        private int $statusCode = 200,
        private string $reasonPhrase = 'OK',
        private string $protocolVersion = '1.1',
    ) {
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return mixed[]
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function reasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function protocolVersion(): string
    {
        return $this->protocolVersion;
    }
}
