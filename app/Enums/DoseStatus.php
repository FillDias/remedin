<?php

namespace App\Enums;

enum DoseStatus: string
{
    case Pending = 'pending';
    case Taken = 'taken';
    case Skipped = 'skipped';
    case Missed = 'missed';

    /**
     * Get the human readable label shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Taken => __('Taken'),
            self::Skipped => __('Skipped'),
            self::Missed => __('Missed'),
        };
    }
}
