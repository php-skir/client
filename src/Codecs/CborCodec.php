<?php

declare(strict_types=1);

namespace Skir\Client\Codecs;

use Skir\Runtime\Cbor;
use Skir\Runtime\MethodDescriptor;

final class CborCodec implements SkirClientHttpCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): mixed
    {
        return Cbor::encodeValuePayload($descriptor->requestType, $request);
    }

    public function encodeRequestBody(MethodDescriptor $descriptor, mixed $request): string
    {
        return Cbor::encodeEnvelope($descriptor, $request);
    }

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed
    {
        return Cbor::decodeValue($descriptor->responseType, $response);
    }

    public function contentType(): string
    {
        return 'application/cbor';
    }
}
