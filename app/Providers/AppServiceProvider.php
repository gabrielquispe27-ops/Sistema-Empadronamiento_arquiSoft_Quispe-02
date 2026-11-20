<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FastPersonalAccessToken;
use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use App\Repositories\Contracts\DeduplicacionCacheInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ZonaRepositoryInterface;
use App\Repositories\EloquentDamnificadoRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\EloquentZonaRepository;
use App\Repositories\RedisDeduplicacionCache;
use App\Services\Contracts\EmpadronamientoServiceInterface;
use App\Services\EmpadronamientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DamnificadoRepositoryInterface::class,
            EloquentDamnificadoRepository::class
        );

        $this->app->bind(
            DeduplicacionCacheInterface::class,
            RedisDeduplicacionCache::class
        );

        $this->app->bind(
            ZonaRepositoryInterface::class,
            EloquentZonaRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            EmpadronamientoServiceInterface::class,
            EmpadronamientoService::class
        );
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(FastPersonalAccessToken::class);

        Validator::extend('zona_valida', function ($attribute, $value): bool {
            if (app()->runningUnitTests()) {
                return DB::table('zonas')->where('id', $value)->exists();
            }

            static $zonaIds = null;
            static $expiresAt = 0;

            if ($zonaIds === null || time() > $expiresAt) {
                $zonaIds = array_flip(DB::table('zonas')->pluck('id')->all());
                $expiresAt = time() + 60;
            }

            return isset($zonaIds[(int) $value]);
        }, 'La zona seleccionada no es válida.');
    }
}
