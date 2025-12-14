<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use WillVincent\SessionManager\Data\UserSession;

/**
 * @method static Collection<int, UserSession> getUserSessions($userId = null)
 * @method static int destroySessions($userId = null, bool $includeCurrent = false)
 * @method static int destroyOtherSessions($userId = null)
 * @method static int destroyAllSessions($userId = null)
 * @method static bool destroySession(string $sessionId, $userId = null)
 * @method static int getSessionCount($userId = null)
 *
 * @see \WillVincent\SessionManager\SessionManager
 */
final class SessionManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \WillVincent\SessionManager\SessionManager::class;
    }
}
