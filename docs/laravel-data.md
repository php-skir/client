# Laravel Data client objects

Use the Laravel Data generator when client request and response objects should integrate with `spatie/laravel-data`. The transport and generation lifecycle remain the same as in [Generating clients](generating-clients.md); this guide covers the generator-specific setup.

## Install the client and generator

```bash
composer require php-skir/client spatie/laravel-data
npm install --save-dev skir skir-laravel-data-generator
```

## Define the service contract

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

Directories below `skir-src` become PHP namespace segments, so `skir-src/admin/users.skir` produces classes in `Skir\Admin`. The `users.skir` filename does not add another namespace segment.

## Configure and generate

Add the generator to the project-root `skir.yml`:

```yaml
generators:
  - mod: skir-laravel-data-generator
    outDir: skir/skirout
    config:
      namespace: Skir
```

Skir owns every configured `/skirout` directory and may replace its contents during generation. Keep handwritten PHP outside `skir/skirout`.

Generate the classes, configure Composer's PSR-4 mapping, and refresh the autoloader:

```bash
npx skir gen
npx skir-laravel-data-generator configure-composer
composer dump-autoload
```

## Call the typed client

Configure the transport endpoint and matching codec as described in [Configuration and codecs](configuration-and-codecs.md), then use the generated client:

```php
use Skir\Admin\GetUserRequestData;
use Skir\Admin\SkirRpcClient;
use Skir\Client\SkirClient as TransportSkirClient;

$client = new SkirRpcClient(app(TransportSkirClient::class));
$user = $client->getUser(new GetUserRequestData(userId: 42));

echo $user->name;
```

The generated `getUser()` method serializes `GetUserRequestData` to the named Skir payload and hydrates the response through the generated `UserData` class. Laravel Data validation therefore runs while the response is hydrated.

Original Skir names remain the wire contract. During hydration, `#[MapInputName('user_id')]` maps the `user_id` wire field to the camel-case `$userId` PHP property; generated `toSkirArray()` writes `user_id` back to the wire payload. Direct arrays of structs receive Laravel Data collection metadata where supported, allowing their items to hydrate as generated Data objects; unsupported nested or nullable-item collection shapes remain arrays and are converted recursively.

## Add a validation overlay

Generator validation selectors use the original Skir module, record, and field names. For example, limit the returned user's `name` without editing generated PHP:

```yaml
generators:
  - mod: skir-laravel-data-generator
    outDir: skir/skirout
    config:
      namespace: Skir
      validation:
        admin/users.skir:
          User:
            name:
              - max:100
```

Validation failures occur when a generated Data class hydrates a raw request or response through `makeFromSkirPayload()`. In this journey, a response whose `name` exceeds 100 characters fails while `getUser()` hydrates `UserData`; the failure is not deferred until the property is read. A raw request passed through `GetUserRequestData::makeFromSkirPayload()` follows the same rule, while the generated client does not rehydrate an already constructed request object.

See the [Skir Laravel Data Generator](https://github.com/php-skir/skir-laravel-data-generator) for its full validation and generated-API reference.
