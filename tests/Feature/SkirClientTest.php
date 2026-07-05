<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Tests\Feature;

use LaravelSkir\Client\Codecs\SkirClientCodecs;
use LaravelSkir\Client\Exceptions\SkirClientException;
use LaravelSkir\Client\Http\SkirRpcRequest;
use LaravelSkir\Client\SkirClient;
use LaravelSkir\Client\Tests\TestCase;
use LaravelSkir\Runtime\DenseJson;
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
    public function it_can_use_standard_json_for_readable_http_payloads(): void
    {
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make([
                'id' => 42,
                'name' => 'Ruben',
            ], 200),
        ]);

        $client = new SkirClient('https://example.com/api', codec: SkirClientCodecs::standardJson());
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
                    'request' => [
                        'id' => 42,
                        'name' => 'Maxim',
                    ],
                ];
        });
    }

    #[Test]
    public function it_can_use_base64_encoded_dense_json_payloads(): void
    {
        $userType = Type::struct([
            Field::value('id', 0, Type::int32()),
            Field::value('name', 1, Type::string()),
        ]);

        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make(
                json_encode(
                    base64_encode(DenseJson::toJson($userType, [
                        'id' => 42,
                        'name' => 'Ruben',
                    ])),
                    JSON_THROW_ON_ERROR,
                ),
                200,
            ),
        ]);

        $client = new SkirClient('https://example.com/api', codec: SkirClientCodecs::base64DenseJson());
        $client->withMockClient($mockClient);

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

        $mockClient->assertSent(function (Request $request) use ($userType): bool {
            return $request instanceof SkirRpcRequest
                && $request->body()->all() === [
                    'method' => 'RenameUser',
                    'request' => base64_encode(DenseJson::toJson($userType, [
                        'id' => 42,
                        'name' => 'Maxim',
                    ])),
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
