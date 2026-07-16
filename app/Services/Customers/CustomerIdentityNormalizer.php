<?php

namespace App\Services\Customers;

class CustomerIdentityNormalizer
{
    public function email(?string $email): ?string
    {
        $normalized = mb_strtolower(trim((string) $email));

        return $normalized === '' ? null : $normalized;
    }

    public function phone(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone);

        return $normalized === '' ? null : $normalized;
    }

    public function identifier(?string $identifier): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '', (string) $identifier));

        return $normalized === '' ? null : $normalized;
    }
}
