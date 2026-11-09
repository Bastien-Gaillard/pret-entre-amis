<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    /** À appeler au début de chaque requête POST. */
    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            throw new HttpException(419);
        }
    }
}
