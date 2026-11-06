<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad Zona (Componente MODELO - Persistencia y Datos).
 *
 * Representa una zona geográfica afectada por la emergencia y su nivel de afectación,
 * agrupando los registros de damnificados asociados para el padrón y reportes (RF05, RF06).
 */
class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'nivel_afectacion',
        'poblacion_estimada',
    ];

    /**
     * Relación 1:N con los damnificados empadronados en la zona.
     */
    public function damnificados(): HasMany
    {
        return $this->hasMany(Damnificado::class, 'zona_id');
    }
}
