<?php

declare(strict_types=1);

namespace Skir\Client;

use Illuminate\Support\ServiceProvider;
use Skir\Client\Codecs\SkirClientCodec;
use Skir\Client\Codecs\SkirClientCodecs;
use Skir\Client\Commands\GenerateClientCommand;
use Skir\Client\Exceptions\SkirClientException;

final class SkirClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/skir-client.php', 'skir-client');

        $this->app->bind(SkirClient::class, function (): SkirClient {
            return new SkirClient(
                baseUrl: (string) config('skir-client.base_url'),
                endpoint: (string) config('skir-client.endpoint', '/'),
                codec: $this->codecFromConfig((string) config('skir-client.codec', 'dense_json')),
            );
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

    private function codecFromConfig(string $codec): SkirClientCodec
    {
        return match ($codec) {
            'cbor' => SkirClientCodecs::cbor(),
            'dense_json' => SkirClientCodecs::denseJson(),
            'standard_json' => SkirClientCodecs::standardJson(),
            'base64_dense_json' => SkirClientCodecs::base64DenseJson(),
            default => throw SkirClientException::unsupportedCodec($codec),
        };
    }
}
