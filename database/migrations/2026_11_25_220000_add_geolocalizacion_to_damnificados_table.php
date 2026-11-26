<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la geolocalización GPS (opcional) capturada en campo a la tabla damnificados.
 * Las tres columnas son nullable: el GPS nunca bloquea el registro del damnificado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damnificados', function (Blueprint $table): void {
            $table->decimal('latitud', 10, 7)->nullable()->after('danos_materiales');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->unsignedInteger('precision_gps')->nullable()->after('longitud')->comment('Precisión del GPS en metros');
        });
    }

    public function down(): void
    {
        Schema::table('damnificados', function (Blueprint $table): void {
            $table->dropColumn(['latitud', 'longitud', 'precision_gps']);
        });
    }
};
