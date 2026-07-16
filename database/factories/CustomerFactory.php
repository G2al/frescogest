<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = fake()->unique()->phoneNumber();

        return [
            'company_name' => fake()->company(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'vat_number' => fake()->unique()->numerify('###########'),
            'tax_code' => fake()->unique()->regexify('[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'phone_normalized' => preg_replace('/\D+/', '', $phone),
            'billing_address' => fake()->streetAddress(),
            'delivery_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'province' => fake()->randomElement(self::PROVINCES),
            'notes' => fake()->sentence(),
            'active' => true,
        ];
    }

    private const PROVINCES = [
        'NA', 'AV', 'BN', 'CE', 'SA', 'RM', 'MI', 'TO', 'BO', 'FI', 'BA', 'PA',
    ];
}
