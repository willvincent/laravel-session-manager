<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Metadata Update Throttle
    |--------------------------------------------------------------------------
    |
    | How often (in seconds) to update session metadata. This prevents
    | excessive database writes on every request.
    |
    */
    'throttle_seconds' => env('SESSION_MANAGER_THROTTLE', 60),

    /*
    |--------------------------------------------------------------------------
    | Redis Session Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for session keys in Redis. This should match your
    | Laravel session configuration.
    |
    */
    'redis_prefix' => env('SESSION_REDIS_PREFIX', 'session:'),

    /*
    |--------------------------------------------------------------------------
    | Session Location Enrichment
    |--------------------------------------------------------------------------
    */
    'location' => [

        /*
        |--------------------------------------------------------------------------
        | Enable Session Location Enrichment
        |--------------------------------------------------------------------------
        |
        | If enabled, the package will attempt to resolve IP addresses to
        | approximate geographic locations using a local MaxMind database.
        |
        */

        'enabled' => env('SESSION_MANAGER_LOCATION', false),

        /*
        |--------------------------------------------------------------------------
        | IP location cache
        |--------------------------------------------------------------------------
        |
        | Cached per IP address to avoid repeated lookups.
        | Uses Laravel's cache system.
        |
        */

        'cache' => [
            'enabled' => env('SESSION_MANAGER_CACHE', true),

            'key' => env('SESSION_MANAGER_CACHE_KEY_PREFIX', 'session_manager:ip_location'),

            // 7 days is a good balance: IPs don't change constantly,
            // but this avoids long-term tracking concerns.
            'ttl' => env('SESSION_MANAGER_CACHE_TTL', 60 * 60 * 24 * 7),

            // Optional cache store override (null = default)
            'store' => env('SESSION_MANAGER_CACHE_STORE'),
        ],

        /*
        |--------------------------------------------------------------------------
        | MaxMind Configuration
        |--------------------------------------------------------------------------
        */

        'maxmind' => [

            // Path to the GeoLite2 City database (.mmdb)
            'database_path' => env(
                'SESSION_MANAGER_MAXMIND_DB',
                storage_path('app/geoip/GeoLite2-City.mmdb')
            ),

            // Store latitude / longitude (off by default for privacy)
            'store_coordinates' => false,
        ],
    ],

];
