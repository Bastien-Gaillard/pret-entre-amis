<?php

namespace App\Enum;

enum ItemCategory: string
{
    case Bricolage = 'bricolage';
    case Jardin = 'jardin';
    case Camping = 'camping';
    case Sport = 'sport';
    case Cuisine = 'cuisine';
    case Livre = 'livre';
    case Jeu = 'jeu';
    case Multimedia = 'multimedia';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Bricolage => 'Bricolage',
            self::Jardin => 'Jardin',
            self::Camping => 'Camping',
            self::Sport => 'Sport',
            self::Cuisine => 'Cuisine',
            self::Livre => 'Livre',
            self::Jeu => 'Jeu',
            self::Multimedia => 'Multimédia',
            self::Autre => 'Autre',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Bricolage => '🔧',
            self::Jardin => '🌱',
            self::Camping => '⛺',
            self::Sport => '🚲',
            self::Cuisine => '🍳',
            self::Livre => '📚',
            self::Jeu => '🎲',
            self::Multimedia => '🎧',
            self::Autre => '📦',
        };
    }
}
