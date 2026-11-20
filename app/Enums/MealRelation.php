<?php

namespace App\Enums;

enum MealRelation: string
{
    case Before = 'before';
    case With = 'with';
    case After = 'after';
    case Any = 'any';

    /**
     * Get the human readable label shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Before => __('Before meals'),
            self::With => __('With meals'),
            self::After => __('After meals'),
            self::Any => __('Regardless of meals'),
        };
    }
}
