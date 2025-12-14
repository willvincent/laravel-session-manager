<?php

declare(strict_types=1);

namespace WillVincent\SessionManager;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;
use stdClass;
use WillVincent\SessionManager\Contracts\IpLocationResolver;
use WillVincent\SessionManager\Data\SessionDevice;
use WillVincent\SessionManager\Data\UserSession;

final class SessionManager
{
    /**
     * Get the session TTL in minutes from configuration.
     */
    public static function sessionLifetime(): int
    {
        $lifetime = config('session.lifetime');

        return is_numeric($lifetime) ? (int) $lifetime : 120;
    }

    /**
     * Get all active sessions for a user.
     *
     * @param  int|string|null  $userId  User ID (defaults to current authenticated user)
     * @return Collection<int, UserSession>
     */
    public function getUserSessions(int|string|null $userId = null): Collection
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return collect();
        }

        $cutoff = now()->subMinutes(self::sessionLifetime())->getTimestamp();

        $sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $cutoff)
            ->orderBy('last_activity', 'desc')
            ->get();

        return $sessions->map(function (stdClass $session): UserSession {
            $agent = new Agent();
            $agent->setUserAgent($session->user_agent ?? '');

            $location = null;

            if (config('session-manager.location.enabled')) {
                $location = resolve(IpLocationResolver::class)
                    ->resolve($session->ip_address);
            }

            $browser = $agent->browser();
            $platform = $agent->platform();

            $device = new SessionDevice(
                browser: $browser,
                browser_version: is_string($browser) ? $agent->version($browser) : null,
                platform: $platform,
                platform_version: is_string($platform) ? $agent->version($platform) : null,
                device: $agent->device(),
                is_desktop: $agent->isDesktop(),
                is_mobile: $agent->isMobile(),
                is_tablet: $agent->isTablet(),
                is_robot: $agent->isRobot(),
            );

            return new UserSession(
                id: $session->id,
                ip_address: $session->ip_address,
                user_agent: $session->user_agent,
                last_activity: $session->last_activity,
                is_current: $session->id === Session::getId(),
                device: $device,
                location: $location,
            );
        });
    }

    /**
     * Logout for a user.
     *
     * @param  int|string|null  $userId  User ID (defaults to current authenticated user)
     * @param  bool  $includeCurrent  If true, logouts ALL sessions including current one
     * @return int Number of sessions logouted
     */
    public function logoutSessions(int|string|null $userId = null, bool $includeCurrent = false): int
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return 0;
        }

        $currentSessionId = Session::getId();

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->when(! $includeCurrent && $currentSessionId, fn (Builder $query) => $query->where('id', '!=', $currentSessionId))
            ->delete();
    }

    /**
     * Logout a specific session by ID.
     *
     * @param  int|string|null  $userId  Optional user ID to verify ownership
     */
    public function logoutSession(string $sessionId, int|string|null $userId = null): bool
    {
        return DB::table('sessions')
            ->where('id', $sessionId)
            ->when($userId !== null, fn (Builder $query) => $query->where('user_id', $userId))
            ->delete() > 0;
    }

    /**
     * Get the count of active sessions for a user.
     *
     * @param  int|string|null  $userId  User ID (defaults to current authenticated user)
     */
    public function getSessionCount(int|string|null $userId = null): int
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return 0;
        }

        $cutoff = now()->subMinutes(self::sessionLifetime())->getTimestamp();

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $cutoff)
            ->count();
    }

    /**
     * Logout all sessions for a user (including current).
     * Useful for locking out a user completely.
     *
     * @param  int|string|null  $userId  User ID (defaults to current authenticated user)
     * @return int Number of sessions logouted
     */
    public function logoutAllSessions(int|string|null $userId = null): int
    {
        return $this->logoutSessions($userId, true);
    }

    /**
     * Logout all sessions for a user but current.
     *
     * @param  int|string|null  $userId  User ID (defaults to current authenticated user)
     * @return int Number of sessions logouted
     */
    public function logoutOtherSessions(int|string|null $userId = null): int
    {
        return $this->logoutSessions($userId);
    }
}
