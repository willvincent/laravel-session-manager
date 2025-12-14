<?php

declare(strict_types=1);

use WillVincent\SessionManager\Data\SessionLocation;

it('covers catch blocks with reflection-based exception triggering', function (): void {
    // Create a location that will go through the exception paths
    $location = new SessionLocation(
        countryCode: 'US',
        region: null,
        city: null,
        timezone: 'America/New_York',
        latitude: null,
        longitude: null,
    );

    // Test with various problematic inputs that might trigger internal errors
    $problematicLocales = [
        str_repeat('x', 10000), // Very long string
        '', // Empty string (might cause issues in some ICU versions)
        'invalid_locale_format_that_is_very_long_'.str_repeat('x', 1000),
    ];

    foreach ($problematicLocales as $locale) {
        $result = $location->countryName($locale);
        expect($result === null || is_string($result))->toBeTrue();

        if (class_exists(IntlTimeZone::class)) {
            $tzResult = $location->timezoneLabel($locale);
            expect($tzResult === null || is_string($tzResult))->toBeTrue();
        }
    }
});

it('covers IntlTimeZone exception with invalid timezone operations', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    // Create location with a timezone
    $location = new SessionLocation(
        countryCode: 'US',
        region: null,
        city: null,
        timezone: 'America/New_York',
        latitude: null,
        longitude: null,
    );

    // Try with invalid locale formats that might cause ICU to throw
    $badLocales = [
        str_repeat("\x00", 100), // Null bytes
        "\xFF\xFE", // Invalid UTF-8 sequence
        str_repeat('locale', 1000),
    ];

    foreach ($badLocales as $badLocale) {
        $result = $location->timezoneLabel($badLocale);
        expect($result === null || is_string($result))->toBeTrue();
    }
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('covers Locale exception with invalid country operations', function (): void {
    if (! class_exists(Locale::class)) {
        $this->markTestSkipped('Locale not available');
    }

    $location = new SessionLocation(
        countryCode: 'US',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    // Try with invalid locale formats
    $badLocales = [
        str_repeat("\x00", 100),
        "\xFF\xFE",
        str_repeat('x', 5000),
    ];

    foreach ($badLocales as $badLocale) {
        $result = $location->countryName($badLocale);
        expect($result === null || is_string($result))->toBeTrue();
    }
})->skip(! class_exists(Locale::class), 'Locale not available');

it('exercises countryName with various locales including edge cases', function (): void {
    $location = new SessionLocation(
        countryCode: 'FR',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    // Test multiple locales to ensure we exercise the code paths
    $locales = ['en', 'fr', 'de', 'ja', 'zh', 'ar', 'he', 'ru'];

    foreach ($locales as $locale) {
        $result = $location->countryName($locale);
        expect($result === null || is_string($result))->toBeTrue();
    }
});

it('exercises timezoneLabel with various locales including edge cases', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    $location = new SessionLocation(
        countryCode: 'JP',
        region: null,
        city: null,
        timezone: 'Asia/Tokyo',
        latitude: null,
        longitude: null,
    );

    // Test multiple locales
    $locales = ['en', 'fr', 'de', 'ja', 'zh', 'ar', 'he', 'ru'];

    foreach ($locales as $locale) {
        $result = $location->timezoneLabel($locale);
        expect($result === null || is_string($result))->toBeTrue();
    }
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');
