# Configuration and codecs

Publish the Laravel configuration file:

```bash
php artisan vendor:publish --tag=skir-client-config
```

Set the server base URL, RPC endpoint, and wire codec:

```dotenv
SKIR_CLIENT_BASE_URL=https://api.example.test
SKIR_CLIENT_ENDPOINT=/api/skir
SKIR_CLIENT_CODEC=dense_json
```

`SKIR_CLIENT_BASE_URL` has no default and is required; it identifies the server origin or shared API prefix. `SKIR_CLIENT_ENDPOINT` defaults to `/`, and `SKIR_CLIENT_CODEC` defaults to `dense_json`.

`SkirConnector` trims a trailing slash from the base URL, while each request resolves the configured relative endpoint. Do not repeat the endpoint path in both values: use `https://api.example.test` plus `/api/skir`, not `https://api.example.test/api/skir` plus `/api/skir`.

## Resolve or construct the client

In Laravel, the service provider builds the client from the published configuration:

```php
use Skir\Client\SkirClient;

$client = app(SkirClient::class);
```

You can also construct clients directly. Dense JSON is the constructor default; the other codecs use `SkirClientCodecs` factories:

```php
use Skir\Client\Codecs\SkirClientCodecs;
use Skir\Client\SkirClient;

$denseJson = new SkirClient('https://api.example.test', '/api/skir');
$explicitDenseJson = new SkirClient(
    baseUrl: 'https://api.example.test',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::denseJson(),
);
$standardJson = new SkirClient(
    baseUrl: 'https://api.example.test',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::standardJson(),
);
$base64DenseJson = new SkirClient(
    baseUrl: 'https://api.example.test',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::base64DenseJson(),
);
$cbor = new SkirClient(
    baseUrl: 'https://api.example.test',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::cbor(),
);
```

The first two clients use the same dense JSON codec; the second selects its factory explicitly.

## Choose a matching codec

The configured codec must match the codec exposed by the server endpoint.

| `SKIR_CLIENT_CODEC` | Wire shape |
| --- | --- |
| `dense_json` | Compact, descriptor-driven JSON values; structs use positional arrays. |
| `standard_json` | Readable JSON where structs use named object fields and primitives remain primitive values. |
| `base64_dense_json` | A dense JSON payload carried inside a base64-encoded JSON string. |
| `cbor` | Binary request and response bodies using `application/cbor`. |

CBOR support is optional. Install its dependency before selecting `cbor`:

```bash
composer require spomky-labs/cbor-php
```

Selecting `cbor` without that package throws a `SkirClientException`. An unknown configured codec value also throws `SkirClientException` when Laravel resolves the client.

See [Generating clients](generating-clients.md) for the typed generation workflow and [Error handling and testing](error-handling-and-testing.md) for transport failure handling and mocks.
