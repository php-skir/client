![Laravel Skir Client](art/banner.png)

# Laravel Skir Client

[![Tests](https://github.com/php-skir/client/actions/workflows/tests.yml/badge.svg)](https://github.com/php-skir/client/actions/workflows/tests.yml)
[![Coverage](https://raw.githubusercontent.com/php-skir/client/badges/coverage.svg)](https://github.com/php-skir/client/actions/workflows/tests.yml)
[![Composer](https://img.shields.io/packagist/v/php-skir/client?label=composer&logo=composer)](https://packagist.org/packages/php-skir/client)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777BB4?logo=php&logoColor=white)](https://packagist.org/packages/php-skir/client)
[![License](https://img.shields.io/github/license/php-skir/client)](LICENSE)

Call [SkirRPC](https://skir.build/docs/skirrpc) services from Laravel through generated, typed PHP clients. Requests go out over [Saloon](https://docs.saloon.dev/) using the standard SkirRPC protocol, so the server can be [`php-skir/server`](https://github.com/php-skir/server) or a Skir service written in any other language.

**Requirements:** PHP 8.3+, Laravel 12 or 13, and Node.js for the Skir compiler.

## Features

- Generate typed RPC clients from Skir schemas with the [client generation guide](docs/generating-clients.md).
- Use standard PHP objects, [Laravel Data](docs/laravel-data.md), or [Simple Data Objects](docs/simple-data-objects.md).
- Configure the server URL, endpoint, and authentication with the [configuration guide](docs/configuration.md).
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
npx skir gen                               # or: php artisan skir:generate-client
npx skir-php-generator configure-composer
composer dump-autoload
php artisan vendor:publish --tag=skir-client-config
```

Configure the server endpoint:

```dotenv
SKIR_CLIENT_BASE_URL=https://api.example.test
SKIR_CLIENT_ENDPOINT=/api/skir
```

If the server expects a credential, configure it in the same file. For example, to send a shared secret in a custom header:

```dotenv
SKIR_CLIENT_AUTH_TYPE=header
SKIR_CLIENT_AUTH_HEADER=X-Internal-Token
SKIR_CLIENT_AUTH_TOKEN=your-shared-secret
```

Every request then carries `X-Internal-Token: your-shared-secret`. Bearer, basic, and custom authentication are covered in the [configuration guide](docs/configuration.md#authentication).

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
- [Configuration](docs/configuration.md)
- [Error handling and testing](docs/error-handling-and-testing.md)
