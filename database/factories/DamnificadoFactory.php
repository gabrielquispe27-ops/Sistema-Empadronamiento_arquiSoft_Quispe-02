<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Damnificado;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Damnificado>
 */
class DamnificadoFactory extends Factory
{
    protected $model = Damnificado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'zona_id' => Zona::factory(),
            'danos_materiales' => 'Afectación estructural de vivienda por emergencia.',
            'registrado_por' => null,
        ];
    }
}
