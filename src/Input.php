<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/** Lecture et nettoyage des champs de formulaire. */
final class Input
{
    public static function string(string $key): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    public static function nullable(string $key): ?string
    {
        $value = self::string($key);

        return $value === '' ? null : $value;
    }

    public static function int(string $key): ?int
    {
        $value = filter_var(self::string($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? null : $value;
    }

    /** Retourne la date au format Y-m-d, ou null si absente ou invalide. */
    public static function date(string $key): ?string
    {
        $value = self::string($key);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
