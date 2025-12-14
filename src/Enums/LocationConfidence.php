<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Enums;

enum LocationConfidence: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function isApproximate(): bool
    {
        return $this !== self::HIGH;
    }

    public function label(): string
    {
        return __('session-manager::confidence.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::HIGH => '●',
            self::MEDIUM => '◐',
            self::LOW => '○',
        };
    }

    public function iconLabel(): string
    {
        return $this->label(); // reuse your translation-backed label
    }

    public function iconKey(): string
    {
        return match ($this) {
            self::HIGH => 'accuracy-high',
            self::MEDIUM => 'accuracy-medium',
            self::LOW => 'accuracy-low',
        };
    }
}
