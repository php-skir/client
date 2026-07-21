# Invocation, Errors, and Testing

## Invoke typed clients

Resolve `Skir\Client\SkirClient` from Laravel and inject it into the generated `SkirRpcClient`.
Application code should call the generated methods so requests and responses retain their generated
types. Use low-level `SkirClient::invoke()` only for debugging or custom integrations; it returns the
decoded transport value rather than a generated response object.

The client and server must use the same codec:

- `dense_json`: descriptor-driven JSON; structs are positional arrays.
- `standard_json`: readable JSON objects with named struct fields.
- `base64_dense_json`: dense JSON inside a base64-encoded JSON string.
- `cbor`: binary `application/cbor`; requires `spomky-labs/cbor-php`.

CBOR is serialization, not privacy or encryption. A missing CBOR dependency or unsupported configured
codec throws `SkirClientException` while Laravel resolves the transport.

## Distinguish failures

| Boundary | Result | Inspect or correct |
|---|---|---|
| HTTP response considered failed, normally 4xx/5xx | `SkirClientException` with method and response | Inspect status/body and the server error policy |
| Successful HTTP response cannot be decoded | `SkirClientException` with method, null response, previous `SkirRuntimeException` | Verify response shape and matching codec |
| Request encoding fails | `SkirRuntimeException` propagates directly | Verify generated request values and codec input |
| DNS, connection, or TLS fails | Saloon `FatalRequestException` propagates with no response | Inspect URL, DNS, TLS, and connectivity |
| Codec is unsupported or optional CBOR support is absent | `SkirClientException` with null method and response | Correct config or install the optional dependency |

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
    && $request->body()->all()['method'] === 'GetUser'
);
$mockClient->assertSentCount(1);
```

Cover the typed happy path, failed HTTP response, malformed successful response, and any
application-specific encoding/network policy. Assert request class, endpoint, RPC method, encoded
body, and send count. JSON codecs use `SkirRpcRequest`; CBOR uses `SkirBinaryRpcRequest`, genuine
CBOR-encoded mock bodies, and `Accept` plus `Content-Type` assertions for `application/cbor`.
