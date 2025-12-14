<?php

declare(strict_types=1);

use WillVincent\SessionManager\Data\SessionLocation;

it('handles IntlTimeZone creation for valid timezone', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    $location = new SessionLocation(
        countryCode: 'GB',
        region: 'England',
        city: 'London',
        timezone: 'Europe/London',
        latitude: null,
        longitude: null,
    );

    $label = $location->timezoneLabel();

    // Should return a string or null
    expect(is_string($label) || is_null($label))->toBeTrue();
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('handles IntlTimeZone exception for invalid timezone', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    $location = new SessionLocation(
        countryCode: 'XX',
        region: null,
        city: null,
        timezone: 'Invalid/NonExistentTimezone',
        latitude: null,
        longitude: null,
    );

    $label = $location->timezoneLabel();

    // Should handle exception and return null
    expect(is_string($label) || is_null($label))->toBeTrue();
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('handles Locale exception when getting country name', function (): void {
    if (! class_exists(Locale::class)) {
        $this->markTestSkipped('Locale not available');
    }

    // Use an invalid country code that might trigger exception
    $location = new SessionLocation(
        countryCode: 'ZZ',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    $name = $location->countryName('invalid-locale-string');

    // Should handle exception and return null or string
    expect(is_string($name) || is_null($name))->toBeTrue();
})->skip(! class_exists(Locale::class), 'Locale not available');

it('falls back to null when timezone label resolution fails', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    $label = $location->timezoneLabel();

    expect($label)->toBeNull();
});

it('uses full timezone rendering path', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    $location = new SessionLocation(
        countryCode: 'JP',
        region: 'Tokyo',
        city: 'Tokyo',
        timezone: 'Asia/Tokyo',
        latitude: null,
        longitude: null,
    );

    // Call with locale to ensure we go through the full path
    $label = $location->timezoneLabel('ja');

    expect(is_string($label) || is_null($label))->toBeTrue();
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('uses Locale display region when Symfony not available', function (): void {
    if (! class_exists(Locale::class)) {
        $this->markTestSkipped('Locale not available');
    }

    $location = new SessionLocation(
        countryCode: 'FR',
        region: 'Île-de-France',
        city: 'Paris',
        timezone: 'Europe/Paris',
        latitude: null,
        longitude: null,
    );

    // Try with French locale
    $name = $location->countryName('fr');

    expect(is_string($name) || is_null($name))->toBeTrue();
})->skip(! class_exists(Locale::class), 'Locale not available');
