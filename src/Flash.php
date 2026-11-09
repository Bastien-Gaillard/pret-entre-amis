<?php

declare(strict_types=1);

namespace App;

/** Messages affichés une seule fois, après une redirection. */
final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public static function pull(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $messages;
    }
}
