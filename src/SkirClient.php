<?php

declare(strict_types=1);

namespace Skir\Client;

use Saloon\Http\Faking\MockClient;
use Skir\Client\Codecs\DenseJsonCodec;
use Skir\Client\Codecs\SkirClientCodec;
use Skir\Client\Codecs\SkirClientHttpCodec;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirBinaryRpcRequest;
use Skir\Client\Http\SkirConnector;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Runtime\Exceptions\SkirRuntimeException;
use Skir\Runtime\MethodDescriptor;

final class SkirClient
{
    private SkirConnector $connector;

    public function __construct(
        string $baseUrl,
        private readonly string $endpoint = '/',
        private readonly SkirClientCodec $codec = new DenseJsonCodec,
    ) {
        $this->connector = new SkirConnector($baseUrl);
    }

    public function withMockClient(MockClient $mockClient): self
    {
        $this->connector->withMockClient($mockClient);

        return $this;
    }

    public function invoke(MethodDescriptor $descriptor, mixed $request): mixed
    {
        $rpcRequest = $this->codec instanceof SkirClientHttpCodec
            ? new SkirBinaryRpcRequest($descriptor, $request, $this->codec, $this->endpoint)
            : new SkirRpcRequest($descriptor, $request, $this->codec, $this->endpoint);

        $response = $this->connector->send($rpcRequest);

        if ($response->failed()) {
            throw SkirClientException::failedResponse($descriptor, $response);
        }

        try {
            return $this->codec->decodeResponse($descriptor, $response->body());
        } catch (SkirRuntimeException $exception) {
            throw SkirClientException::invalidResponse($descriptor, $exception);
        }
    }
}
