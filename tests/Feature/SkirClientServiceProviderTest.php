<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\Tests\TestCase;
use Skir\Client\WireFormat;
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
    public function it_requires_a_base_url_when_resolving_the_client(): void
    {
        config()->set('skir-client.base_url', null);

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Set [skir-client.base_url] (SKIR_CLIENT_BASE_URL) to the SkirRPC server URL.');

        app(SkirClient::class);
    }

    #[Test]
    public function it_resolves_the_configured_wire_format(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.format', 'readable');

        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('25', 200),
        ]);

        $client = app(SkirClient::class);
        $client->withMockClient($mockClient);
        $client->invoke(new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()), 5.0);

        $mockClient->assertSent(function (SkirRpcRequest $request): bool {
            return $request->format === WireFormat::Readable
                && $request->body()->all() === 'Square:1001:readable:5';
        });
    }

    #[Test]
    public function it_rejects_an_unsupported_wire_format(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.format', 'binary');

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Skir client format [binary] is not supported. Use [dense] or [readable].');

        app(SkirClient::class);
    }
}
