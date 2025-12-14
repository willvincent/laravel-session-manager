<?php

declare(strict_types=1);

namespace WillVincent\SessionManager;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use WillVincent\SessionManager\Contracts\IpLocationResolver;
use WillVincent\SessionManager\Service\MaxMindIpLocationResolver;

final class SessionManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/session-manager.php',
            'session-manager'
        );

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'session-manager');

        $this->app->singleton(IpLocationResolver::class, function (): IpLocationResolver {
            /** @var array{maxmind: array{database_path: string|null, store_coordinates: bool}, cache: array{store: string|null, ttl: int}} $config */
            $config = config('session-manager.location');

            $cacheStoreName = $config['cache']['store'];
            $cacheStore = $cacheStoreName !== null
                ? Cache::store($cacheStoreName)
                : Cache::store();

            return new MaxMindIpLocationResolver(
                databasePath: $config['maxmind']['database_path'],
                cache: $cacheStore,
                cacheTtl: $config['cache']['ttl'],
                storeCoordinates: $config['maxmind']['store_coordinates'],
            );
        });

        // Backwards compat: allow resolving the concrete class
        $this->app->alias(IpLocationResolver::class, MaxMindIpLocationResolver::class);

        $this->app->singleton(SessionManager::class);

        // Register the facade alias
        $loader = AliasLoader::getInstance();
        $loader->alias('SessionManager', Facades\SessionManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadTranslationsFrom(__DIR__.'/../lang', 'session-manager');
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/session-manager'),
            ], 'session-manager-lang');

            $this->publishes([
                __DIR__.'/../config/session-manager.php' => config_path('session-manager.php'),
            ], 'session-manager-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'session-manager-migrations');
        }
    }
}
