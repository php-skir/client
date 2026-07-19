# Error handling and testing

The client reports failed HTTP responses and response-decoding failures through `SkirClientException`, while keeping the method descriptor available for application logging and policy decisions.

## Handle client failures

A response that Saloon considers failed throws `SkirClientException` with the message `Skir RPC request failed with status {status}.`. By default, Saloon treats 4xx and 5xx responses as failed. The invoked `MethodDescriptor` is available through `$exception->method`, and the Saloon `Response` is available through `$exception->response`. Inspect the response when your application needs its status or body; the exception does not expose separate server error fields.

Any response Saloon does not consider failed proceeds to response decoding. Redirects are therefore not automatically handled by the failed-response branch.

A 2xx response that cannot be decoded also throws `SkirClientException`, but with the message `Skir RPC response for [{method}] could not be decoded.`. In this case, `$exception->method` contains the method descriptor, `$exception->response` is `null`, and `$exception->getPrevious()` returns the underlying `SkirRuntimeException`.

The exception boundary is deliberately narrow:

- Only a codec-thrown `SkirRuntimeException` during response decoding is wrapped as an invalid-response `SkirClientException`.
- A `SkirRuntimeException` thrown while encoding the request propagates directly.
- DNS, connection, and TLS failures propagate as `Saloon\Exceptions\Request\FatalRequestException` and do not have a response.
- An unsupported configured codec or a missing optional CBOR dependency throws `SkirClientException` with both `method` and `response` set to `null`.

The following handler is specifically for the `SkirClientException` paths. It does not catch direct request-encoding or Saloon fatal-request exceptions:

```php
use Skir\Client\Exceptions\SkirClientException;

try {
    $user = $client->getUser($request);
} catch (SkirClientException $exception) {
    if ($exception->response !== null) {
        logger()->warning('SkirRPC request failed.', [
            'method' => $exception->method?->name,
            'status' => $exception->response->status(),
        ]);
    }

    throw $exception;
}
```

Rethrowing preserves the application's exception policy after adding contextual logging; Laravel's exception handler can report the exception once.

## Test a generated client

The following feature test belongs in a consuming Laravel application. It uses the application's `Tests\TestCase`, resolves the configured transport from the container, and exercises the generated standard-PHP client without making a network call.

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Skir\Admin\GetUserRequest;
use Skir\Admin\SkirRpcClient;
use Skir\Client\Exceptions\SkirClientException;
use Skir\Client\Http\SkirRpcRequest;
use Skir\Client\SkirClient as TransportSkirClient;
use Skir\Runtime\Exceptions\SkirRuntimeException;
use Tests\TestCase;

final class SkirRpcClientTest extends TestCase
{
    #[Test]
    public function it_sends_and_decodes_a_typed_request(): void
    {
        config()->set('skir-client.base_url', 'https://api.example.test');
        config()->set('skir-client.endpoint', '/api/skir');
        config()->set('skir-client.codec', 'dense_json');

        $transport = app(TransportSkirClient::class);
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('[42,"Maxim"]', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);
        $transport->withMockClient($mockClient);

        $client = new SkirRpcClient($transport);
        $user = $client->getUser(new GetUserRequest(userId: 42));

        $this->assertSame(42, $user->userId);
        $this->assertSame('Maxim', $user->name);

        $mockClient->assertSent(function (Request $request): bool {
            return $request instanceof SkirRpcRequest
                && $request->resolveEndpoint() === '/api/skir'
                && $request->body()->all() === [
                    'method' => 'GetUser',
                    'request' => [42],
                ];
        });
        $mockClient->assertSentCount(1);
    }

    #[Test]
    public function it_exposes_failed_http_responses(): void
    {
        config()->set('skir-client.base_url', 'https://api.example.test');
        config()->set('skir-client.endpoint', '/api/skir');
        config()->set('skir-client.codec', 'dense_json');

        $transport = app(TransportSkirClient::class);
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make([], 404),
        ]);
        $transport->withMockClient($mockClient);

        $client = new SkirRpcClient($transport);

        try {
            $client->getUser(new GetUserRequest(userId: 42));
            $this->fail('Expected a failed SkirRPC response.');
        } catch (SkirClientException $exception) {
            $this->assertSame('Skir RPC request failed with status 404.', $exception->getMessage());
            $this->assertSame('GetUser', $exception->method?->name);
            $this->assertSame(404, $exception->response?->status());
        }
    }

    #[Test]
    public function it_exposes_invalid_successful_responses(): void
    {
        config()->set('skir-client.base_url', 'https://api.example.test');
        config()->set('skir-client.endpoint', '/api/skir');
        config()->set('skir-client.codec', 'dense_json');

        $transport = app(TransportSkirClient::class);
        $mockClient = new MockClient([
            SkirRpcRequest::class => MockResponse::make('not-json', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);
        $transport->withMockClient($mockClient);

        $client = new SkirRpcClient($transport);

        try {
            $client->getUser(new GetUserRequest(userId: 42));
            $this->fail('Expected an invalid SkirRPC response.');
        } catch (SkirClientException $exception) {
            $this->assertSame('Skir RPC response for [GetUser] could not be decoded.', $exception->getMessage());
            $this->assertSame('GetUser', $exception->method?->name);
            $this->assertNull($exception->response);
            $this->assertInstanceOf(SkirRuntimeException::class, $exception->getPrevious());
        }
    }
}
```

`MockClient` matches the configured response to `SkirRpcRequest`, records the outgoing request, and lets `assertSent()` verify the request class, endpoint, method name, and encoded body. `assertSentCount(1)` also guards against duplicate calls. No network call is made.

When the application selects the binary CBOR codec, mock and assert `Skir\Client\Http\SkirBinaryRpcRequest` instead. Return an actual CBOR-encoded value in the `MockResponse`, such as one produced by `Skir\Runtime\Cbor::encodeValue()` for the generated method's response type. Assert that the request body is a CBOR binary string, decode it when the envelope needs inspection, and assert that both `Accept` and `Content-Type` are `application/cbor` rather than applying the JSON array assertion shown above.

See [Configuration and codecs](configuration-and-codecs.md) for transport setup and codec selection, and [Generating clients](generating-clients.md) for the schema and generated classes used above.
