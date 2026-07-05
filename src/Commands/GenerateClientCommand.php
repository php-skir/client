<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class GenerateClientCommand extends Command
{
    protected $signature = 'skir:generate-client
        {--root= : Path to the Skir project root}
        {--skir-bin= : Path to the Skir compiler JavaScript file}
        {--node= : Node executable}';

    protected $description = 'Generate Skir client code using the configured Skir compiler.';

    public function handle(): int
    {
        $root = (string) ($this->option('root') ?: config('skir-client.generator.root'));
        $skirBin = (string) ($this->option('skir-bin') ?: config('skir-client.generator.skir_bin'));
        $node = (string) ($this->option('node') ?: config('skir-client.generator.node', 'node'));

        $process = new Process([$node, $skirBin, 'gen', '--root', $root]);
        $process->setTimeout(null);

        try {
            $process->mustRun(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });
        } catch (ProcessFailedException $exception) {
            $this->error($exception->getProcess()->getErrorOutput() ?: $exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
