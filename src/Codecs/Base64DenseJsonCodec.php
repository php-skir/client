<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

use JsonException;
use LaravelSkir\Runtime\DenseJson;
use LaravelSkir\Runtime\Exceptions\SkirRuntimeException;
use LaravelSkir\Runtime\MethodDescriptor;

final class Base64DenseJsonCodec implements SkirClientCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): string
    {
        return base64_encode(DenseJson::toJson($descriptor->requestType, $request));
    }

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed
    {
        try {
            $encodedDenseJson = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw SkirRuntimeException::invalidValue("Skir base64 dense JSON response is invalid: {$exception->getMessage()}");
        }

        if (! is_string($encodedDenseJson)) {
            throw SkirRuntimeException::invalidValue('Skir base64 dense JSON responses must be JSON strings.');
        }

        $denseJson = base64_decode($encodedDenseJson, true);

        if ($denseJson === false) {
            throw SkirRuntimeException::invalidValue('Skir base64 dense JSON response contains invalid base64.');
        }

        return DenseJson::fromJson($descriptor->responseType, $denseJson);
    }
}
