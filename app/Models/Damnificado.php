<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Damnificado (Componente MODELO - Persistencia y Datos).
 *
 * Representa a una persona damnificada dentro del padrón único consolidado.
 * Garantiza la persistencia idempotente sobre la restricción física UNIQUE(dni) en MySQL (AC03).
 */
class Damnificado extends Model
{
    use HasFactory;

    protected $table = 'damnificados';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'zona_id',
        'danos_materiales',
        'registrado_por',
    ];

    /**
     * Relación N:1 con la zona de emergencia donde fue empadronado.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Ejecuta la inserción o actualización idempotente del registro en el padrón consolidado,
     * apoyándose en el índice físico UNIQUE (dni) de MySQL (RF03, AC03).
     *
     * @param  array<string, mixed>  $datos
     */
    public static function upsertPadron(array $datos): void
    {
        self::query()->upsert(
            [
                [
                    'dni' => (string) $datos['dni'],
                    'nombres' => (string) $datos['nombres'],
                    'apellidos' => (string) $datos['apellidos'],
                    'zona_id' => (int) $datos['zona_id'],
                    'danos_materiales' => (string) ($datos['danos_materiales'] ?? ''),
                    'registrado_por' => isset($datos['registrado_por']) ? (int) $datos['registrado_por'] : null,
                ],
            ],
            ['dni'],
            ['nombres', 'apellidos', 'zona_id', 'danos_materiales', 'updated_at']
        );
    }
}
