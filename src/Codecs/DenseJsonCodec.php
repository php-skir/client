<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

use LaravelSkir\Runtime\DenseJson;
use LaravelSkir\Runtime\MethodDescriptor;

final class DenseJsonCodec implements SkirClientCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): mixed
    {
        return DenseJson::encode($descriptor->requestType, $request);
    }

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed
    {
        return DenseJson::fromJson($descriptor->responseType, $response);
    }
}
