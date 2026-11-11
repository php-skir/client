<?php

declare(strict_types=1);

namespace Skir\Client\Exceptions;

use RuntimeException;
use Saloon\Http\Response;
use Skir\Runtime\MethodDescriptor;
use Throwable;

final class SkirClientException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly ?MethodDescriptor $method = null,
        public readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function failedResponse(MethodDescriptor $method, Response $response): self
    {
        return new self(
            "Skir RPC request failed with status {$response->status()}.",
            method: $method,
            response: $response,
        );
    }

    public static function invalidResponse(MethodDescriptor $method, Throwable $previous): self
    {
        return new self(
            "Skir RPC response for [{$method->name}] could not be decoded.",
            method: $method,
            previous: $previous,
        );
    }

    public static function missingBaseUrl(string $configKey = 'skir-client'): self
    {
        return new self("Set [{$configKey}.base_url] (SKIR_CLIENT_BASE_URL) to the SkirRPC server URL.");
    }

    public static function unsupportedAuthType(string $type): self
    {
        return new self("Skir client auth type [{$type}] is not supported. Use [header], [bearer], or [basic].");
    }

    public static function missingAuthCredential(string $configKey, string $env): self
    {
        return new self("Set [{$configKey}] ({$env}) to use the configured Skir client authentication.");
    }

    public static function unsupportedFormat(string $format): self
    {
        return new self("Skir client format [{$format}] is not supported. Use [dense] or [readable].");
    }
}
