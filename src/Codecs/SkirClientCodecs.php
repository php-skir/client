<?php

declare(strict_types=1);

namespace Skir\Client\Codecs;

use Skir\Client\Exceptions\SkirClientException;
use Skir\Runtime\Cbor;

final class SkirClientCodecs
{
    public static function cbor(): SkirClientCodec
    {
        if (! Cbor::available()) {
            throw SkirClientException::missingCborDependency();
        }

        return new CborCodec;
    }

    public static function denseJson(): SkirClientCodec
    {
        return new DenseJsonCodec;
    }

    public static function standardJson(): SkirClientCodec
    {
        return new StandardJsonCodec;
    }

    public static function base64DenseJson(): SkirClientCodec
    {
        return new Base64DenseJsonCodec;
    }
}
