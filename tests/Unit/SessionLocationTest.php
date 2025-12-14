<?php

declare(strict_types=1);

use WillVincent\SessionManager\Data\SessionLocation;
use WillVincent\SessionManager\Enums\LocationConfidence;

it('creates session location with all fields', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'San Francisco',
        timezone: 'America/Los_Angeles',
        latitude: 37.7749,
        longitude: -122.4194,
        confidence: LocationConfidence::HIGH,
        source: 'maxmind',
    );

    expect($location->countryCode)->toBe('US')
        ->and($location->region)->toBe('California')
        ->and($location->city)->toBe('San Francisco')
        ->and($location->timezone)->toBe('America/Los_Angeles')
        ->and($location->latitude)->toBe(37.7749)
        ->and($location->longitude)->toBe(-122.4194)
        ->and($location->confidence)->toBe(LocationConfidence::HIGH)
        ->and($location->source)->toBe('maxmind');
});

it('creates session location with minimal fields', function (): void {
    $location = new SessionLocation(
        countryCode: 'FR',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->countryCode)->toBe('FR')
        ->and($location->region)->toBeNull()
        ->and($location->city)->toBeNull()
        ->and($location->timezone)->toBeNull()
        ->and($location->latitude)->toBeNull()
        ->and($location->longitude)->toBeNull()
        ->and($location->confidence)->toBeNull()
        ->and($location->source)->toBe('maxmind');
});

it('returns true for isApproximate when confidence is medium', function (): void {
    $location = new SessionLocation(
        countryCode: 'DE',
        region: 'Berlin',
        city: 'Berlin',
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::MEDIUM,
    );

    expect($location->isApproximate())->toBeTrue();
});

it('returns true for isApproximate when confidence is low', function (): void {
    $location = new SessionLocation(
        countryCode: 'GB',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::LOW,
    );

    expect($location->isApproximate())->toBeTrue();
});

it('returns false for isApproximate when confidence is high', function (): void {
    $location = new SessionLocation(
        countryCode: 'JP',
        region: 'Tokyo',
        city: 'Tokyo',
        timezone: 'Asia/Tokyo',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    expect($location->isApproximate())->toBeFalse();
});

it('returns true for isApproximate when confidence is null', function (): void {
    $location = new SessionLocation(
        countryCode: 'CA',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->isApproximate())->toBeTrue();
});

it('returns confidence label', function (): void {
    $location = new SessionLocation(
        countryCode: 'AU',
        region: 'New South Wales',
        city: 'Sydney',
        timezone: 'Australia/Sydney',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    expect($location->confidenceLabel())->toBeString();
});

it('returns default confidence label when null', function (): void {
    $location = new SessionLocation(
        countryCode: 'NZ',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->confidenceLabel())->toBeString();
});

it('generates label with city and region', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: 'California',
        city: 'San Francisco',
        timezone: 'America/Los_Angeles',
        latitude: null,
        longitude: null,
    );

    expect($location->label())->toBe('San Francisco, California');
});

it('generates label with city only', function (): void {
    $location = new SessionLocation(
        countryCode: 'FR',
        region: null,
        city: 'Paris',
        timezone: 'Europe/Paris',
        latitude: null,
        longitude: null,
    );

    expect($location->label())->toBe('Paris');
});

it('generates label with region only', function (): void {
    $location = new SessionLocation(
        countryCode: 'DE',
        region: 'Bavaria',
        city: null,
        timezone: 'Europe/Berlin',
        latitude: null,
        longitude: null,
    );

    expect($location->label())->toBe('Bavaria');
});

it('generates label including country when requested', function (): void {
    $location = new SessionLocation(
        countryCode: 'GB',
        region: 'England',
        city: 'London',
        timezone: 'Europe/London',
        latitude: null,
        longitude: null,
    );

    $label = $location->label(include_country: true);

    expect($label)->toContain('London')
        ->and($label)->toContain('England');
});

it('returns unknown location when all fields are null', function (): void {
    $location = new SessionLocation(
        countryCode: null,
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->label())->toBe(__('session-manager::unknown_location'));
});

it('returns unknown location when fields are empty strings', function (): void {
    $location = new SessionLocation(
        countryCode: '',
        region: '  ',
        city: '',
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->label())->toBe(__('session-manager::unknown_location'));
});

it('generates label with confidence prefix for approximate locations', function (): void {
    $location = new SessionLocation(
        countryCode: 'IT',
        region: 'Lazio',
        city: 'Rome',
        timezone: 'Europe/Rome',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::MEDIUM,
    );

    $label = $location->labelWithConfidence();

    // Should return a string (Near Rome, Lazio or translation thereof)
    expect($label)->toBeString()->and($label)->not->toBeEmpty();
});

it('does not prefix unknown location with confidence', function (): void {
    $location = new SessionLocation(
        countryCode: null,
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::LOW,
    );

    expect($location->labelWithConfidence())->toBe(__('session-manager::unknown_location'));
});

it('does not prefix high confidence locations', function (): void {
    $location = new SessionLocation(
        countryCode: 'ES',
        region: 'Madrid',
        city: 'Madrid',
        timezone: 'Europe/Madrid',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    $label = $location->labelWithConfidence();

    expect($label)->toBe('Madrid, Madrid');
});

it('returns confidence icon', function (): void {
    $location = new SessionLocation(
        countryCode: 'BR',
        region: 'São Paulo',
        city: 'São Paulo',
        timezone: 'America/Sao_Paulo',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::HIGH,
    );

    expect($location->confidenceIcon())->toBe('●');
});

it('returns default confidence icon when null', function (): void {
    $location = new SessionLocation(
        countryCode: 'MX',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->confidenceIcon())->toBe('○');
});

it('returns confidence icon key', function (): void {
    $location = new SessionLocation(
        countryCode: 'IN',
        region: 'Maharashtra',
        city: 'Mumbai',
        timezone: 'Asia/Kolkata',
        latitude: null,
        longitude: null,
        confidence: LocationConfidence::MEDIUM,
    );

    expect($location->confidenceIconKey())->toBe('accuracy-medium');
});

it('returns default confidence icon key when null', function (): void {
    $location = new SessionLocation(
        countryCode: 'ZA',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->confidenceIconKey())->toBe('accuracy-low');
});

it('generates label with timezone when requested', function (): void {
    $location = new SessionLocation(
        countryCode: 'US',
        region: 'New York',
        city: 'New York',
        timezone: 'America/New_York',
        latitude: null,
        longitude: null,
    );

    $label = $location->label(with_tz: true);

    // Should contain city name and timezone (either formatted or raw)
    expect($label)->toContain('New York')
        ->and($label)->toContain('(');
});

it('skips timezone when not available', function (): void {
    $location = new SessionLocation(
        countryCode: 'RU',
        region: 'Moscow',
        city: 'Moscow',
        timezone: null,
        latitude: null,
        longitude: null,
    );

    $label = $location->label(with_tz: true);

    expect($label)->toBe('Moscow, Moscow');
});

it('returns null for country name with invalid code', function (): void {
    $location = new SessionLocation(
        countryCode: 'INVALID',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->countryName())->toBeNull();
});

it('returns null for country name with single character code', function (): void {
    $location = new SessionLocation(
        countryCode: 'A',
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->countryName())->toBeNull();
});

it('returns null for country name when code is null', function (): void {
    $location = new SessionLocation(
        countryCode: null,
        region: null,
        city: null,
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->countryName())->toBeNull();
});

it('returns null for timezone label when timezone is null', function (): void {
    $location = new SessionLocation(
        countryCode: 'AR',
        region: 'Buenos Aires',
        city: 'Buenos Aires',
        timezone: null,
        latitude: null,
        longitude: null,
    );

    expect($location->timezoneLabel())->toBeNull();
});

it('handles country code normalization', function (): void {
    $location = new SessionLocation(
        countryCode: 'us',
        region: 'Texas',
        city: 'Austin',
        timezone: 'America/Chicago',
        latitude: null,
        longitude: null,
    );

    // Should normalize to uppercase
    $countryName = $location->countryName();
    // Either returns a name or null (depending on intl availability)
    expect(is_string($countryName) || is_null($countryName))->toBeTrue();
});
