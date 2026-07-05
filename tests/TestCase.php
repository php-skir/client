<?php

declare(strict_types=1);

namespace LaravelSkir\Client\Tests;

use LaravelSkir\Client\SkirClientServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

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
