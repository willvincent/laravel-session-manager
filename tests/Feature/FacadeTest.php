<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use WillVincent\SessionManager\Facades\SessionManager;

it('can use facade to get user sessions', function (): void {
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

    $sessions = SessionManager::getUserSessions();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->id)->toBe('session-123');
});

it('can use facade to destroy other sessions', function (): void {
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

    $deleted = SessionManager::logoutOtherSessions();

    expect($deleted)->toBe(1)
        ->and(DB::table('sessions')->count())->toBe(1);
});

it('can use facade to destroy all sessions', function (): void {
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

    $deleted = SessionManager::logoutAllSessions();

    expect($deleted)->toBe(2)
        ->and(DB::table('sessions')->count())->toBe(0);
});

it('can use facade to destroy specific session', function (): void {
    DB::table('sessions')->insert([
        'id' => 'session-123',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $result = SessionManager::logoutSession('session-123');

    expect($result)->toBeTrue()
        ->and(DB::table('sessions')->count())->toBe(0);
});

it('can use facade to get session count', function (): void {
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

    $count = SessionManager::getSessionCount();

    expect($count)->toBe(2);
});

it('can use facade with specific user id', function (): void {
    Session::shouldReceive('getId')->andReturn('session-999');

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

    $sessions = SessionManager::getUserSessions(1);
    $count = SessionManager::getSessionCount(2);

    expect($sessions)->toHaveCount(1)
        ->and($count)->toBe(1);
});

it('facade is properly registered', function (): void {
    expect(class_exists('SessionManager'))->toBeTrue();
});
