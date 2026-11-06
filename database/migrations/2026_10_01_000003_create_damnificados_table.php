<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla damnificados (Componente MODELO - Esquema Relacional MySQL).
 *
 * Implementa la restricción física UNIQUE (dni) como segunda barrera definitiva
 * de integridad para evitar registros duplicados en el padrón consolidado (RF03, AC03).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damnificados', function (Blueprint $table): void {
            $table->id();
            $table->char('dni', 8)->unique()->comment('Clave única física anti-duplicados (RF03, AC03)');
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->foreignId('zona_id')
                ->constrained('zonas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('danos_materiales');
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damnificados');
    }
};
