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
        $root = $this->temporaryPath('root');
        $compiler = $this->temporaryPath('compiler.php');
        $workingDirectoryFile = $this->temporaryPath('working-directory.txt');

        mkdir($root, recursive: true);
        $resolvedRoot = (string) realpath($root);
        $exportedWorkingDirectoryFile = var_export($workingDirectoryFile, return: true);

        file_put_contents($compiler, <<<PHP
<?php

if (\$argv[1] !== 'gen') {
    fwrite(STDERR, 'Unexpected command.');
    exit(1);
}

if (\$argv[2] !== '--root') {
    fwrite(STDERR, 'Missing root flag.');
    exit(1);
}

echo "Generated from {\$argv[3]}.\n";
file_put_contents({$exportedWorkingDirectoryFile}, getcwd());
PHP);

        $this
            ->artisan('skir:generate-client', [
                '--root' => $root,
                '--skir-bin' => $compiler,
                '--node' => PHP_BINARY,
            ])
            ->expectsOutputToContain("Generated from {$resolvedRoot}.")
            ->assertExitCode(0);

        $this->assertSame($resolvedRoot, file_get_contents($workingDirectoryFile));
    }

    #[Test]
    public function it_fails_when_the_project_root_does_not_exist(): void
    {
        $root = $this->temporaryPath('missing-root');
        $compiler = $this->temporaryPath('compiler.php');

        file_put_contents($compiler, '<?php');

        $this
            ->artisan('skir:generate-client', [
                '--root' => $root,
                '--skir-bin' => $compiler,
                '--node' => PHP_BINARY,
            ])
            ->expectsOutputToContain("Skir project root does not exist: {$root}")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_the_skir_compiler_file_does_not_exist(): void
    {
        $root = $this->temporaryPath('root');
        $compiler = $this->temporaryPath('missing-compiler.php');

        mkdir($root, recursive: true);

        $this
            ->artisan('skir:generate-client', [
                '--root' => $root,
                '--skir-bin' => $compiler,
                '--node' => PHP_BINARY,
            ])
            ->expectsOutputToContain("Skir compiler file does not exist: {$compiler}")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_skir_compiler_failures(): void
    {
        $root = $this->temporaryPath('root');
        $compiler = $this->temporaryPath('compiler.php');

        mkdir($root, recursive: true);

        file_put_contents($compiler, <<<'PHP'
<?php

fwrite(STDERR, "Unable to parse skir.yml.\n");

exit(1);
PHP);

        $this
            ->artisan('skir:generate-client', [
                '--root' => $root,
                '--skir-bin' => $compiler,
                '--node' => PHP_BINARY,
            ])
            ->expectsOutputToContain('Unable to parse skir.yml.')
            ->assertExitCode(1);
    }

    private function temporaryPath(string $name): string
    {
        return sys_get_temp_dir().'/skir-client-command-'.str_replace('.', '-', uniqid($name.'-', more_entropy: true));
    }
}
