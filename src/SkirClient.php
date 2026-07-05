<?php

declare(strict_types=1);

namespace LaravelSkir\Client;

use LaravelSkir\Client\Exceptions\SkirClientException;
use LaravelSkir\Client\Http\SkirConnector;
use LaravelSkir\Client\Http\SkirRpcRequest;
use LaravelSkir\Runtime\DenseJson;
use LaravelSkir\Runtime\Exceptions\SkirRuntimeException;
use LaravelSkir\Runtime\MethodDescriptor;
use Saloon\Http\Faking\MockClient;

final class SkirClient
{
    private SkirConnector $connector;

    public function __construct(
        string $baseUrl,
        private readonly string $endpoint = '/',
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
        $response = $this->connector->send(new SkirRpcRequest($descriptor, $request, $this->endpoint));

        if ($response->failed()) {
            throw SkirClientException::failedResponse($descriptor, $response);
        }

        try {
            return DenseJson::fromJson($descriptor->responseType, $response->body());
        } catch (SkirRuntimeException $exception) {
            throw SkirClientException::invalidResponse($descriptor, $exception);
        }
    }
}
