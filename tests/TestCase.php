<?php

declare(strict_types=1);

namespace Skir\Client\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Skir\Client\SkirClientServiceProvider;

abstract class TestCase extends Orchestra
{
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
