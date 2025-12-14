<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use WillVincent\SessionManager\Data\SessionLocation;
use WillVincent\SessionManager\Enums\LocationConfidence;
use WillVincent\SessionManager\Service\MaxMindIpLocationResolver;

beforeEach(function (): void {
    Config::set('session-manager.cache.enabled', true);
    Config::set('session-manager.cache.key', 'session_manager:ip_location');
});

it('resolves location with high confidence when city and region available', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600);

    // IP from MaxMind test data that has city and region
    $result = $resolver->lookup('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class)
        ->and($result->city)->not->toBeNull()
        ->and($result->confidence)->toBeInstanceOf(LocationConfidence::class);
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('resolves location with medium confidence when only city available', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600);

    $result = $resolver->lookup('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class)
        ->and($result->countryCode)->not->toBeNull();
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('stores coordinates when enabled', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600, storeCoordinates: true);

    $result = $resolver->lookup('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class)
        ->and($result->latitude)->not->toBeNull()
        ->and($result->longitude)->not->toBeNull();
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('does not store coordinates when disabled', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600, storeCoordinates: false);

    $result = $resolver->lookup('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class)
        ->and($result->latitude)->toBeNull()
        ->and($result->longitude)->toBeNull();
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('uses cache when resolve is called', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600);

    // First call - should hit reader
    $result1 = $resolver->resolve('81.2.69.142');
    // Second call - should use cache
    $result2 = $resolver->resolve('81.2.69.142');

    expect($result1)->toBeInstanceOf(SessionLocation::class)
        ->and($result2)->toBeInstanceOf(SessionLocation::class)
        ->and($result1->city)->toBe($result2->city);
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('determines confidence level based on available data', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600);

    $result = $resolver->lookup('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class)
        ->and($result->confidence)->toBeInstanceOf(LocationConfidence::class)
        ->and($result->source)->toBe('maxmind');
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');

it('handles cache bypass when disabled', function (): void {
    $dbPath = __DIR__.'/../GeoLite2-City.mmdb';

    if (! file_exists($dbPath)) {
        $this->markTestSkipped('MaxMind database not available');
    }

    Config::set('session-manager.cache.enabled', false);

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver($dbPath, $cache, 3600);

    $result = $resolver->resolve('81.2.69.142');

    expect($result)->toBeInstanceOf(SessionLocation::class);
})->skip(! file_exists(__DIR__.'/../GeoLite2-City.mmdb'), 'MaxMind database not available');
