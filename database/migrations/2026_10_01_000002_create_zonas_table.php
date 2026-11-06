<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla zonas (Componente MODELO - Esquema Relacional MySQL).
 *
 * Representa las zonas geográficas afectadas por la emergencia y su población estimada
 * para el cálculo de cobertura de ayuda humanitaria (RF05, RF06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->enum('nivel_afectacion', ['bajo', 'medio', 'alto', 'critico'])->default('alto');
            $table->unsignedInteger('poblacion_estimada')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
