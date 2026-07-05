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

## Generation

The package includes an Artisan wrapper for the Skir compiler:

```bash
php artisan skir:generate-client
```

Use `skir-php-generator` for standard PHP DTOs or `skir-laravel-data-generator` for Spatie Laravel Data DTOs in your `skir.yml`.

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
