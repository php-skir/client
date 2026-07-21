## php-skir/client

`php-skir/client` provides Laravel transport and generation support for typed SkirRPC clients. Treat
the Skir schema and `skir.yml` as the source of truth. Every configured `/skirout` directory is
generator-owned and may be replaced; do not edit files inside it.

Prefer the generated typed client for application code. Use `Skir\Client\SkirClient::invoke()` only
for debugging or custom transport integrations. Keep the base URL, endpoint, and codec aligned with
the server. CBOR is a compact serialization format, not encryption or confidentiality.

Use the `skir-client-development` skill whenever generating or configuring a client, diagnosing
transport failures, choosing a codec, or writing client tests. Client tests must use Saloon mocks
and must not contact a real server.
