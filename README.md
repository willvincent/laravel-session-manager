# Laravel Session Manager

Enhanced session management for Laravel applications with device tracking and remote session termination.

[![Tests](https://github.com/willvincent/laravel-session-manager/actions/workflows/tests.yml/badge.svg)](https://github.com/willvincent/laravel-session-manager/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/willvincent/laravel-session-manager/graph/badge.svg?token=5AUQHEJWZ6)](https://codecov.io/gh/willvincent/laravel-session-manager)

## Features

* 📱 Track user sessions across devices
* 🔒 Remote logout from other devices
* 🚀 Works with any session driver (database, redis, file, etc.)
* ⚡ Optimized with throttling and indexing
* 🧪 Fully tested (100% test and type coverage & PHPStan Level 9)

---

## Quick Start

#### Install the package

```bash
composer require willvincent/laravel-session-manager
```

#### Publish config and language files

```bash
php artisan vendor:publish --tag=session-manager-config
php artisan vendor:publish --tag=session-manager-lang
```

### If **not** using the `database` session driver

> The following steps are **only required** if your application is **not** using Laravel’s `database` session driver.

#### Publish migrations and ensure the sessions table exists

```bash
php artisan vendor:publish --tag=session-manager-migrations
php artisan session:table   # Only if the sessions table does not already exist
php artisan migrate
```

#### Register the session indexing middleware

Add the middleware to the `web` middleware group:

```php
// bootstrap/app.php

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \WillVincent\SessionManager\Http\Middleware\IndexSessionMetadata::class,
    ]);
})
```

#### Schedule pruning of stale session data

```php
// routes/console.php

use Illuminate\Support\Facades\Schedule;

Schedule::command('session-manager:prune-sessions')
    ->daily();
```

Adjust the schedule as needed.
By default, the command removes session records older than Laravel’s configured session lifetime.

Optional flags:

* `--ttl=MINUTES` — override the session lifetime
* `--dry-run` — show how many records would be deleted without deleting them

Example:

```bash
php artisan session-manager:prune-sessions --dry-run
```

### Using the `database` session driver?

If your application **uses Laravel’s `database` session driver**, the steps above are **not required**.

In this case:

* Session metadata is read directly from Laravel’s `sessions` table
* Session records are already created and maintained automatically
* Laravel’s built-in session garbage collection handles expiration

### ✅ That’s it

Session metadata will now be tracked automatically and kept clean over time.

---

### Using the Facade

The package exposes a **facade-first API**, consistent with Laravel’s native style:

```php
use WillVincent\SessionManager\Facades\SessionManager;
```

Fetch all sessions for the authenticated user:

```php
$sessions = SessionManager::getUserSessions(auth()->id());
```

Log out all other sessions (after password confirmation):

```php
SessionManager::logoutOtherSessions(auth()->id());
```

---

## Comparison with Laravel’s Built-in Session Logout

Laravel provides:

```php
Auth::logoutOtherDevices($password);
```

**Limitations of the built-in approach:**

* ❌ No built-in API/UI to list sessions or devices in Laravel core (except in some starter kits)
* ❌ No or limited session metadata (IP, browser, device, location)
* ❌ Not session-aware or targetable (all-or-nothing)
* ❌ Depends on `AuthenticateSession` middleware

**Laravel Session Manager:**

* ✅ Works with **all** native session drivers (Redis, database, file, etc.)
* ✅ Lists all active sessions and devices
* ✅ Allows targeted or bulk remote logout
* ✅ Optional [IP-based location enrichment](LOCATION.md)

If you need visibility and control beyond a blind logout call, this package fills the gap.

---

## Usage

### Get User Sessions

```php
use WillVincent\SessionManager\Facades\SessionManager;

$sessions = SessionManager::getUserSessions(auth()->id());

foreach ($sessions as $session) {
    echo $session->agent->platform();   // e.g. "macOS"
    echo $session->agent->browser();    // e.g. "Chrome"
    echo $session->ip_address;          // e.g. "192.168.1.1"
    echo $session->is_current_device;   // true / false
    echo $session->last_active;         // "2 minutes ago"

    // Optional location data
    echo $session->location?->labelWithConfidence(include_country: true);
}
```

### Logout Other Sessions

```php
use WillVincent\SessionManager\Facades\SessionManager;

$request->validate([
    'password' => ['required', 'current_password'],
]);

SessionManager::logoutOtherSessions(auth()->id());
```

---

## Livewire Example

```php
use Livewire\Component;
use WillVincent\SessionManager\Facades\SessionManager;

class SessionSettings extends Component
{
    public string $password = '';

    public function getSessionsProperty()
    {
        return SessionManager::getUserSessions(auth()->id());
    }

    public function logoutOtherSessions()
    {
        $this->validate([
            'password' => ['required', 'current_password'],
        ]);

        SessionManager::logoutOtherSessions(auth()->id());

        $this->dispatch('sessions-updated');
    }

    public function render()
    {
        return view('livewire.session-settings');
    }
}
```

---

## Advanced: Service Container Access

Most applications should use the facade. If you prefer constructor injection:

```php
use WillVincent\SessionManager\SessionManager;

public function __construct(
    private SessionManager $sessions,
) {}

$this->sessions->logoutOtherSessions($userId);
```

---

## Session Driver Support

The ability to list and remotely terminate sessions depends on the underlying session driver.

| Session Driver | List user sessions | Log out other sessions |
|----------------|--------------------|------------------------|
| `database`     | ✅                  | ✅                      |
| `redis`        | ✅                  | ✅                      |
| `file`         | ✅                  | ✅                      |
| `cookie`       | ✅                  | ❌                      |
| `array`        | ❌                  | ❌                      |

### Cookie Session Driver Notes

When using the `cookie` session driver, session data is stored entirely client-side. Because there is no server-side
session store, **remote session termination is not possible** via this package alone.

If your application uses cookie-based sessions and you want to invalidate other devices, you **must also** call
Laravel’s built-in method:

```php
use Illuminate\Support\Facades\Auth;

Auth::logoutOtherDevices($password);
```

This requires validating the user’s password (which you should already be doing before calling
`logoutOtherSessions`). Laravel will then invalidate authentication on other devices on their next request
via the `AuthenticateSession` middleware.

In this scenario, this package will still:

* Track session metadata
* Clean up stored session records

…but Laravel handles the actual authentication invalidation.

### Array Session Driver Notes

The `array` session driver stores session data **in memory for the current request only**.
Sessions do not persist across requests, processes, or devices.

Because of this:

- Sessions cannot be listed
- Sessions cannot be remotely terminated
- Session metadata has no meaningful lifespan

The `array` driver is intended for testing and local development only and is
not compatible with multi-device session management.


---

## Requirements

* PHP 8.2+
* Laravel 11.0+
* Default Laravel session table (create via `php artisan session:table`)
* doctrine/dbal (installs with this package, required for altering the sessions table)
* `geoip2/geoip2`, `symfony/intl`, and `ext-intl` are optional (for IP location support)

---

## License

This package is [MIT](LICENSE.md) licensed, and free to use, fork, etc.