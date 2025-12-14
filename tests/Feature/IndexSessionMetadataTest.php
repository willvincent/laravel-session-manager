<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use WillVincent\SessionManager\Http\Middleware\IndexSessionMetadata;

beforeEach(function (): void {
    $this->middleware = new IndexSessionMetadata();
});

it('indexes session metadata for authenticated user with redis driver', function (): void {
    Config::set('session.driver', 'redis');
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(1);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    $sessionId = $request->session()->getId();

    Cache::shouldReceive('add')
        ->once()
        ->with('session-index:touch:'.$sessionId, 1, Mockery::type(DateTimeInterface::class))
        ->andReturn(true);

    DB::shouldReceive('table')->with('sessions')->andReturnSelf();
    DB::shouldReceive('upsert')
        ->once()
        ->withArgs(fn ($records, $uniqueBy, $update): bool => isset($records[0]['id'])
            && $records[0]['user_id'] === 1
            && $uniqueBy === ['id']
            && $update === ['user_id', 'ip_address', 'user_agent', 'last_activity'])
        ->andReturn(1);

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('indexes session metadata for authenticated user with file driver', function (): void {
    Config::set('session.driver', 'file');
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(2);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    Cache::shouldReceive('add')->andReturn(true);
    DB::shouldReceive('table')->with('sessions')->andReturnSelf();
    DB::shouldReceive('upsert')->once()->andReturn(1);

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('does not index when using database driver', function (): void {
    Config::set('session.driver', 'database');
    Auth::shouldReceive('check')->andReturn(true);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    Cache::shouldReceive('add')->never();
    DB::shouldReceive('table')->never();

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('does not index when user is not authenticated', function (): void {
    Config::set('session.driver', 'redis');
    Auth::shouldReceive('check')->andReturn(false);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    Cache::shouldReceive('add')->never();
    DB::shouldReceive('table')->never();

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('respects throttle configuration', function (): void {
    Config::set('session.driver', 'redis');
    Config::set('session-manager.throttle_seconds', 120);
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(1);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    Cache::shouldReceive('add')
        ->once()
        ->andReturn(true);

    DB::shouldReceive('table')->with('sessions')->andReturnSelf();
    DB::shouldReceive('upsert')->once()->andReturn(1);

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('does not update when throttle is active', function (): void {
    Config::set('session.driver', 'redis');
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(1);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    // Cache::add returns false when key already exists (throttled)
    Cache::shouldReceive('add')
        ->once()
        ->andReturn(false);

    // DB should not be called when throttled
    DB::shouldReceive('table')->never();

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('captures request IP and user agent', function (): void {
    Config::set('session.driver', 'redis');
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(1);

    $request = Request::create('/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.100',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
    ]);
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->setId('ip-test-session');
    $request->session()->start();

    Cache::shouldReceive('add')->andReturn(true);

    DB::shouldReceive('table')->with('sessions')->andReturnSelf();
    DB::shouldReceive('upsert')
        ->once()
        ->withArgs(fn ($records): bool => $records[0]['ip_address'] === '192.168.1.100'
            && $records[0]['user_agent'] === 'Mozilla/5.0 Test Browser')
        ->andReturn(1);

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('handles null user agent', function (): void {
    Config::set('session.driver', 'redis');
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(1);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(resolve('session.store'));
    $request->session()->start();

    Cache::shouldReceive('add')->andReturn(true);

    DB::shouldReceive('table')->with('sessions')->andReturnSelf();
    DB::shouldReceive('upsert')
        ->once()
        ->withArgs(fn ($records): bool => is_string($records[0]['user_agent']))
        ->andReturn(1);

    $response = $this->middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});
