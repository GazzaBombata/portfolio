<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Conto principale', 'Carta', 'Conto risparmio']),
            'bank' => $this->faker->randomElement(['Intesa', 'Fineco', 'Revolut']),
            'iban_last4' => (string) $this->faker->numberBetween(1000, 9999),
            'currency' => 'EUR',
            'active' => true,
        ];
    }
}
