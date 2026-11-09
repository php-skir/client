# Configuration

Publish the Laravel configuration file:

```bash
php artisan vendor:publish --tag=skir-client-config
```

Set the server base URL and RPC endpoint:

```dotenv
SKIR_CLIENT_BASE_URL=https://api.example.test
SKIR_CLIENT_ENDPOINT=/api/skir
```

`SKIR_CLIENT_BASE_URL` has no default and is required; it identifies the server origin or shared API prefix. `SKIR_CLIENT_ENDPOINT` defaults to `/`.

`SkirConnector` trims a trailing slash from the base URL, while each request resolves the configured relative endpoint. Do not repeat the endpoint path in both values: use `https://api.example.test` plus `/api/skir`, not `https://api.example.test/api/skir` plus `/api/skir`.

## Resolve or construct the client

In Laravel, the service provider builds the client from the published configuration:

```php
use Skir\Client\SkirClient;

$client = app(SkirClient::class);
```

You can also construct one directly:

```php
use Skir\Client\SkirClient;

$client = new SkirClient('https://api.example.test', '/api/skir');
```

## Wire format

The client speaks the standard SkirRPC protocol, the same one upstream Skir clients use. Each call is a `POST` whose body is `MethodName:methodNumber::denseJsonRequest`, and the server answers with the response in dense JSON. Any SkirRPC server works, whether it is `php-skir/server` or a service written in another language.

See [Generating clients](generating-clients.md) for the typed generation workflow and [Error handling and testing](error-handling-and-testing.md) for transport failure handling and mocks.
