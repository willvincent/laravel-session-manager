<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class IndexSessionMetadata
{
    /**
     * Handle an incoming request.
     *
     * For non-database session drivers (redis, file, etc.), this middleware
     * maintains a parallel record of session metadata in the sessions table
     * so we can track devices and perform remote logouts.
     *
     * When using the database session driver, this middleware does nothing
     * because Laravel already writes to the sessions table automatically.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasSession() ||
            ! Auth::check() ||
            config('session.driver') === 'database' // Nothing to do if using DB session driver.
        ) {
            return $response;
        }

        $sid = $request->session()->getId();

        // Throttle: only update once per configured interval per session
        $throttleSecondsConfig = config('session-manager.throttle_seconds', 60);
        $throttleSeconds = is_int($throttleSecondsConfig) ? $throttleSecondsConfig : 60;
        $throttleKey = 'session-index:touch:'.$sid;

        if (! Cache::add($throttleKey, 1, now()->addSeconds($throttleSeconds))) {
            return $response;
        }

        DB::table('sessions')->upsert(
            [[
                'id' => $sid,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'last_activity' => now()->timestamp,
            ]],
            ['id'],
            ['user_id', 'ip_address', 'user_agent', 'last_activity'],
        );

        return $response;
    }
}
