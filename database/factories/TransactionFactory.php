<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['depot', 'paiement', 'retrait', 'transfert']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000),
            'reference' => $this->faker->optional()->uuid(),
        ];
    }
}
