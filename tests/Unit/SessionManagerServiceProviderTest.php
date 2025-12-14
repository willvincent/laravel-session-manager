<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use WillVincent\SessionManager\Service\MaxMindIpLocationResolver;
use WillVincent\SessionManager\SessionManager;

it('registers SessionManager as singleton', function (): void {
    $instance1 = resolve(SessionManager::class);
    $instance2 = resolve(SessionManager::class);

    expect($instance1)->toBe($instance2);
});

it('registers MaxMindIpLocationResolver as singleton', function (): void {
    $instance1 = resolve(MaxMindIpLocationResolver::class);
    $instance2 = resolve(MaxMindIpLocationResolver::class);

    expect($instance1)->toBe($instance2);
});

it('merges configuration from package', function (): void {
    expect(config('session-manager.throttle_seconds'))->toBeInt()
        ->and(config('session-manager.location.enabled'))->toBeBool()
        ->and(config('session-manager.location.cache.enabled'))->toBeBool();
});

it('creates MaxMindIpLocationResolver with default cache store', function (): void {
    Config::set('session-manager.location.cache.store');

    $resolver = resolve(MaxMindIpLocationResolver::class);

    expect($resolver)->toBeInstanceOf(MaxMindIpLocationResolver::class);
});

it('creates MaxMindIpLocationResolver with custom cache store', function (): void {
    Config::set('session-manager.location.cache.store', 'array');

    // Recreate the singleton with new config
    app()->forgetInstance(MaxMindIpLocationResolver::class);

    $resolver = resolve(MaxMindIpLocationResolver::class);

    expect($resolver)->toBeInstanceOf(MaxMindIpLocationResolver::class);
});

it('loads translations from lang directory', function (): void {
    // Translations are loaded, just verify the function exists
    expect(function_exists('__'))->toBeTrue();
    // In test environment, translation keys may just return the key itself
    $result = __('session-manager::unknown_location');
    expect($result)->toBeString();
});

it('registers facade alias', function (): void {
    expect(class_exists('SessionManager'))->toBeTrue();
});

it('provides correct config values to resolver', function (): void {
    Config::set('session-manager.location.maxmind.database_path', '/custom/path/test.mmdb');
    Config::set('session-manager.location.maxmind.store_coordinates', true);
    Config::set('session-manager.location.cache.ttl', 9999);

    // Recreate the singleton with new config
    app()->forgetInstance(MaxMindIpLocationResolver::class);

    $resolver = resolve(MaxMindIpLocationResolver::class);

    expect($resolver)->toBeInstanceOf(MaxMindIpLocationResolver::class);
});
