# Laravel Skir Client

Laravel package for consuming SkirRPC services with Saloon.

## Installation

```bash
composer require laravel-skir/client
```

Publish the config if you want Laravel container resolution:

```bash
php artisan vendor:publish --tag=skir-client-config
```

```env
SKIR_CLIENT_BASE_URL=https://example.com/api/skir
SKIR_CLIENT_ENDPOINT=/
SKIR_CLIENT_CODEC=dense_json
```

## Usage

Use generated `MethodDescriptor` objects from `skir-php-generator` or `skir-laravel-data-generator`:

```php
use App\Skir\Admin\SkirMethods;
use LaravelSkir\Client\SkirClient;

$client = new SkirClient('https://example.com/api/skir');

$user = $client->invoke(
    SkirMethods::getUser(),
    [
        'id' => 42,
        'name' => 'Maxim',
    ],
);
```

In Laravel, resolve the configured client from the container:

```php
$user = app(SkirClient::class)->invoke(SkirMethods::getUser(), $payload);
```

Generated typed client adapters wrap this lower-level transport:

```php
use App\Skir\Admin\GetUserRequestData;
use App\Skir\Admin\SkirRpcClient;
use LaravelSkir\Client\SkirClient;

$client = new SkirRpcClient(app(SkirClient::class));

$user = $client->getUser(new GetUserRequestData(
    userId: 42,
));
```

## Generation

The package includes an Artisan wrapper for the Skir compiler:

```bash
php artisan skir:generate-client
```

Use `skir-php-generator` for standard PHP DTOs or `skir-laravel-data-generator` for Spatie Laravel Data DTOs in your `skir.yml`.

Example `skir.yml` for Laravel Data clients:

```yaml
generators:
  - mod: skir-laravel-data-generator
    outDir: app/SkirGenerated
    config:
      namespace: App\Skir
```

The command runs:

```bash
node node_modules/skir/dist/compiler.js gen --root <project-root>
```

Configure the executable paths with:

```env
SKIR_CLIENT_NODE=node
SKIR_CLIENT_SKIR_BIN=/absolute/path/to/node_modules/skir/dist/compiler.js
SKIR_CLIENT_ROOT=/absolute/path/to/project
```

The command validates the configured project root and compiler file before spawning Node, then streams compiler output back through Artisan. Typed PHP objects, method descriptors, and typed RPC client adapters are produced by the Skir generator packages configured in `skir.yml`; this package provides the transport and the Laravel command to run that generation flow.

## Codecs

Dense JSON is the default and matches the default server endpoint:

```php
use App\Skir\Admin\SkirRpcClient;
use LaravelSkir\Client\SkirClient;

$transport = new SkirClient('https://example.com', '/api/skir');
$client = new SkirRpcClient($transport);
```

For readable JSON endpoints, configure both sides as standard JSON:

```php
use LaravelSkir\Client\Codecs\SkirClientCodecs;
use LaravelSkir\Client\SkirClient;

$transport = new SkirClient(
    baseUrl: 'https://example.com',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::standardJson(),
);
```

For base64-encoded dense JSON endpoints:

```php
$transport = new SkirClient(
    baseUrl: 'https://example.com',
    endpoint: '/api/skir',
    codec: SkirClientCodecs::base64DenseJson(),
);
```

Container configuration supports:

```env
SKIR_CLIENT_CODEC=dense_json
SKIR_CLIENT_CODEC=standard_json
SKIR_CLIENT_CODEC=base64_dense_json
```

The selected client codec must match the server endpoint codec.
