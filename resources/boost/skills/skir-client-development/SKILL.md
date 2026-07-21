---
name: skir-client-development
description: Use when generating, configuring, invoking, debugging, or testing typed SkirRPC clients in Laravel applications using php-skir/client.
---

# Skir Client Development

Keep the Skir schema authoritative, keep handwritten code outside `/skirout`, and prefer generated
typed clients over direct transport calls.

## Route the task

- Generation, generator configuration, Composer autoloading, Laravel config, or environment setup:
  read [generation and configuration](references/generation-and-configuration.md).
- Invocation, codecs, exceptions, debugging, recovery, or network-free tests: read
  [invocation, errors, and testing](references/invocation-errors-and-testing.md).
- Tasks spanning both areas: read both references before changing code.

## Workflow

1. Inspect the consuming application's `skir.yml`, schema, generated namespace, and current package
   configuration. Never invent method numbers or command options.
2. Read only the relevant bundled reference, then verify exact behavior against the installed
   package source when versions may differ.
3. Change schema, supported generator configuration, or handwritten application code. Do not edit
   anything under a generated `/skirout` directory.
4. Test typed success and applicable failure boundaries without a real network call.
