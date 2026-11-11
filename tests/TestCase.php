<?php

declare(strict_types=1);

namespace Skir\Client\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Skir\Client\SkirClientServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function tearDown(): void
    {
        SkirClientServiceProvider::useConfig('skir-client');

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SkirClientServiceProvider::class,
        ];
    }
}
