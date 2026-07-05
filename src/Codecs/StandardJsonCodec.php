<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

use JsonException;
use LaravelSkir\Runtime\Exceptions\SkirRuntimeException;
use LaravelSkir\Runtime\MethodDescriptor;

final class StandardJsonCodec implements SkirClientCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): mixed
    {
        return $request;
    }

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed
    {
        try {
            return json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw SkirRuntimeException::invalidValue("Skir standard JSON response is invalid: {$exception->getMessage()}");
        }
    }
}
