<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Principal DatabaseSeeder (Componente MODELO - Datos Iniciales y de Prueba).
 *
 * Crea los actores de prueba del sistema (Brigadista de Campo y Coordinador de Defensa Civil)
 * y las zonas geográficas afectadas iniciales para validación funcional y pruebas de carga.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta el sembrado inicial de la base de datos.
     */
    public function run(): void
    {
        // 1. Usuarios de prueba (Brigadista y Coordinador de Defensa Civil)
        User::query()->updateOrCreate(
            ['email' => 'brigadista@defensacivil.gob.pe'],
            [
                'name' => 'Juan Pérez (Brigadista de Campo)',
                'password' => Hash::make('Brigadista2026!'),
                'role' => User::ROLE_BRIGADISTA,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'coordinador@defensacivil.gob.pe'],
            [
                'name' => 'María Quispe (Coordinadora de Defensa Civil)',
                'password' => Hash::make('Coordinador2026!'),
                'role' => User::ROLE_COORDINADOR,
                'email_verified_at' => now(),
            ]
        );

        // 2. Zonas geográficas de emergencia iniciales
        $zonasIniciales = [
            [
                'nombre' => 'Sector Huamanga Centro',
                'nivel_afectacion' => 'critico',
                'poblacion_estimada' => 1500,
            ],
            [
                'nombre' => 'Distrito San Juan Bautista',
                'nivel_afectacion' => 'alto',
                'poblacion_estimada' => 1200,
            ],
            [
                'nombre' => 'Distrito Carmen Alto',
                'nivel_afectacion' => 'alto',
                'poblacion_estimada' => 950,
            ],
            [
                'nombre' => 'Distrito Jesús Nazareno',
                'nivel_afectacion' => 'medio',
                'poblacion_estimada' => 800,
            ],
            [
                'nombre' => 'Sector Mollepata',
                'nivel_afectacion' => 'bajo',
                'poblacion_estimada' => 450,
            ],
        ];

        foreach ($zonasIniciales as $zona) {
            Zona::query()->updateOrCreate(
                ['nombre' => $zona['nombre']],
                [
                    'nivel_afectacion' => $zona['nivel_afectacion'],
                    'poblacion_estimada' => $zona['poblacion_estimada'],
                ]
            );
        }
    }
}
