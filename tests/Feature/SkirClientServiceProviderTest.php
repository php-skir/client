<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use CBOR\Encoder;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\Tests\TestCase;
use Skir\Runtime\DenseJson;
use Skir\Runtime\MethodDescriptor;
use Skir\Runtime\Type;

final class SkirClientServiceProviderTest extends TestCase
{
    #[Test]
    public function it_resolves_a_configured_client_from_the_container(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.endpoint', '/rpc');

        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('25', 200),
        ]);

        $client = app(SkirClient::class);
        $client->withMockClient($mockClient);

        $result = $client->invoke(
            new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()),
            5.0,
        );

        $this->assertSame(25.0, $result);

        $mockClient->assertSent(function (SkirRpcRequest $request): bool {
            return $request->resolveEndpoint() === '/rpc';
        });
    }

    #[Test]
    public function it_resolves_a_configured_standard_json_client_from_the_container(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.codec', 'standard_json');

        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make([
                'id' => 42,
            ], 200),
        ]);

        $client = app(SkirClient::class);
        $client->withMockClient($mockClient);

        $result = $client->invoke(
            new MethodDescriptor('FindUser', 1002, Type::struct([]), Type::struct([])),
            [
                'id' => 42,
            ],
        );

        $this->assertSame([
            'id' => 42,
        ], $result);

        $mockClient->assertSent(function (SkirRpcRequest $request): bool {
            return $request->body()->all() === [
                'method' => 'FindUser',
                'request' => [
                    'id' => 42,
                ],
            ];
        });
    }

    #[Test]
    public function it_resolves_a_configured_cbor_client_from_the_container(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.codec', 'cbor');

        $mockClient = new MockClient([
            MockResponse::make((new Encoder)->encode(DenseJson::encode(Type::float32(), 25.0)), 200),
        ]);

        $client = app(SkirClient::class);
        $client->withMockClient($mockClient);

        $result = $client->invoke(
            new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()),
            5.0,
        );

        $this->assertSame(25.0, $result);

        $mockClient->assertSent(function ($request): bool {
            return $request->headers()->get('Content-Type') === 'application/cbor';
        });
    }

    #[Test]
    public function it_fails_with_a_package_exception_for_unknown_configured_codecs(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.codec', 'xml');

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Skir client codec [xml] is not supported.');

        app(SkirClient::class);
    }
}
