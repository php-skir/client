<?php

declare(strict_types=1);

namespace Skir\Client\Codecs;

use Skir\Runtime\MethodDescriptor;

interface SkirClientHttpCodec extends SkirClientCodec
{
    public function encodeRequestBody(MethodDescriptor $descriptor, mixed $request): string;

    public function contentType(): string;
}
