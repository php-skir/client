<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\SkirClientServiceProvider;
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

    #[Test]
    public function it_resolves_header_authentication_from_config(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.auth.type', 'header');
        config()->set('skir-client.auth.header', 'X-Trackr-Internal-Token');
        config()->set('skir-client.auth.token', 'secret');

        $this->assertSentHeader(app(SkirClient::class), 'X-Trackr-Internal-Token', 'secret');
    }

    #[Test]
    public function it_resolves_bearer_authentication_from_config(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.auth.type', 'bearer');
        config()->set('skir-client.auth.token', 'secret');

        $this->assertSentHeader(app(SkirClient::class), 'Authorization', 'Bearer secret');
    }

    #[Test]
    public function it_resolves_basic_authentication_from_config(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.auth.type', 'basic');
        config()->set('skir-client.auth.username', 'admin');
        config()->set('skir-client.auth.password', 'hunter2');

        $this->assertSentHeader(app(SkirClient::class), 'Authorization', 'Basic '.base64_encode('admin:hunter2'));
    }

    #[Test]
    public function it_rejects_an_unsupported_auth_type(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.auth.type', 'digest');

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Skir client auth type [digest] is not supported. Use [header], [bearer], or [basic].');

        app(SkirClient::class);
    }

    #[Test]
    public function it_requires_a_token_when_authentication_is_configured(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');
        config()->set('skir-client.auth.type', 'header');
        config()->set('skir-client.auth.token', null);

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Set [skir-client.auth.token] (SKIR_CLIENT_AUTH_TOKEN) to use the configured Skir client authentication.');

        app(SkirClient::class);
    }

    #[Test]
    public function it_lets_the_application_add_a_custom_authenticator(): void
    {
        config()->set('skir-client.base_url', 'https://example.com/api');

        app()->extend(SkirClient::class, fn (SkirClient $client): SkirClient => $client->withAuthenticator(
            new class implements Authenticator
            {
                public function set(PendingRequest $pendingRequest): void
                {
                    $pendingRequest->headers()->add('X-Custom', 'custom');
                }
            },
        ));

        $this->assertSentHeader(app(SkirClient::class), 'X-Custom', 'custom');
    }

    #[Test]
    public function it_reads_a_renamed_config_file(): void
    {
        SkirClientServiceProvider::useConfig('trackr-skir');
        config()->set('trackr-skir', [
            'base_url' => 'https://trackr.example.com',
            'endpoint' => '/internal/skir',
            'format' => 'dense',
            'auth' => [
                'type' => 'header',
                'header' => 'X-Trackr-Internal-Token',
                'token' => 'secret',
            ],
        ]);

        $client = app(SkirClient::class);

        $this->assertSentHeader($client, 'X-Trackr-Internal-Token', 'secret');
    }

    #[Test]
    public function it_names_the_renamed_config_key_when_the_base_url_is_missing(): void
    {
        SkirClientServiceProvider::useConfig('trackr-skir');

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Set [trackr-skir.base_url] (SKIR_CLIENT_BASE_URL) to the SkirRPC server URL.');

        app(SkirClient::class);
    }

    private function assertSentHeader(SkirClient $client, string $header, string $value): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('25', 200),
        ]);

        $client->withMockClient($mockClient);
        $client->invoke(new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()), 5.0);

        $mockClient->assertSent(function (SkirRpcRequest $request, Response $response) use ($header, $value): bool {
            return $response->getPendingRequest()->headers()->get($header) === $value;
        });
    }
}
