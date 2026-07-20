<?php

namespace App\Services\Auth;

use App\Enums\CustomerType;
use App\Exceptions\CustomerIdentityConflictException;
use App\Models\Customer;
use App\Models\User;
use App\Services\Customers\CustomerIdentityNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterCustomerService
{
    public function __construct(private readonly CustomerIdentityNormalizer $normalizer) {}

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $email = $this->normalizer->email($data['email']);
            $phone = $this->normalizer->phone($data['phone']);

            if (Customer::query()->where('email', $email)->lockForUpdate()->exists()) {
                throw new CustomerIdentityConflictException('email');
            }

            if ($phone !== null && Customer::query()->where('phone_normalized', $phone)->lockForUpdate()->exists()) {
                throw new CustomerIdentityConflictException('phone');
            }

            $user = User::query()->create([
                'name' => $this->displayName($data),
                'email' => $email,
                'password' => Hash::make($data['password']),
            ]);

            $user->forceFill([
                'active' => true,
                'can_access_panel' => false,
            ])->save();

            $user->customer()->create([
                'type' => $data['type'],
                'company_name' => $data['type'] === CustomerType::Restaurant->value ? trim($data['company_name']) : null,
                'first_name' => filled($data['first_name'] ?? null) ? trim($data['first_name']) : null,
                'last_name' => filled($data['last_name'] ?? null) ? trim($data['last_name']) : null,
                'email' => $email,
                'phone' => trim($data['phone']),
                'phone_normalized' => $phone,
                'active' => true,
            ]);

            return $user->load('customer');
        });
    }

    private function displayName(array $data): string
    {
        if ($data['type'] === CustomerType::Restaurant->value) {
            return trim($data['company_name']);
        }

        return trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
    }
}
