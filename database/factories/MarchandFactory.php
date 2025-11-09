<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Marchand>
 */
class MarchandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marchandsSenegal = [
            'Orange Money',
            'Wave',
            'Free Money',
            'Expresso',
            'Sunu Transfer',
            'Wizall Money',
            'Yoban Pay',
            'PayDunya',
            'Kirene',
            'Joni Joni'
        ];

        $nom = $this->faker->randomElement($marchandsSenegal);

        return [
            'nom' => $nom,
            'code' => strtoupper(substr($nom, 0, 3)) . $this->faker->unique()->numberBetween(100, 999),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
