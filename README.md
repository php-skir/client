![Laravel Skir Client](art/banner.png)

# Laravel Skir Client

[![Tests](https://github.com/php-skir/client/actions/workflows/tests.yml/badge.svg)](https://github.com/php-skir/client/actions/workflows/tests.yml)
[![Coverage](https://raw.githubusercontent.com/php-skir/client/badges/coverage.svg)](https://github.com/php-skir/client/actions/workflows/tests.yml)
[![Composer](https://img.shields.io/packagist/v/php-skir/client?label=composer&logo=composer)](https://packagist.org/packages/php-skir/client)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777BB4?logo=php&logoColor=white)](https://packagist.org/packages/php-skir/client)
[![License](https://img.shields.io/github/license/php-skir/client)](LICENSE)

Laravel package for calling SkirRPC services through generated typed clients and Saloon.

## Features

- Generate typed RPC clients from Skir schemas with the [client generation guide](docs/generating-clients.md).
- Use standard PHP objects, [Laravel Data](docs/laravel-data.md), or [Simple Data Objects](docs/simple-data-objects.md).
- Configure Laravel container resolution and matching wire codecs with the [configuration and codecs guide](docs/configuration-and-codecs.md).
- [Handle failures and test without network requests](docs/error-handling-and-testing.md).

## Quick start

Install the client and standard PHP generator:

```bash
composer require php-skir/client
npm install --save-dev skir skir-php-generator
```

Create `skir-src/admin/users.skir`:

```skir
struct GetUserRequest {
  user_id: int32;
}

struct User {
  user_id: int32;
  name: string;
}

method GetUser(GetUserRequest): User = 3180856469;
```

Configure the root `skir.yml`:

```yaml
generators:
  - mod: skir-php-generator
    outDir: skir/skirout
    config:
      namespace: Skir
```

Skir owns the output directory and may replace its contents during generation.

Generate the client, configure Composer autoloading, and publish the Laravel config:

```bash
npx skir gen
npx skir-php-generator configure-composer
composer dump-autoload
php artisan vendor:publish --tag=skir-client-config
```

Configure the server endpoint and matching wire codec:

```dotenv
SKIR_CLIENT_BASE_URL=https://api.example.test
SKIR_CLIENT_ENDPOINT=/api/skir
SKIR_CLIENT_CODEC=dense_json
```

Call the service through the generated typed client:

```php
use Skir\Admin\GetUserRequest;
use Skir\Admin\SkirRpcClient;
use Skir\Client\SkirClient as TransportSkirClient;

$client = new SkirRpcClient(app(TransportSkirClient::class));
$user = $client->getUser(new GetUserRequest(userId: 42));

echo $user->name;
```

## Generator alternatives

Standard PHP is the dependency-light baseline. Use [`skir-laravel-data-generator`](docs/laravel-data.md) for Laravel Data or [`skir-simple-data-objects-generator`](docs/simple-data-objects.md) for Simple Data Objects.

## Documentation

- [Generating clients](docs/generating-clients.md)
- [Laravel Data](docs/laravel-data.md)
- [Simple Data Objects](docs/simple-data-objects.md)
- [Configuration and codecs](docs/configuration-and-codecs.md)
- [Error handling and testing](docs/error-handling-and-testing.md)
