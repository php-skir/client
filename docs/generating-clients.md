# Generating clients

Use the standard PHP generator to turn a Skir schema into typed request, response, and RPC client classes.

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

Configure the generator in the project-root `skir.yml`:

```yaml
generators:
  - mod: skir-php-generator
    outDir: skir/skirout
    config:
      namespace: Skir
```

Every configured `outDir` is owned by Skir, must end in `/skirout`, and may be replaced during generation. Keep handwritten PHP outside these directories.

The source directory becomes part of the generated namespace: `skir-src/admin/` produces `Skir\Admin`. The schema filename, `users.skir` in this example, does not add a namespace segment.

## Generate and configure autoloading

Run the direct generation workflow from the project root:

```bash
npx skir gen
npx skir-php-generator configure-composer
composer dump-autoload
```

`npx skir gen` is the direct generation path. `configure-composer` reads the generator entry and adds the matching PSR-4 mapping to `composer.json`; it does not run Composer, so `composer dump-autoload` remains a separate step.

Laravel applications may use the optional wrapper instead of running `npx skir gen` directly:

```bash
php artisan skir:generate-client
```

The wrapper accepts `--root`, `--skir-bin`, and `--node`. Example environment overrides:

```dotenv
SKIR_CLIENT_ROOT=/path/to/laravel-project
SKIR_CLIENT_SKIR_BIN=/path/to/laravel-project/node_modules/skir/dist/compiler.js
SKIR_CLIENT_NODE=node
```

`SKIR_CLIENT_ROOT` defaults to Laravel's `base_path()`, `SKIR_CLIENT_SKIR_BIN` defaults to `base_path('node_modules/skir/dist/compiler.js')`, and `SKIR_CLIENT_NODE` defaults to `node`. Command options override these values. The command runs the configured Node executable and compiler with `gen --root <root>` and streams compiler output to the terminal.

When `--root` points to another project, you may also need `--skir-bin`: the configured compiler path remains based on the Laravel application unless you override it.

The wrapper only replaces the first command. Run `configure-composer` and `composer dump-autoload` afterward when Composer mapping still needs to be configured.

## Call the typed client

The generated `Skir\Admin\SkirRpcClient` wraps the transport client. Its `getUser()` method converts the request with `$request->toArray()` and restores the response with `User::fromArray()`.

```php
use Skir\Admin\GetUserRequest;
use Skir\Admin\SkirRpcClient;
use Skir\Admin\User;
use Skir\Client\SkirClient as TransportSkirClient;

$client = new SkirRpcClient(app(TransportSkirClient::class));
$user = $client->getUser(new GetUserRequest(userId: 42));

assert($user instanceof User);
echo $user->name;
```

For debugging or custom integrations, invoke the transport directly:

```php
use Skir\Admin\GetUserRequest;
use Skir\Admin\SkirMethods;
use Skir\Client\SkirClient;

$request = new GetUserRequest(userId: 42);

$response = app(SkirClient::class)->invoke(
    SkirMethods::getUser(),
    $request->toArray(),
);
```

Prefer the generated client for application code because it restores the typed `User` response; the low-level call returns the decoded transport value.

See the [standard PHP generator repository](https://github.com/php-skir/skir-php-generator) for the full generator reference.
