<?php

namespace App\Exception;

use DomainException;

final class ItemAlreadyLentException extends DomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Cet objet est déjà prêté.', 0, $previous);
    }
}
