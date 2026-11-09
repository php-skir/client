<?php

declare(strict_types=1);

namespace Skir\Client;

use Illuminate\Support\ServiceProvider;
use Skir\Client\Commands\GenerateClientCommand;
use Skir\Client\Exceptions\SkirClientException;

final class SkirClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/skir-client.php', 'skir-client');

        $this->app->bind(SkirClient::class, function (): SkirClient {
            $baseUrl = config('skir-client.base_url');

            if (! is_string($baseUrl) || $baseUrl === '') {
                throw SkirClientException::missingBaseUrl();
            }

            return new SkirClient($baseUrl, (string) config('skir-client.endpoint'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/skir-client.php' => config_path('skir-client.php'),
        ], 'skir-client-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateClientCommand::class,
            ]);
        }
    }
}
