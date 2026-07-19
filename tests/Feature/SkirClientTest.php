<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use CBOR\Decoder;
use CBOR\Encoder;
use CBOR\StringStream;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Skir\Client\Codecs\SkirClientCodec;
use Skir\Client\Codecs\SkirClientCodecs;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient;
use Skir\Client\Tests\TestCase;
use Skir\Runtime\DenseJson;
use Skir\Runtime\Exceptions\SkirRuntimeException;
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
    public function it_can_use_cbor_payloads(): void
    {
        $userType = Type::struct([
            Field::value('id', 0, Type::int32()),
            Field::value('name', 1, Type::string()),
        ]);

        $mockClient = new MockClient([
            MockResponse::make(
                (new Encoder)->encode(DenseJson::encode($userType, [
                    'id' => 42,
                    'name' => 'Ruben',
                ])),
                200,
                [
                    'Content-Type' => 'application/cbor',
                ],
            ),
        ]);

        $client = new SkirClient('https://example.com/api', codec: SkirClientCodecs::cbor());
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
            $payload = Decoder::create()
                ->decode(StringStream::create($request->body()->all()))
                ->normalize();

            return $request->resolveEndpoint() === '/'
                && $request->headers()->get('Content-Type') === 'application/cbor'
                && $payload['method'] === 'RenameUser'
                && DenseJson::decode($userType, $payload['request']) === [
                    'id' => 42,
                    'name' => 'Maxim',
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

    #[Test]
    public function it_localises_invalid_standard_json_responses(): void
    {
        $this->assertInvalidResponseIsLocalised(
            SkirClientCodecs::standardJson(),
            'not-json',
            'Skir standard JSON response is invalid:',
        );
    }

    #[Test]
    public function it_localises_invalid_base64_dense_json_responses(): void
    {
        $this->assertInvalidResponseIsLocalised(
            SkirClientCodecs::base64DenseJson(),
            '"%%%"',
            'Skir base64 dense JSON response contains invalid base64.',
        );
    }

    #[Test]
    public function it_localises_invalid_cbor_responses(): void
    {
        $this->assertInvalidResponseIsLocalised(
            SkirClientCodecs::cbor(),
            "\xff",
            'Invalid CBOR:',
        );
    }

    private function assertInvalidResponseIsLocalised(
        SkirClientCodec $codec,
        string $responseBody,
        string $runtimeExceptionMessage,
    ): void {
        $mockClient = new MockClient([
            MockResponse::make($responseBody, 200),
        ]);
        $descriptor = new MethodDescriptor('Square', 1001, Type::float32(), Type::float32());
        $client = new SkirClient('https://example.com/api', codec: $codec);
        $client->withMockClient($mockClient);

        try {
            $client->invoke($descriptor, 5.0);
            $this->fail('Expected an invalid RPC response to throw.');
        } catch (SkirClientException $exception) {
            $this->assertSame('Skir RPC response for [Square] could not be decoded.', $exception->getMessage());
            $this->assertSame($descriptor, $exception->method);
            $this->assertInstanceOf(SkirRuntimeException::class, $exception->getPrevious());
            $this->assertStringContainsString($runtimeExceptionMessage, $exception->getPrevious()->getMessage());
        }
    }
}
