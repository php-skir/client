<?php

declare(strict_types=1);

namespace Skir\Client;

use Illuminate\Support\ServiceProvider;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Skir\Client\Commands\GenerateClientCommand;
use Skir\Client\Exceptions\SkirClientException;

final class SkirClientServiceProvider extends ServiceProvider
{
    private static string $configKey = 'skir-client';

    public static function useConfig(string $key): void
    {
        self::$configKey = $key;
    }

    public static function configKey(): string
    {
        return self::$configKey;
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/skir-client.php', 'skir-client');

        $this->app->bind(SkirClient::class, function (): SkirClient {
            $key = self::$configKey;
            $baseUrl = config("{$key}.base_url");

            if (! is_string($baseUrl) || $baseUrl === '') {
                throw SkirClientException::missingBaseUrl($key);
            }

            $format = (string) config("{$key}.format", 'dense');

            $client = new SkirClient(
                $baseUrl,
                (string) config("{$key}.endpoint", '/'),
                WireFormat::tryFrom($format) ?? throw SkirClientException::unsupportedFormat($format),
            );

            $authenticator = $this->authenticator($key);

            return $authenticator === null ? $client : $client->withAuthenticator($authenticator);
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

    private function authenticator(string $key): ?Authenticator
    {
        $type = config("{$key}.auth.type");

        return match ($type) {
            null, '' => null,
            'header' => new HeaderAuthenticator(
                $this->credential($key, 'token', 'SKIR_CLIENT_AUTH_TOKEN'),
                $this->credential($key, 'header', 'SKIR_CLIENT_AUTH_HEADER'),
            ),
            'bearer' => new TokenAuthenticator($this->credential($key, 'token', 'SKIR_CLIENT_AUTH_TOKEN')),
            'basic' => new BasicAuthenticator(
                $this->credential($key, 'username', 'SKIR_CLIENT_AUTH_USERNAME'),
                $this->credential($key, 'password', 'SKIR_CLIENT_AUTH_PASSWORD'),
            ),
            default => throw SkirClientException::unsupportedAuthType((string) $type),
        };
    }

    private function credential(string $key, string $field, string $env): string
    {
        $value = config("{$key}.auth.{$field}");

        if (! is_string($value) || $value === '') {
            throw SkirClientException::missingAuthCredential("{$key}.auth.{$field}", $env);
        }

        return $value;
    }
}
