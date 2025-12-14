<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use WillVincent\SessionManager\Data\SessionLocation;
use WillVincent\SessionManager\Enums\LocationConfidence;
use WillVincent\SessionManager\Service\MaxMindIpLocationResolver;
use WillVincent\SessionManager\SessionManager;

it('does not resolve location when disabled', function (): void {
    Config::set('session-manager.location.enabled', false);

    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-123');

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => 1,
        'ip_address' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessionManager = new SessionManager();
    $sessions = $sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->location)->toBeNull();
});

it('resolves location when enabled', function (): void {
    Config::set('session-manager.location.enabled', true);

    $mockLocation = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'Mountain View',
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    $mockResolver = Mockery::mock(MaxMindIpLocationResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->with('8.8.8.8')
        ->andReturn($mockLocation);

    app()->instance(MaxMindIpLocationResolver::class, $mockResolver);

    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-456');

    DB::table('sessions')->insert([
        'id' => 'session-456',
        'user_id' => 1,
        'ip_address' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessionManager = new SessionManager();
    $sessions = $sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->location)->toBeInstanceOf(SessionLocation::class)
        ->and($sessions->first()->location->city)->toBe('Mountain View')
        ->and($sessions->first()->location->countryCode)->toBe('US');
});

it('handles null location from resolver', function (): void {
    Config::set('session-manager.location.enabled', true);

    $mockResolver = Mockery::mock(MaxMindIpLocationResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->with('127.0.0.1')
        ->andReturn(null);

    app()->instance(MaxMindIpLocationResolver::class, $mockResolver);

    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-789');

    DB::table('sessions')->insert([
        'id' => 'session-789',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessionManager = new SessionManager();
    $sessions = $sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->location)->toBeNull();
});

it('resolves location for multiple sessions', function (): void {
    Config::set('session-manager.location.enabled', true);

    $location1 = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'San Francisco',
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    $location2 = new SessionLocation(
        countryCode: 'GB',
        region: 'England',
        city: 'London',
        timezone: 'Europe/London',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    $mockResolver = Mockery::mock(MaxMindIpLocationResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->with('1.2.3.4')
        ->andReturn($location1);
    $mockResolver->shouldReceive('resolve')
        ->with('5.6.7.8')
        ->andReturn($location2);

    app()->instance(MaxMindIpLocationResolver::class, $mockResolver);

    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-1');

    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => 1,
            'ip_address' => '1.2.3.4',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-2',
            'user_id' => 1,
            'ip_address' => '5.6.7.8',
            'user_agent' => 'Chrome/90.0',
            'payload' => 'test-payload',
            'last_activity' => time() - 100,
        ],
    ]);

    $sessionManager = new SessionManager();
    $sessions = $sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(2)
        ->and($sessions->first()->location->city)->toBe('San Francisco')
        ->and($sessions->last()->location->city)->toBe('London');
});

it('resolves location with different confidence levels', function (): void {
    Config::set('session-manager.location.enabled', true);

    $lowConfidenceLocation = new SessionLocation(
        countryCode: 'DE',
        region: null,
        city: null,
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::LOW,
    );

    $mockResolver = Mockery::mock(MaxMindIpLocationResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->with('9.9.9.9')
        ->andReturn($lowConfidenceLocation);

    app()->instance(MaxMindIpLocationResolver::class, $mockResolver);

    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-low');

    DB::table('sessions')->insert([
        'id' => 'session-low',
        'user_id' => 1,
        'ip_address' => '9.9.9.9',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessionManager = new SessionManager();
    $sessions = $sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->location->confidence)->toBe(LocationConfidence::LOW)
        ->and($sessions->first()->location->city)->toBeNull()
        ->and($sessions->first()->location->countryCode)->toBe('DE');
});
