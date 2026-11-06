<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Entidad User (Componente MODELO - Persistencia y Datos).
 *
 * Representa a los actores del sistema (Brigadista y Coordinador de Defensa Civil)
 * y gestiona la emisión de tokens de acceso mediante Laravel Sanctum (ADR-004).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_BRIGADISTA = 'brigadista';
    public const ROLE_COORDINADOR = 'coordinador';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Atributos ocultos en la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Determina si el usuario tiene el rol de Coordinador de Defensa Civil.
     */
    public function esCoordinador(): bool
    {
        return $this->role === self::ROLE_COORDINADOR;
    }

    /**
     * Determina si el usuario tiene el rol de Brigadista.
     */
    public function esBrigadista(): bool
    {
        return $this->role === self::ROLE_BRIGADISTA;
    }
}
