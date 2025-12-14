<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Config::set('session.lifetime', 120);
});

it('prunes sessions older than default TTL', function (): void {
    $now = now();

    // Create expired session (older than 120 minutes)
    DB::table('sessions')->insert([
        'id' => 'expired-session',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(121)->getTimestamp(),
    ]);

    // Create active session (within TTL)
    DB::table('sessions')->insert([
        'id' => 'active-session',
        'user_id' => 2,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(60)->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions')
        ->assertSuccessful()
        ->expectsOutput('Pruned 1 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'active-session')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'expired-session')->exists())->toBeFalse();
});

it('prunes sessions using custom TTL', function (): void {
    $now = now();

    // Create session that's 61 minutes old
    DB::table('sessions')->insert([
        'id' => 'session-61-min',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(61)->getTimestamp(),
    ]);

    // Create session that's 59 minutes old
    DB::table('sessions')->insert([
        'id' => 'session-59-min',
        'user_id' => 2,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(59)->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions', ['--ttl' => 60])
        ->assertSuccessful()
        ->expectsOutput('Pruned 1 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'session-59-min')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'session-61-min')->exists())->toBeFalse();
});

it('performs dry run without deleting sessions', function (): void {
    $now = now();

    // Create multiple expired sessions
    DB::table('sessions')->insert([
        [
            'id' => 'expired-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(200)->getTimestamp(),
        ],
        [
            'id' => 'expired-2',
            'user_id' => 2,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(150)->getTimestamp(),
        ],
    ]);

    $this->artisan('session-manager:prune-sessions', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutput('[DRY RUN] 2 expired sessions would be pruned.');

    // All sessions should still exist
    expect(DB::table('sessions')->count())->toBe(2)
        ->and(DB::table('sessions')->where('id', 'expired-1')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'expired-2')->exists())->toBeTrue();
});

it('handles no expired sessions gracefully', function (): void {
    $now = now();

    // Create only active sessions
    DB::table('sessions')->insert([
        [
            'id' => 'active-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(30)->getTimestamp(),
        ],
        [
            'id' => 'active-2',
            'user_id' => 2,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(60)->getTimestamp(),
        ],
    ]);

    $this->artisan('session-manager:prune-sessions')
        ->assertSuccessful()
        ->expectsOutput('No expired sessions found.');

    expect(DB::table('sessions')->count())->toBe(2);
});

it('handles no sessions in database', function (): void {
    $this->artisan('session-manager:prune-sessions')
        ->assertSuccessful()
        ->expectsOutput('No expired sessions found.');

    expect(DB::table('sessions')->count())->toBe(0);
});

it('prunes multiple expired sessions', function (): void {
    $now = now();

    // Create 5 expired sessions
    for ($i = 1; $i <= 5; $i++) {
        DB::table('sessions')->insert([
            'id' => "expired-{$i}",
            'user_id' => $i,
            'ip_address' => "127.0.0.{$i}",
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(150)->getTimestamp(),
        ]);
    }

    // Create 3 active sessions
    for ($i = 6; $i <= 8; $i++) {
        DB::table('sessions')->insert([
            'id' => "active-{$i}",
            'user_id' => $i,
            'ip_address' => "127.0.0.{$i}",
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(30)->getTimestamp(),
        ]);
    }

    $this->artisan('session-manager:prune-sessions')
        ->assertSuccessful()
        ->expectsOutput('Pruned 5 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(3);

    // Verify only active sessions remain
    for ($i = 6; $i <= 8; $i++) {
        expect(DB::table('sessions')->where('id', "active-{$i}")->exists())->toBeTrue();
    }
});

it('prunes sessions exactly at TTL boundary', function (): void {
    $now = now();
    $ttl = 120;

    // Session exactly at cutoff (should NOT be pruned - uses < not <=)
    DB::table('sessions')->insert([
        'id' => 'at-boundary',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes($ttl)->getTimestamp(),
    ]);

    // Session just after cutoff (should be pruned)
    DB::table('sessions')->insert([
        'id' => 'just-after-boundary',
        'user_id' => 2,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes($ttl + 1)->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions', ['--ttl' => $ttl])
        ->assertSuccessful()
        ->expectsOutput('Pruned 1 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'at-boundary')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'just-after-boundary')->exists())->toBeFalse();
});

it('works with TTL of zero', function (): void {
    $now = now();

    // All sessions should be expired with TTL of 0
    DB::table('sessions')->insert([
        'id' => 'recent-session',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subSeconds(30)->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions', ['--ttl' => 0])
        ->assertSuccessful()
        ->expectsOutput('Pruned 1 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(0);
});

it('dry run with custom TTL shows correct count', function (): void {
    $now = now();

    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(45)->getTimestamp(),
        ],
        [
            'id' => 'session-2',
            'user_id' => 2,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test-payload',
            'last_activity' => $now->copy()->subMinutes(25)->getTimestamp(),
        ],
    ]);

    $this->artisan('session-manager:prune-sessions', ['--dry-run' => true, '--ttl' => 30])
        ->assertSuccessful()
        ->expectsOutput('[DRY RUN] 1 expired sessions would be pruned.');

    expect(DB::table('sessions')->count())->toBe(2);
});

it('dry run with no expired sessions', function (): void {
    $now = now();

    DB::table('sessions')->insert([
        'id' => 'active-session',
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutput('[DRY RUN] 0 expired sessions would be pruned.');

    expect(DB::table('sessions')->count())->toBe(1);
});

it('preserves sessions with null user_id', function (): void {
    $now = now();

    // Expired guest session
    DB::table('sessions')->insert([
        'id' => 'expired-guest',
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(200)->getTimestamp(),
    ]);

    // Active guest session
    DB::table('sessions')->insert([
        'id' => 'active-guest',
        'user_id' => null,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'test-payload',
        'last_activity' => $now->copy()->subMinutes(30)->getTimestamp(),
    ]);

    $this->artisan('session-manager:prune-sessions')
        ->assertSuccessful()
        ->expectsOutput('Pruned 1 expired session(s).');

    expect(DB::table('sessions')->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'active-guest')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'expired-guest')->exists())->toBeFalse();
});
