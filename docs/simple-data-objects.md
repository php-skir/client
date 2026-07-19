# Simple Data Objects client objects

Use the Simple Data Objects generator when client request and response objects should use immutable `std-out/simple-data-objects` DTOs. The shared transport and generation lifecycle are covered in [Generating clients](generating-clients.md); this guide focuses on the generator-specific setup.

This generator requires PHP 8.4 or newer.

## Install the client and generator

```bash
composer require php-skir/client std-out/simple-data-objects
npm install --save-dev skir skir-simple-data-objects-generator
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
  - mod: skir-simple-data-objects-generator
    outDir: skir/skirout
    config:
      namespace: Skir
```

Skir owns every configured `/skirout` directory and may replace its contents during generation. Keep handwritten PHP outside `skir/skirout`.

Generate the classes, configure Composer's PSR-4 mapping, and refresh the autoloader:

```bash
npx skir gen
npx skir-simple-data-objects-generator configure-composer
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

The direct `new GetUserRequestData(...)` call is a trusted construction path. The generated client serializes that existing request with `toSkirArray()` without revalidating it, then hydrates the response as `UserData` before returning it. When request data starts as an untrusted raw array, use `makeFromSkirPayload()` as shown below.

## Hydrate raw client data safely

Use the generated raw-payload constructor for untrusted named Skir input:

```php
use Skir\Admin\GetUserRequestData;

$request = GetUserRequestData::makeFromSkirPayload([
    'user_id' => 42,
]);
```

`makeFromSkirPayload()` validates the untrusted named array before recursively hydrating nested values. Inherited `from()`, `collection()`, `lazyCollection()`, and `TypedDataCollection::of()` are trusted hydration paths and do not run the generated validation rules.

Original Skir names remain the payload and serialized names, so `user_id` maps to the camel-case `$request->userId` property. The inherited immutable `with()` method also uses PHP property names and returns a new object, for example `$updated = $request->with(userId: 43)` without changing `$request`.

Supported direct arrays of structs hydrate to `TypedDataCollection` instances containing generated DTOs. Nested struct arrays, arrays of optional structs, and other unsupported collection shapes remain PHP arrays while their supported nested values are still hydrated recursively.

See the [Skir Simple Data Objects Generator](https://github.com/php-skir/skir-simple-data-objects-generator) for its full validation, collection, and generated-API reference.
