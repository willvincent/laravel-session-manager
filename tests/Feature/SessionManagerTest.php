<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use WillVincent\SessionManager\Data\SessionDevice;
use WillVincent\SessionManager\SessionManager;

beforeEach(function (): void {
    $this->sessionManager = resolve(SessionManager::class);
});

it('returns empty collection when user is not authenticated', function (): void {
    $sessions = $this->sessionManager->getUserSessions();

    expect($sessions)->toBeEmpty();
});

it('returns empty collection when user has no sessions', function (): void {
    Auth::shouldReceive('id')->andReturn(1);

    $sessions = $this->sessionManager->getUserSessions();

    expect($sessions)->toBeEmpty();
});

it('retrieves sessions for authenticated user', function (): void {
    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-123');

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessions = $this->sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->id)->toBe('session-123')
        ->and($sessions->first()->ip_address)->toBe('127.0.0.1')
        ->and($sessions->first()->is_current)->toBeTrue();
});

it('retrieves sessions for specific user', function (): void {
    Session::shouldReceive('getId')->andReturn('session-456');

    DB::table('sessions')->insert([
        [
            'id' => 'session-123',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-456',
            'user_id' => 2,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/90.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    $sessions = $this->sessionManager->getUserSessions(1);

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->id)->toBe('session-123');
});

it('parses user agent information correctly', function (): void {
    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-123');

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessions = $this->sessionManager->getUserSessions();

    $device = $sessions->first()->device;
    expect($device)->toBeInstanceOf(SessionDevice::class)
        ->and($device->browser)->toBeString()
        ->and($device->platform)->toBeString()
        ->and($device->is_desktop)->toBeBool()
        ->and($device->is_mobile)->toBeBool();
});

it('orders sessions by last activity descending', function (): void {
    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-1');

    $now = time();

    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now - 100,
        ],
        [
            'id' => 'session-2',
            'user_id' => 1,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now,
        ],
        [
            'id' => 'session-3',
            'user_id' => 1,
            'ip_address' => '127.0.0.3',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now - 200,
        ],
    ]);

    $sessions = $this->sessionManager->getUserSessions();

    expect($sessions)->toHaveCount(3)
        ->and($sessions->first()->id)->toBe('session-2')
        ->and($sessions->last()->id)->toBe('session-3');
});

it('destroys other sessions but keeps current', function (): void {
    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-current');

    DB::table('sessions')->insert([
        [
            'id' => 'session-current',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-old-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time() - 100,
        ],
        [
            'id' => 'session-old-2',
            'user_id' => 1,
            'ip_address' => '127.0.0.3',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time() - 200,
        ],
    ]);

    $deleted = $this->sessionManager->logoutOtherSessions();

    expect($deleted)->toBe(2)
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'session-current')->exists())->toBeTrue();
});

it('destroys all sessions including current', function (): void {
    Auth::shouldReceive('id')->andReturn(1);
    Session::shouldReceive('getId')->andReturn('session-current');

    DB::table('sessions')->insert([
        [
            'id' => 'session-current',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-old',
            'user_id' => 1,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time() - 100,
        ],
    ]);

    $deleted = $this->sessionManager->logoutAllSessions();

    expect($deleted)->toBe(2)
        ->and(DB::table('sessions')->count())->toBe(0);
});

it('destroys sessions for specific user', function (): void {
    Session::shouldReceive('getId')->andReturn('session-456');

    DB::table('sessions')->insert([
        [
            'id' => 'session-123',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-456',
            'user_id' => 2,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/90.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    $deleted = $this->sessionManager->logoutOtherSessions(1);

    expect($deleted)->toBe(1)
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('user_id', 2)->exists())->toBeTrue();
});

it('returns zero when destroying sessions for unauthenticated user', function (): void {
    Auth::shouldReceive('id')->andReturn(null);

    $deleted = $this->sessionManager->logoutOtherSessions();

    expect($deleted)->toBe(0);
});

it('destroys specific session by id', function (): void {
    DB::table('sessions')->insert([
        [
            'id' => 'session-123',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-456',
            'user_id' => 1,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/90.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    $result = $this->sessionManager->logoutSession('session-123');

    expect($result)->toBeTrue()
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'session-123')->exists())->toBeFalse();
});

it('verifies user ownership when destroying specific session', function (): void {
    DB::table('sessions')->insert([
        [
            'id' => 'session-123',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-456',
            'user_id' => 2,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/90.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    // Try to delete user 2's session while specifying user 1
    $result = $this->sessionManager->logoutSession('session-456', 1);

    expect($result)->toBeFalse()
        ->and(DB::table('sessions')->count())->toBe(2);
});

it('returns false when destroying non-existent session', function (): void {
    $result = $this->sessionManager->logoutSession('non-existent');

    expect($result)->toBeFalse();
});

it('counts sessions for authenticated user', function (): void {
    Auth::shouldReceive('id')->andReturn(1);

    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-2',
            'user_id' => 1,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    $count = $this->sessionManager->getSessionCount();

    expect($count)->toBe(2);
});

it('counts sessions for specific user', function (): void {
    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-2',
            'user_id' => 2,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ],
    ]);

    $count = $this->sessionManager->getSessionCount(1);

    expect($count)->toBe(1);
});

it('returns zero when counting sessions for unauthenticated user', function (): void {
    Auth::shouldReceive('id')->andReturn(null);

    $count = $this->sessionManager->getSessionCount();

    expect($count)->toBe(0);
});

it('handles uuid user ids', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => $uuid,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $sessions = $this->sessionManager->getUserSessions($uuid);

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->id)->toBe('session-123');
});

it('handles string user ids', function (): void {
    $stringId = 'user_abc123';
    Session::shouldReceive('getId')->andReturn('session-999');

    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => $stringId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $deleted = $this->sessionManager->logoutOtherSessions($stringId);

    expect($deleted)->toBe(1);
});
