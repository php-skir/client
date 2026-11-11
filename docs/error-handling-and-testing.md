# Error handling and testing

The client reports failed HTTP responses and response-decoding failures through `SkirClientException`, with the invoked method descriptor attached.

## Handle client failures

A response that Saloon considers failed throws `SkirClientException` with the message `Skir RPC request failed with status {status}.`. By default, Saloon treats 4xx and 5xx responses as failed. The invoked `MethodDescriptor` is available through `$exception->method`, and the Saloon `Response` is available through `$exception->response`. Inspect the response when your application needs its status or body; the exception does not expose separate server error fields.

Redirects are followed by the underlying Guzzle client; any other response Saloon does not consider failed is decoded.

A 2xx response that cannot be decoded also throws `SkirClientException`, but with the message `Skir RPC response for [{method}] could not be decoded.`. In this case, `$exception->method` contains the method descriptor, `$exception->response` is `null`, and `$exception->getPrevious()` returns the underlying `SkirRuntimeException`.

Only these cases throw `SkirClientException`:

- a failed HTTP response;
- a successful response that cannot be decoded (the `SkirRuntimeException` is available as the previous exception);
- resolving the client from the container without a configured base URL;
- resolving the client with an unsupported `auth.type`, or with an auth type whose credential is empty.

A `SkirRuntimeException` thrown while encoding the request propagates directly. DNS, connection, and TLS failures propagate as `Saloon\Exceptions\Request\FatalRequestException` and have no response.

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
                && $request->descriptor->name === 'GetUser'
                && $request->body()->all() === 'GetUser:'.$request->descriptor->number.'::[42]';
        });
        $mockClient->assertSentCount(1);
    }

    #[Test]
    public function it_exposes_failed_http_responses(): void
    {
        config()->set('skir-client.base_url', 'https://api.example.test');
        config()->set('skir-client.endpoint', '/api/skir');

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

See [Configuration](configuration.md) for transport setup, and [Generating clients](generating-clients.md) for the schema and generated classes used above.
