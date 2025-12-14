<?php

declare(strict_types=1);

use WillVincent\SessionManager\Enums\LocationConfidence;

it('has high confidence case', function (): void {
    expect(LocationConfidence::HIGH->value)->toBe('high');
});

it('has medium confidence case', function (): void {
    expect(LocationConfidence::MEDIUM->value)->toBe('medium');
});

it('has low confidence case', function (): void {
    expect(LocationConfidence::LOW->value)->toBe('low');
});

it('returns false for isApproximate on high confidence', function (): void {
    expect(LocationConfidence::HIGH->isApproximate())->toBeFalse();
});

it('returns true for isApproximate on medium confidence', function (): void {
    expect(LocationConfidence::MEDIUM->isApproximate())->toBeTrue();
});

it('returns true for isApproximate on low confidence', function (): void {
    expect(LocationConfidence::LOW->isApproximate())->toBeTrue();
});

it('returns label for high confidence', function (): void {
    $label = LocationConfidence::HIGH->label();

    expect($label)->toBeString()
        ->and($label)->toBe(__('session-manager::confidence.high'));
});

it('returns label for medium confidence', function (): void {
    $label = LocationConfidence::MEDIUM->label();

    expect($label)->toBeString()
        ->and($label)->toBe(__('session-manager::confidence.medium'));
});

it('returns label for low confidence', function (): void {
    $label = LocationConfidence::LOW->label();

    expect($label)->toBeString()
        ->and($label)->toBe(__('session-manager::confidence.low'));
});

it('returns icon for high confidence', function (): void {
    expect(LocationConfidence::HIGH->icon())->toBe('●');
});

it('returns icon for medium confidence', function (): void {
    expect(LocationConfidence::MEDIUM->icon())->toBe('◐');
});

it('returns icon for low confidence', function (): void {
    expect(LocationConfidence::LOW->icon())->toBe('○');
});

it('returns icon label for high confidence', function (): void {
    $iconLabel = LocationConfidence::HIGH->iconLabel();

    expect($iconLabel)->toBe(__('session-manager::confidence.high'));
});

it('returns icon label for medium confidence', function (): void {
    $iconLabel = LocationConfidence::MEDIUM->iconLabel();

    expect($iconLabel)->toBe(__('session-manager::confidence.medium'));
});

it('returns icon label for low confidence', function (): void {
    $iconLabel = LocationConfidence::LOW->iconLabel();

    expect($iconLabel)->toBe(__('session-manager::confidence.low'));
});

it('returns icon key for high confidence', function (): void {
    expect(LocationConfidence::HIGH->iconKey())->toBe('accuracy-high');
});

it('returns icon key for medium confidence', function (): void {
    expect(LocationConfidence::MEDIUM->iconKey())->toBe('accuracy-medium');
});

it('returns icon key for low confidence', function (): void {
    expect(LocationConfidence::LOW->iconKey())->toBe('accuracy-low');
});
