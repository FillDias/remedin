<?php

namespace App\Enums;

enum BrazilianTimezone: string
{
    case Noronha = 'America/Noronha';
    case Brasilia = 'America/Sao_Paulo';
    case Amazon = 'America/Manaus';
    case Acre = 'America/Rio_Branco';

    /**
     * Get the human readable label shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Noronha => __('Fernando de Noronha (UTC-2)'),
            self::Brasilia => __('Brasília (UTC-3)'),
            self::Amazon => __('Amazon (UTC-4)'),
            self::Acre => __('Acre (UTC-5)'),
        };
    }
}
