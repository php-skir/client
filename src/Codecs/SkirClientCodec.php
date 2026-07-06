<?php

declare(strict_types=1);

namespace Skir\Client\Codecs;

use Skir\Runtime\MethodDescriptor;

interface SkirClientCodec
{
    public function encodeRequest(MethodDescriptor $descriptor, mixed $request): mixed;

    public function decodeResponse(MethodDescriptor $descriptor, string $response): mixed;
}
