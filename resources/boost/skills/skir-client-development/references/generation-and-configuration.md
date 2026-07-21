# Generation and Configuration

## Ownership

Define contracts in the Skir schema and configure generators in the project-root `skir.yml`. Every
generator `outDir` must end in `/skirout`; Skir owns that directory and may replace it. Keep adapters,
authentication integration, and other handwritten PHP outside it.

## Generate

From the consuming project root, the complete direct workflow is:

```bash
npx skir gen
npx skir-php-generator configure-composer
composer dump-autoload
```

`configure-composer` adds the generated PSR-4 mapping but does not refresh Composer autoloading.

Laravel applications may replace only `npx skir gen` with:

```bash
php artisan skir:generate-client
```

The supported options are `--root`, `--skir-bin`, and `--node`. The wrapper runs the configured Node
executable and compiler as `gen --root <root>`, streams output, and has no process timeout. It does
not run `configure-composer` or `composer dump-autoload`.

Generator configuration uses:

| Environment key | Laravel config key | Default |
|---|---|---|
| `SKIR_CLIENT_ROOT` | `skir-client.generator.root` | Application `base_path()` |
| `SKIR_CLIENT_SKIR_BIN` | `skir-client.generator.skir_bin` | `node_modules/skir/dist/compiler.js` |
| `SKIR_CLIENT_NODE` | `skir-client.generator.node` | `node` |

Command options override configuration. If `--root` points to another project, verify `--skir-bin`
points to that project's compiler too. On failure, inspect the reported missing root/compiler path or
the compiler's stderr before retrying.

## Configure Laravel transport

Publish the package configuration when the application needs a local copy:

```bash
php artisan vendor:publish --tag=skir-client-config
```

Set:

```dotenv
SKIR_CLIENT_BASE_URL=https://api.example.test
SKIR_CLIENT_ENDPOINT=/api/skir
SKIR_CLIENT_CODEC=dense_json
```

`SKIR_CLIENT_BASE_URL` is required. `SKIR_CLIENT_ENDPOINT` defaults to `/`; do not duplicate the
endpoint path in the base URL. `SKIR_CLIENT_CODEC` defaults to `dense_json` and must match the server.

Do not substitute repository-local wrapper commands or machine-specific absolute paths in reusable
package guidance. Use the consuming application's documented runtime surface when executing these
commands.
