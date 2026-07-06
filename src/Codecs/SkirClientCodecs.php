<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

use LaravelSkir\Client\Exceptions\SkirClientException;
use LaravelSkir\Runtime\Cbor;

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
