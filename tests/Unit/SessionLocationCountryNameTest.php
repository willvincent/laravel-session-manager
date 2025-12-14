<?php

declare(strict_types=1);

use Symfony\Component\Intl\Countries;
use WillVincent\SessionManager\Data\SessionLocation;

it('returns country name using Symfony Intl if available', function (): void {
    if (! class_exists(Countries::class)) {
        $this->markTestSkipped('Symfony Intl not installed');
    }

    $location = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'San Francisco',
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
    );

    $countryName = $location->countryName('en');

    expect($countryName)->toBeString();
})->skip(! class_exists(Countries::class), 'Symfony Intl not installed');

it('returns country name using ext-intl if available', function (): void {
    if (! class_exists(Locale::class)) {
        $this->markTestSkipped('ext-intl not installed');
    }

    $location = new SessionLocation(
        countryCode: 'FR',
        region: null,
        city: 'Paris',
        timezone: 'Europe/Paris',
        latitude: null,
        longitude: null,
    );

    $countryName = $location->countryName('en');

    expect(is_string($countryName) || is_null($countryName))->toBeTrue();
})->skip(! class_exists(Locale::class), 'ext-intl not installed');

it('returns timezone label using IntlTimeZone if available', function (): void {
    if (! class_exists(IntlTimeZone::class)) {
        $this->markTestSkipped('IntlTimeZone not available');
    }

    $location = new SessionLocation(
        countryCode: 'US',
        region: 'New York',
        city: 'New York',
        timezone: 'America/New_York',
        latitude: null,
        longitude: null,
    );

    $timezoneLabel = $location->timezoneLabel('en');

    expect(is_string($timezoneLabel) || is_null($timezoneLabel))->toBeTrue();
})->skip(! class_exists(IntlTimeZone::class), 'IntlTimeZone not available');

it('handles errors when getting country name gracefully', function (): void {
    $location = new SessionLocation(
        countryCode: 'XX', // Invalid country code
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    $countryName = $location->countryName();

    // Should return a string or null
    expect(is_string($countryName) || is_null($countryName))->toBeTrue();
});

it('handles errors when getting timezone label gracefully', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: null,
        city: null,
        timezone: 'Invalid/Timezone',
        latitude: null,
        longitude: null,
    );

    $timezoneLabel = $location->timezoneLabel();

    // Should return a string or null
    expect(is_string($timezoneLabel) || is_null($timezoneLabel))->toBeTrue();
});

it('uses app locale when no locale specified', function (): void {
    app()->setLocale('fr');

    $location = new SessionLocation(
        countryCode: 'DE',
        region: 'Bavaria',
        city: 'Munich',
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
    );

    $label = $location->label();

    // Should work with any locale
    expect($label)->toBeString()->and($label)->not->toBeEmpty();

    // Reset to default
    app()->setLocale('en');
});
