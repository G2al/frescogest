<?php

namespace App\Exceptions;

use RuntimeException;

class CustomerIdentityConflictException extends RuntimeException
{
    public function __construct(public readonly string $field)
    {
        parent::__construct('Esiste già un\'anagrafica cliente con questi dati. Contatta l\'assistenza.');
    }
}
