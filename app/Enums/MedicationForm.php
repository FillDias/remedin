<?php

namespace App\Enums;

enum MedicationForm: string
{
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Softgel = 'softgel';
    case Effervescent = 'effervescent';
    case Drops = 'drops';
    case Liquid = 'liquid';
    case Powder = 'powder';
    case Other = 'other';

    /**
     * Get the human readable label shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Tablet => __('Tablet'),
            self::Capsule => __('Capsule'),
            self::Softgel => __('Softgel'),
            self::Effervescent => __('Effervescent'),
            self::Drops => __('Drops'),
            self::Liquid => __('Liquid'),
            self::Powder => __('Powder'),
            self::Other => __('Other'),
        };
    }

    /**
     * Get the unit suggested in the form when this form is chosen. The user can change it.
     */
    public function defaultDoseUnit(): string
    {
        return match ($this) {
            self::Tablet, self::Effervescent => __('tablet unit'),
            self::Capsule, self::Softgel => __('capsule unit'),
            self::Drops => __('drops unit'),
            self::Liquid => __('ml unit'),
            self::Powder => __('scoop unit'),
            self::Other => __('unit'),
        };
    }

    /**
     * Determine if the stock of this form can be counted in units.
     *
     * Drops, liquids and powders are measured, not counted, so stock control is disabled for them.
     */
    public function tracksStock(): bool
    {
        return match ($this) {
            self::Tablet, self::Capsule, self::Softgel, self::Effervescent => true,
            self::Drops, self::Liquid, self::Powder, self::Other => false,
        };
    }
}
