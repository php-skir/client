<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Codecs;

final class SkirClientCodecs
{
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
