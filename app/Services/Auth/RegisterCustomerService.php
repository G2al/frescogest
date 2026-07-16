<?php

namespace App\Services\Auth;

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
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'email' => $email,
                'password' => Hash::make($data['password']),
            ]);

            $user->forceFill([
                'active' => true,
                'can_access_panel' => false,
            ])->save();

            $user->customer()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'phone' => trim($data['phone']),
                'phone_normalized' => $phone,
                'active' => true,
            ]);

            return $user->load('customer');
        });
    }
}
