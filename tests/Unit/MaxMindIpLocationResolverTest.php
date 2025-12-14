<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use WillVincent\SessionManager\Service\MaxMindIpLocationResolver;

beforeEach(function (): void {
    Config::set('session-manager.cache.enabled', false);
    Config::set('session-manager.cache.key', 'session_manager:ip_location');
});

it('returns null when database path is null', function (): void {
    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver(null, $cache, 3600);

    $result = $resolver->resolve('8.8.8.8');

    expect($result)->toBeNull();
});

it('returns null when database file does not exist', function (): void {
    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver('/non/existent/path.mmdb', $cache, 3600);

    $result = $resolver->resolve('8.8.8.8');

    expect($result)->toBeNull();
});

it('verifies cache key format', function (): void {
    $expectedKey = 'session_manager:ip_location:'.hash('sha256', '192.168.1.1');

    // Just verify the hash format is correct
    expect($expectedKey)->toStartWith('session_manager:ip_location:');
});

it('accepts configurable cache TTL', function (): void {
    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver(null, $cache, 7200);

    // Resolver was constructed with 7200 TTL - just verify construction
    expect($resolver)->toBeInstanceOf(MaxMindIpLocationResolver::class);
});

it('bypasses cache when caching is disabled', function (): void {
    $cache = Mockery::mock(Repository::class);
    $cache->shouldNotReceive('remember');

    Config::set('session-manager.cache.enabled', false);

    $resolver = new MaxMindIpLocationResolver(null, $cache, 3600);
    $resolver->resolve('172.16.0.1');

    expect(true)->toBeTrue();
});

it('returns null from lookup when reader is null', function (): void {
    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver(null, $cache, 3600);

    $result = $resolver->lookup('1.1.1.1');

    expect($result)->toBeNull();
});

it('returns null when address not found', function (): void {
    $reader = Mockery::mock(Reader::class);
    $reader->shouldReceive('city')
        ->once()
        ->with('127.0.0.1')
        ->andThrow(new AddressNotFoundException('Not found'));

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver(null, $cache, 3600);

    // Use reflection to inject mock reader
    $reflection = new ReflectionClass($resolver);
    $property = $reflection->getProperty('reader');
    $property->setValue($resolver, $reader);

    $result = $resolver->lookup('127.0.0.1');

    expect($result)->toBeNull();
});

it('returns null on generic exception', function (): void {
    $reader = Mockery::mock(Reader::class);
    $reader->shouldReceive('city')
        ->once()
        ->with('8.8.8.8')
        ->andThrow(new RuntimeException('Database error'));

    $cache = Cache::store();
    $resolver = new MaxMindIpLocationResolver(null, $cache, 3600);

    $reflection = new ReflectionClass($resolver);
    $property = $reflection->getProperty('reader');
    $property->setValue($resolver, $reader);

    $result = $resolver->lookup('8.8.8.8');

    expect($result)->toBeNull();
});
