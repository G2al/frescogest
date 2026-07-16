<?php

namespace App\Services\Customers;

use App\Exceptions\CustomerIdentityConflictException;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateCustomerProfileService
{
    public function __construct(private readonly CustomerIdentityNormalizer $normalizer) {}

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $customer = $user->customer()->lockForUpdate()->firstOrFail();
            $email = array_key_exists('email', $data) ? $this->normalizer->email($data['email']) : $customer->email;
            $phone = array_key_exists('phone', $data) ? $this->normalizer->phone($data['phone']) : $customer->phone_normalized;

            if (Customer::query()->whereKeyNot($customer->id)->where('email', $email)->exists()) {
                throw new CustomerIdentityConflictException('email');
            }

            if ($phone !== null && Customer::query()->whereKeyNot($customer->id)->where('phone_normalized', $phone)->exists()) {
                throw new CustomerIdentityConflictException('phone');
            }

            $customerData = Arr::only($data, [
                'company_name', 'first_name', 'last_name', 'email', 'phone', 'billing_address',
                'delivery_address', 'city', 'postal_code', 'province', 'vat_number', 'tax_code', 'notes',
            ]);

            if (array_key_exists('province', $customerData)) {
                $customerData['province'] = strtoupper((string) $customerData['province']);
            }

            if (array_key_exists('email', $customerData)) {
                $customerData['email'] = $email;
                $user->update(['email' => $email]);
            }

            if (array_key_exists('phone', $customerData)) {
                $customerData['phone_normalized'] = $phone;
            }

            foreach (['vat_number', 'tax_code'] as $identifier) {
                if (array_key_exists($identifier, $customerData)) {
                    $customerData[$identifier] = $this->normalizer->identifier($customerData[$identifier]);

                    if ($customerData[$identifier] !== null && Customer::query()
                        ->whereKeyNot($customer->id)
                        ->where($identifier, $customerData[$identifier])
                        ->exists()) {
                        throw new CustomerIdentityConflictException($identifier);
                    }
                }
            }

            $customer->update($customerData);
            $user->update(['name' => $customer->display_name]);

            return $user->fresh('customer');
        });
    }
}
