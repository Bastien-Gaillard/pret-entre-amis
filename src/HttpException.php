<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class HttpException extends RuntimeException
{
    private const MESSAGES = [
        400 => 'Requête invalide',
        404 => 'Page introuvable',
        405 => 'Méthode non autorisée',
        419 => 'Session expirée, merci de recharger la page',
    ];

    public function __construct(public readonly int $status)
    {
        parent::__construct(self::MESSAGES[$status] ?? 'Erreur', $status);
    }
}
