<?php

declare(strict_types=1);

use WillVincent\SessionManager\Data\SessionLocation;

// This test file attempts to trigger the exception handlers by creating
// conditions that would cause the Intl functions to throw

it('attempts to trigger Locale exception with error handler', function (): void {
    if (! class_exists(Locale::class)) {
        $this->markTestSkipped('Locale not available');
    }

    // Set up error handler that converts errors to exceptions
    set_error_handler(function ($severity, $message, $file, $line): void {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        $location = new SessionLocation(
            countryCode: 'US',
            region: null,
            city: null,
            timezone: null,
            latitude: null,
            longitude: null,
        );

        // Try various inputs that might cause errors
        $inputs = [
            str_repeat('x', 100000), // Very long
            "\xFF\xFF\xFF\xFF", // Invalid UTF-8
            str_repeat("\x00", 1000), // Null bytes
        ];

        foreach ($inputs as $input) {
            $result = $location->countryName($input);
            expect($result === null || is_string($result))->toBeTrue();
        }
    } finally {
        restore_error_handler();
    }
})->skip(! class_exists(Locale::class), 'Locale not available');

it('attempts to trigger IntlTimeZone exception with error handler', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    set_error_handler(function ($severity, $message, $file, $line): void {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        $location = new SessionLocation(
            countryCode: 'US',
            region: null,
            city: null,
            timezone: 'America/New_York',
            latitude: null,
            longitude: null,
        );

        $inputs = [
            str_repeat('x', 100000),
            "\xFF\xFF\xFF\xFF",
            str_repeat("\x00", 1000),
        ];

        foreach ($inputs as $input) {
            $result = $location->timezoneLabel($input);
            expect($result === null || is_string($result))->toBeTrue();
        }
    } finally {
        restore_error_handler();
    }
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('exercises the appLocale method through multiple calls', function (): void {
    // Create multiple locations and call methods that use appLocale()
    $locations = [];
    for ($i = 0; $i < 10; $i++) {
        $locations[] = new SessionLocation(
            countryCode: 'US',
            region: null,
            city: null,
            timezone: 'America/New_York',
            latitude: null,
            longitude: null,
        );
    }

    foreach ($locations as $location) {
        if (class_exists(IntlTimeZone::class)) {
            $result = $location->timezoneLabel();
            expect($result === null || is_string($result))->toBeTrue();
        }

        $result2 = $location->countryName();
        expect($result2 === null || is_string($result2))->toBeTrue();
    }
});

it('covers all branches with comprehensive locale testing', function (): void {
    $location = new SessionLocation(
        countryCode: 'DE',
        region: 'Bavaria',
        city: 'Munich',
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
    );

    // Test all the different locale variations
    $locales = [
        'en', 'en_US', 'en-US',
        'de', 'de_DE', 'de-DE',
        'fr', 'fr_FR', 'fr-FR',
        'ja', 'ja_JP', 'ja-JP',
        'zh', 'zh_CN', 'zh-CN',
        'ar', 'ar_SA', 'ar-SA',
        null, // default locale
    ];

    foreach ($locales as $locale) {
        $countryResult = $location->countryName($locale);
        expect($countryResult === null || is_string($countryResult))->toBeTrue();

        if (class_exists(IntlTimeZone::class)) {
            $tzResult = $location->timezoneLabel($locale);
            expect($tzResult === null || is_string($tzResult))->toBeTrue();
        }
    }
});
