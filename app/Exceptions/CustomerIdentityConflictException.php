<?php

namespace App\Exceptions;

use RuntimeException;

class CustomerIdentityConflictException extends RuntimeException
{
    public function __construct(public readonly string $field)
    {
        parent::__construct(match ($field) {
            'email' => 'Questo indirizzo email è già associato a un cliente. Accedi oppure contatta l’assistenza.',
            'phone' => 'Questo numero di telefono è già associato a un cliente. Accedi oppure contatta l’assistenza.',
            default => 'Esiste già un cliente con questi dati. Accedi oppure contatta l’assistenza.',
        });
    }
}
