<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

use LaravelSkir\Runtime\MethodDescriptor;

interface SkirClientCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): mixed;

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed;
}
