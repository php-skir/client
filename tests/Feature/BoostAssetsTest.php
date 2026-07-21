<?php

declare(strict_types=1);

namespace Skir\Client\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Skir\Client\Tests\TestCase;
use SplFileInfo;

final class BoostAssetsTest extends TestCase
{
    private const string GUIDELINE = 'resources/boost/guidelines/core.blade.php';

    private const string SKILL = 'resources/boost/skills/skir-client-development/SKILL.md';

    /** @var array<int, string> */
    private const array REFERENCES = [
        'resources/boost/skills/skir-client-development/references/generation-and-configuration.md',
        'resources/boost/skills/skir-client-development/references/invocation-errors-and-testing.md',
    ];

    #[Test]
    public function it_publishes_a_concise_client_guideline(): void
    {
        $guideline = $this->readPackageFile(self::GUIDELINE);

        $this->assertLessThanOrEqual(150, str_word_count(strip_tags($guideline)));
        $this->assertStringContainsString('php-skir/client', $guideline);
        $this->assertStringContainsString('skir-client-development', $guideline);
        $this->assertStringContainsString('/skirout', $guideline);
        $this->assertStringContainsString('generated', $guideline);
        $this->assertStringContainsString('typed client', $guideline);
    }

    #[Test]
    public function it_publishes_a_uniquely_named_client_skill_with_valid_frontmatter(): void
    {
        $skill = $this->readPackageFile(self::SKILL);
        $matched = preg_match('/\A---\R(.*?)\R---\R/s', $skill, $matches);

        $this->assertSame(1, $matched);

        $frontmatter = $matches[1];
        $this->assertLessThanOrEqual(1024, strlen($frontmatter));
        $this->assertMatchesRegularExpression('/^name: skir-client-development$/m', $frontmatter);
        $this->assertMatchesRegularExpression('/^description: Use when .+php-skir\/client\.$/m', $frontmatter);
        $this->assertSame(
            'skir-client-development',
            basename(dirname($this->packagePath(self::SKILL))),
        );
    }

    #[Test]
    public function it_bundles_every_client_skill_reference(): void
    {
        $skill = $this->readPackageFile(self::SKILL);

        foreach (self::REFERENCES as $reference) {
            $this->readPackageFile($reference);
            $this->assertStringContainsString(
                'references/'.basename($reference),
                $skill,
            );
        }
    }

    #[Test]
    public function it_keeps_client_guidance_anchored_to_the_released_package(): void
    {
        $guidance = $this->allGuidance();

        $this->assertSourceAnchorsAreDocumented(
            'src/Commands/GenerateClientCommand.php',
            ['skir:generate-client', '--root', '--skir-bin', '--node'],
            $guidance,
        );
        $this->assertSourceAnchorsAreDocumented(
            'config/skir-client.php',
            [
                'SKIR_CLIENT_BASE_URL',
                'SKIR_CLIENT_ENDPOINT',
                'SKIR_CLIENT_CODEC',
                'SKIR_CLIENT_ROOT',
                'SKIR_CLIENT_SKIR_BIN',
                'SKIR_CLIENT_NODE',
            ],
            $guidance,
        );

        foreach ([
            'configure-composer',
            'composer dump-autoload',
            'dense_json',
            'standard_json',
            'base64_dense_json',
            'cbor',
            'SkirClientException',
            'SkirRuntimeException',
            'FatalRequestException',
            'MockClient',
            'MockResponse',
            'assertSent',
            'assertSentCount',
        ] as $requiredGuidance) {
            $this->assertStringContainsString($requiredGuidance, $guidance);
        }
    }

    #[Test]
    public function it_keeps_client_guidance_portable_and_non_executable(): void
    {
        $guidance = $this->allGuidance();

        foreach ([
            '/Users/',
            '.projects/trackr',
            'make artisan',
            'make composer',
            'make npm',
            'make test',
            'CBOR encrypts',
            'CBOR is secure',
            'edit generated',
        ] as $forbiddenGuidance) {
            $this->assertStringNotContainsStringIgnoringCase($forbiddenGuidance, $guidance);
        }

        $this->assertStringContainsString('serialization', $guidance);
        $this->assertStringContainsString('Do not edit', $guidance);
        $this->assertSame(
            [self::GUIDELINE, self::SKILL, ...self::REFERENCES],
            $this->boostFiles(),
        );
    }

    #[Test]
    public function it_does_not_require_laravel_boost(): void
    {
        $composer = json_decode(
            $this->readPackageFile('composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertIsArray($composer);
        $this->assertArrayNotHasKey('laravel/boost', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('laravel/boost', $composer['require-dev'] ?? []);
    }

    private function allGuidance(): string
    {
        $guidanceFiles = [self::GUIDELINE, self::SKILL, ...self::REFERENCES];

        return implode("\n", array_map($this->readPackageFile(...), $guidanceFiles));
    }

    /**
     * @param  array<int, string>  $anchors
     */
    private function assertSourceAnchorsAreDocumented(string $sourceFile, array $anchors, string $guidance): void
    {
        $source = $this->readPackageFile($sourceFile);

        foreach ($anchors as $anchor) {
            $this->assertStringContainsString($anchor, $source);
            $this->assertStringContainsString($anchor, $guidance);
        }
    }

    /** @return array<int, string> */
    private function boostFiles(): array
    {
        $boostPath = $this->packagePath('resources/boost');
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($boostPath, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if (! $file->isFile()) {
                continue;
            }

            $files[] = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($file->getPathname(), strlen($this->packagePath()) + 1),
            );
        }

        sort($files);

        return $files;
    }

    private function readPackageFile(string $relativePath): string
    {
        $absolutePath = $this->packagePath($relativePath);

        $this->assertFileExists($absolutePath);
        $this->assertIsReadable($absolutePath);

        $contents = file_get_contents($absolutePath);

        $this->assertIsString($contents);
        $this->assertNotSame('', trim($contents));

        return $contents;
    }

    private function packagePath(string $relativePath = ''): string
    {
        return implode(DIRECTORY_SEPARATOR, array_filter([
            dirname(__DIR__, 2),
            $relativePath,
        ]));
    }
}
