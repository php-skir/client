<?php

declare(strict_types=1);

namespace Skir\Client;

use Saloon\Http\Faking\MockClient;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirConnector;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Runtime\DenseJson;
use Skir\Runtime\Exceptions\SkirRuntimeException;
use Skir\Runtime\MethodDescriptor;

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
        $response = $this->connector->send(
            new SkirRpcRequest($descriptor, $request, $this->endpoint),
        );

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
