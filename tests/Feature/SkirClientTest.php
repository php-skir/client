<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\Tests\TestCase;
use Skir\Client\WireFormat;
use Skir\Runtime\Field;
use Skir\Runtime\MethodDescriptor;
use Skir\Runtime\Type;

final class SkirClientTest extends TestCase
{
    #[Test]
    public function it_sends_a_skir_rpc_request_and_decodes_the_dense_json_response(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('25', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $client = new SkirClient('https://example.com/api');
        $client->withMockClient($mockClient);

        $result = $client->invoke(
            new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()),
            5.0,
        );

        $this->assertSame(25.0, $result);

        $mockClient->assertSent(function (Request $request): bool {
            return $request instanceof SkirRpcRequest
                && $request->resolveEndpoint() === '/'
                && $request->body()->all() === 'Square:1001::5';
        });
    }

    #[Test]
    public function it_encodes_struct_requests_and_decodes_struct_responses(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('[42,"Ruben"]', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $client = new SkirClient('https://example.com/api');
        $client->withMockClient($mockClient);

        $userType = Type::struct([
            Field::value('id', 0, Type::int32()),
            Field::value('name', 1, Type::string()),
        ]);

        $result = $client->invoke(
            new MethodDescriptor('RenameUser', 1002, $userType, $userType),
            [
                'id' => 42,
                'name' => 'Maxim',
            ],
        );

        $this->assertSame([
            'id' => 42,
            'name' => 'Ruben',
        ], $result);

        $mockClient->assertSent(function (Request $request): bool {
            return $request instanceof SkirRpcRequest
                && $request->body()->all() === 'RenameUser:1002::[42,"Maxim"]';
        });
    }

    #[Test]
    public function it_localises_failed_rpc_responses(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make([
                'error' => [
                    'code' => 'skir_method_not_found',
                    'message' => 'Skir method [Missing] is not registered.',
                ],
            ], 404),
        ]);

        $client = new SkirClient('https://example.com/api');
        $client->withMockClient($mockClient);

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Skir RPC request failed with status 404.');

        $client->invoke(
            new MethodDescriptor('Missing', 1001, Type::float32(), Type::float32()),
            5.0,
        );
    }

    #[Test]
    public function it_localises_invalid_dense_json_responses(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('not-json', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $client = new SkirClient('https://example.com/api');
        $client->withMockClient($mockClient);

        $this->expectException(SkirClientException::class);
        $this->expectExceptionMessage('Skir RPC response for [Square] could not be decoded.');

        $client->invoke(
            new MethodDescriptor('Square', 1001, Type::float32(), Type::float32()),
            5.0,
        );
    }

    #[Test]
    public function it_can_exchange_readable_json(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make("{\n  \"id\": 42,\n  \"name\": \"Ruben\"\n}", 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $client = new SkirClient('https://example.com/api', format: WireFormat::Readable);
        $client->withMockClient($mockClient);

        $userType = Type::struct([
            Field::value('id', 0, Type::int32()),
            Field::value('name', 1, Type::string()),
        ]);

        $result = $client->invoke(
            new MethodDescriptor('RenameUser', 1002, $userType, $userType),
            [
                'id' => 42,
                'name' => 'Maxim',
            ],
        );

        $this->assertSame([
            'id' => 42,
            'name' => 'Ruben',
        ], $result);

        $mockClient->assertSent(function (Request $request): bool {
            return $request instanceof SkirRpcRequest
                && $request->body()->all() === "RenameUser:1002:readable:{\n  \"id\": 42,\n  \"name\": \"Maxim\"\n}";
        });
    }
}
