<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpadronamientoController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web (Componente CONTROLADOR - Enrutamiento HTTP y SSR con Blade)
|--------------------------------------------------------------------------
| Mapea las peticiones HTTP entrantes hacia los controladores de cada módulo
| aplicando el middleware de autenticación Laravel Sanctum (ADR-004).
*/

// Módulo 1: Autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('logout');

// Rutas protegidas por Laravel Sanctum
Route::middleware(['auth:sanctum'])->group(function (): void {
    // Módulo 2: Captura / Empadronamiento en Campo (Brigadista y Coordinador)
    Route::get('/captura', [EmpadronamientoController::class, 'create'])->name('captura.create');
    Route::post('/captura', [EmpadronamientoController::class, 'store'])->name('captura.store');

    // Módulo 3: Padrón Consolidado (Coordinador de Defensa Civil - RF05)
    Route::get('/dashboard', [PadronController::class, 'index'])
        ->middleware('role:coordinador')
        ->name('dashboard.index');

    // Módulo 4: Reportes Estadísticos por Zona (Coordinador de Defensa Civil - RF06)
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('role:coordinador')
        ->name('reportes.index');
});
