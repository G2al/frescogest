<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'vat_number' => fake()->unique()->numerify('###########'),
            'tax_code' => fake()->optional()->numerify('###########'),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'province' => fake()->randomElement(self::PROVINCES),
            'iban' => fake()->optional()->iban('IT'),
            'logo_path' => null,
            'active' => fake()->boolean(90),
        ];
    }

    private const PROVINCES = [
        'NA', 'AV', 'BN', 'CE', 'SA', 'RM', 'MI', 'TO', 'BO', 'FI', 'BA', 'PA',
    ];
}
