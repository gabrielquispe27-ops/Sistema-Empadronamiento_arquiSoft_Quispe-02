<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpadronamientoController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API (Componente CONTROLADOR - Endpoints protegidos con Sanctum)
|--------------------------------------------------------------------------
| Permite la sincronización de registros retenidos en localStorage desde
| dispositivos móviles en campo ante microcortes de conectividad (RC07, ADR-004).
*/

Route::post('/auth/token', [AuthController::class, 'login'])->name('api.auth.token');

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::post('/captura', [EmpadronamientoController::class, 'store'])->name('api.captura.store');
    Route::get('/padron', [PadronController::class, 'index'])
        ->middleware('role:coordinador')
        ->name('api.padron.index');
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('role:coordinador')
        ->name('api.reportes.index');
});
