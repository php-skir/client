<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Exceptions;

use LaravelSkir\Runtime\MethodDescriptor;
use RuntimeException;
use Saloon\Http\Response;
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

    public static function unsupportedCodec(string $codec): self
    {
        return new self("Skir client codec [{$codec}] is not supported.");
    }
}
