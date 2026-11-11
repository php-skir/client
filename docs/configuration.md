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

The client speaks the standard SkirRPC protocol, the same one upstream Skir clients use, so any SkirRPC server works, whether it is `php-skir/server` or a service written in another language. Each call is a `POST` whose body is `MethodName:methodNumber:format:request`.

By default requests and responses use dense JSON, which the Skir docs recommend for APIs. Set `SKIR_CLIENT_FORMAT=readable` to exchange readable JSON instead, for example while debugging:

```dotenv
SKIR_CLIENT_FORMAT=readable
```

```php
use Skir\Client\SkirClient;
use Skir\Client\WireFormat;

$client = new SkirClient('https://api.example.test', '/api/skir', WireFormat::Readable);
```

Readable JSON is meant for humans and is not safe for persistence. Binary is not part of the SkirRPC wire protocol; generated classes offer `toBytes()` and `fromBytes()` when you need it outside RPC.

## Authentication

The client sends no credentials by default. Set `SKIR_CLIENT_AUTH_TYPE` to `header`, `bearer`, or `basic` to authenticate every request. The package uses Saloon's built-in authenticators, so the request is authenticated the same way any Saloon connector would be.

### Custom HTTP header

Send a shared secret in a header of your choice:

```dotenv
SKIR_CLIENT_AUTH_TYPE=header
SKIR_CLIENT_AUTH_HEADER=X-Internal-Token
SKIR_CLIENT_AUTH_TOKEN=your-shared-secret
```

Every SkirRPC request now includes:

```http
X-Internal-Token: your-shared-secret
```

`SKIR_CLIENT_AUTH_HEADER` defaults to `Authorization`, so leaving it out sends the raw token in the `Authorization` header.

To rotate the secret, have the server accept both the current and the next value, update `SKIR_CLIENT_AUTH_TOKEN`, and refresh the configuration cache with `php artisan config:cache` if you use it. The client always sends a single token.

### Bearer token

```dotenv
SKIR_CLIENT_AUTH_TYPE=bearer
SKIR_CLIENT_AUTH_TOKEN=your-token
```

This sends `Authorization: Bearer your-token`.

### Basic authentication

```dotenv
SKIR_CLIENT_AUTH_TYPE=basic
SKIR_CLIENT_AUTH_USERNAME=admin
SKIR_CLIENT_AUTH_PASSWORD=secret
```

When an auth type is set but a credential it needs is empty, resolving the client throws `SkirClientException`. The client never falls back to an unauthenticated request. An unknown auth type also throws.

### Without the container

Pass any Saloon authenticator to `withAuthenticator()`:

```php
use Saloon\Http\Auth\HeaderAuthenticator;
use Skir\Client\SkirClient;

$client = (new SkirClient('https://api.example.test', '/api/skir'))
    ->withAuthenticator(new HeaderAuthenticator(config('services.internal.token'), 'X-Internal-Token'));
```

### Custom authentication

For anything else, implement `Saloon\Contracts\Authenticator` and attach it to the container-resolved client in your `AppServiceProvider`:

```php
use App\Http\Auth\SignedRequestAuthenticator;
use Skir\Client\SkirClient;

public function register(): void
{
    $this->app->extend(
        SkirClient::class,
        fn (SkirClient $client) => $client->withAuthenticator(new SignedRequestAuthenticator()),
    );
}
```

Use `Saloon\Http\Auth\MultiAuthenticator` to combine several authenticators, for example to send two headers.

See [Generating clients](generating-clients.md) for the typed generation workflow and [Error handling and testing](error-handling-and-testing.md) for transport failure handling and mocks.
