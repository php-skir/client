<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Tests\Feature;

use LaravelSkir\Client\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateClientCommandTest extends TestCase
{
    #[Test]
    public function it_runs_the_skir_compiler_for_the_given_project_root(): void
    {
        $root = sys_get_temp_dir().'/skir-client-command-root';
        $compiler = sys_get_temp_dir().'/skir-client-command-compiler.php';

        if (! is_dir($root)) {
            mkdir($root, recursive: true);
        }

        file_put_contents($compiler, <<<'PHP'
<?php

if ($argv[1] !== 'gen') {
    fwrite(STDERR, 'Unexpected command.');
    exit(1);
}

if ($argv[2] !== '--root') {
    fwrite(STDERR, 'Missing root flag.');
    exit(1);
}

echo "Generated from {$argv[3]}.\n";
PHP);

        $this
            ->artisan('skir:generate-client', [
                '--root' => $root,
                '--skir-bin' => $compiler,
                '--node' => PHP_BINARY,
            ])
            ->expectsOutputToContain("Generated from {$root}.")
            ->assertExitCode(0);
    }
}
