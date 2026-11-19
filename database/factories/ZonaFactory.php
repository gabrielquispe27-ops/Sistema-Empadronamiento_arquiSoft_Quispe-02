<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zona>
 */
class ZonaFactory extends Factory
{
    protected $model = Zona::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Zona ' . fake()->unique()->city(),
            'nivel_afectacion' => fake()->randomElement(['bajo', 'medio', 'alto', 'critico']),
            'poblacion_estimada' => fake()->numberBetween(200, 2500),
        ];
    }
}
