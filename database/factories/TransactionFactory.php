<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $description = $this->faker->company();
        $amount = $this->faker->randomFloat(2, -300, -5);
        $date = $this->faker->dateTimeBetween('-6 months')->format('Y-m-d');

        return [
            'account_id' => Account::factory(),
            'booked_on' => $date,
            'amount' => $amount,
            'currency' => 'EUR',
            'raw_description' => $description,
            'description' => $description,
            'fingerprint' => Transaction::fingerprintFor(1, $date, (string) $amount, $description),
            'occurrence' => 1,
        ];
    }
}
