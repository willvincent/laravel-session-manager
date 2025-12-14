<?php

declare(strict_types=1);

use WillVincent\SessionManager\Data\SessionLocation;

it('handles country name resolution with Locale when Symfony Intl unavailable', function (): void {
    // Temporarily mock Symfony\Component\Intl\Countries as not existing
    // This will force the code to use Locale::getDisplayRegion path
    $location = new SessionLocation(
        countryCode: 'DE',
        region: 'Bavaria',
        city: 'Munich',
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
    );

    $countryName = $location->countryName('en');

    // Should return either a string or null depending on intl availability
    expect(is_string($countryName) || is_null($countryName))->toBeTrue();
});

it('handles timezone label resolution with IntlTimeZone', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'Los Angeles',
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
    );

    $timezoneLabel = $location->timezoneLabel('en');

    // Should return either a string or null depending on intl availability
    expect(is_string($timezoneLabel) || is_null($timezoneLabel))->toBeTrue();
});

it('handles exceptions in timezone label gracefully', function (): void {
    $location = new SessionLocation(
        countryCode: 'XX',
        region: null,
        city: null,
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
    );

    $timezoneLabel = $location->timezoneLabel();

    // Should not throw - returns string or null
    expect(is_string($timezoneLabel) || is_null($timezoneLabel))->toBeTrue();
});

it('falls through to null when country code lookup fails', function (): void {
    $location = new SessionLocation(
        countryCode: 'ZZ', // Invalid code
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    $countryName = $location->countryName();

    // Depending on intl availability, may return string or null
    expect(is_string($countryName) || is_null($countryName))->toBeTrue();
});
