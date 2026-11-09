<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\Tests\TestCase;
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
}
