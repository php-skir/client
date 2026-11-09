# Invocation, Errors, and Testing

## Invoke typed clients

Resolve `Skir\Client\SkirClient` from Laravel and inject it into the generated `SkirRpcClient`.
Application code should call the generated methods so requests and responses retain their generated
types. Use low-level `SkirClient::invoke()` only for debugging or custom integrations; it returns the
decoded transport value rather than a generated response object.

The client speaks the standard SkirRPC protocol: it POSTs `MethodName:methodNumber::denseJson` and
decodes a dense JSON response, so it works against any SkirRPC server.

## Distinguish failures

| Boundary | Result | Inspect or correct |
|---|---|---|
| HTTP response considered failed, normally 4xx/5xx | `SkirClientException` with method and response | Inspect status/body and the server error policy |
| Successful HTTP response cannot be decoded | `SkirClientException` with method, null response, previous `SkirRuntimeException` | Verify the endpoint is a SkirRPC server and the schemas match |
| Request encoding fails | `SkirRuntimeException` propagates directly | Verify generated request values |
| DNS, connection, or TLS fails | Saloon `FatalRequestException` propagates with no response | Inspect URL, DNS, TLS, and connectivity |

Responses Saloon does not classify as failed, including redirects by default, continue to decoding.
Catching only `SkirClientException` does not catch encoding or network failures.

## Test without network access

Use Saloon's `MockClient` and `MockResponse`, not Laravel `Http::fake()`:

```php
$transport = app(TransportSkirClient::class);
$mockClient = new MockClient([
    SkirRpcRequest::class => MockResponse::make('[42,"Maxim"]', 200),
]);
$transport->withMockClient($mockClient);

$client = new SkirRpcClient($transport);
$user = $client->getUser(new GetUserRequest(userId: 42));

$mockClient->assertSent(fn (Request $request): bool =>
    $request instanceof SkirRpcRequest
    && $request->resolveEndpoint() === '/api/skir'
    && $request->descriptor->name === 'GetUser'
);
$mockClient->assertSentCount(1);
```

Cover the typed happy path, failed HTTP response, malformed successful response, and any
application-specific encoding/network policy. Assert request class, endpoint, RPC method, encoded
body, and send count. Mock and assert `SkirRpcRequest`.
