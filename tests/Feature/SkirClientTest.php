<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Tests\Feature;

use LaravelSkir\Client\Exceptions\SkirClientException;
use LaravelSkir\Client\Http\SkirRpcRequest;
use LaravelSkir\Client\SkirClient;
use LaravelSkir\Client\Tests\TestCase;
use LaravelSkir\Runtime\Field;
use LaravelSkir\Runtime\MethodDescriptor;
use LaravelSkir\Runtime\Type;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

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
                && $request->body()->all() === [
                    'method' => 'Square',
                    'request' => 5.0,
                ];
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
                && $request->body()->all() === [
                    'method' => 'RenameUser',
                    'request' => [42, 'Maxim'],
                ];
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
}
