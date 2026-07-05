<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Http;

use Saloon\Http\Connector;

final class SkirConnector extends Connector
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
}
