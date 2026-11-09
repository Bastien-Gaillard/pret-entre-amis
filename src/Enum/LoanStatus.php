<?php

namespace App\Enum;

enum LoanStatus: string
{
    case EnCours = 'en_cours';
    case EnRetard = 'en_retard';
    case Rendu = 'rendu';

    public function label(): string
    {
        return match ($this) {
            self::EnCours => 'En cours',
            self::EnRetard => 'En retard',
            self::Rendu => 'Rendu',
        };
    }
}
