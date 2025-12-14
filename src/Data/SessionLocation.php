<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Data;

use IntlTimeZone;
use Locale;
use Symfony\Component\Intl\Countries;
use Throwable;
use WillVincent\SessionManager\Enums\LocationConfidence;

final readonly class SessionLocation
{
    public function __construct(
        public ?string $countryCode,
        public ?string $region,
        public ?string $city,
        public ?string $timezone,
        public ?float $latitude,
        public ?float $longitude,
        public ?LocationConfidence $confidence = null,
        public string $source = 'maxmind',
    ) {}

    public function isApproximate(): bool
    {
        return $this->confidence?->isApproximate() ?? true;
    }

    public function confidenceLabel(): string
    {
        return $this->confidence?->label() ?? __('session-manager::confidence.approximate');
    }

    public function labelWithConfidence(
        bool $include_country = false,
        bool $with_tz = false,
        ?string $locale = null,
    ): string {
        $base = $this->label(
            include_country: $include_country,
            with_tz: $with_tz,
            locale: $locale,
        );

        if ($base === __('session-manager::unknown_location')) {
            return $base;
        }

        if ($this->isApproximate()) {
            return __('session-manager::near_location', ['location' => $base]);
        }

        return $base;
    }

    public function confidenceIcon(): string
    {
        return $this->confidence?->icon() ?? '○';
    }

    public function confidenceIconKey(): string
    {
        return $this->confidence?->iconKey() ?? 'accuracy-low';
    }

    public function label(
        bool $include_country = false,
        bool $with_tz = false,
        ?string $locale = null,
    ): string {
        $parts = array_filter([
            $this->city,
            $this->region,
            $include_country ? ($this->countryName($locale) ?? $this->countryCode) : null,
        ], fn (?string $v): bool => is_string($v) && mb_trim($v) !== '');

        if ($parts === []) {
            return __('session-manager::unknown_location');
        }

        $label = implode(', ', $parts);

        if ($with_tz && $this->timezone) {
            $tz = $this->timezoneLabel($locale) ?? $this->timezone;
            $label .= sprintf(' (%s)', $tz);
        }

        return $label;
    }

    /**
     * Returns localized country name if possible, else null.
     * Falls back to:
     *  - Symfony Intl Countries::getName() if installed
     *  - PHP intl Locale::getDisplayRegion() if available
     */
    public function countryName(?string $locale = null): ?string
    {
        $code = $this->countryCode ? mb_strtoupper(mb_trim($this->countryCode)) : null;

        if (! $code || mb_strlen($code) !== 2) {
            return null;
        }

        $locale ??= $this->appLocale();

        // Optional dependency: symfony/intl
        if (class_exists(Countries::class)) {
            /** @var class-string $countries */
            $countries = Countries::class;

            try {
                $name = $countries::getName($code, $locale);

                return $name ?: null;
                // @codeCoverageIgnoreStart
            } catch (Throwable) {
                // fall through
            }

            // @codeCoverageIgnoreEnd
        }

        // Built-in: ext-intl (Locale)
        if (class_exists(Locale::class)) {
            try {
                // Use "und-CC" to indicate "undefined language, country CC"
                $name = Locale::getDisplayRegion('und-'.$code, $locale);

                return $name ?: null;
                // @codeCoverageIgnoreStart
            } catch (Throwable) {
                // fall through
            }

            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Optional: pretty timezone label.
     * If ext-intl is available, returns localized timezone name, else null.
     */
    public function timezoneLabel(?string $locale = null): ?string
    {
        if (! $this->timezone) {
            return null;
        }

        $locale ??= $this->appLocale();

        if (class_exists(IntlTimeZone::class)) {
            try {
                $tz = IntlTimeZone::createTimeZone($this->timezone);

                // LONG_GENERIC gives "Central Time" style names when possible.
                $name = $tz->getDisplayName(false, IntlTimeZone::DISPLAY_LONG_GENERIC, $locale);

                return $name ?: null;
                // @codeCoverageIgnoreStart
            } catch (Throwable) {
                return null;
            }

            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    private function appLocale(): string
    {
        // Avoid hard dependency on Laravel helpers in case someone uses this DTO standalone.
        try {
            $app = function_exists('app') ? app() : null;
            $locale = $app ? $app->getLocale() : null;

            return is_string($locale) && $locale !== '' ? $locale : 'en';
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return 'en';
        }

        // @codeCoverageIgnoreEnd
    }
}
