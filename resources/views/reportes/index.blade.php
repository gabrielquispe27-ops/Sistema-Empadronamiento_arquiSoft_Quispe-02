@extends('layouts.app')

@section('title', 'Reportes Estadísticos por Zona · Defensa Civil')

@section('content')
<div class="mb-4">
    <h1 class="h4 fw-bold mb-1">Reportes Estadísticos de Avance y Cobertura por Zona</h1>
    <p class="text-muted small mb-0">
        Métricas consolidadas con respaldo en caché Redis para toma de decisiones logísticas (RF06, HU04).
    </p>
</div>

<!-- Tarjetas de resumen global -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total de Personas Empadronadas</div>
                <div class="display-6 fw-bold text-primary mt-1">
                    {{ number_format($metricas['total_empadronados'] ?? 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Zonas de Emergencia Monitoreadas</div>
                <div class="display-6 fw-bold text-dark mt-1">
                    {{ $metricas['total_zonas'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de cobertura por zona afectada -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h6 fw-bold mb-0">Cobertura de Empadronamiento por Zona Geográfica</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zona Afectada</th>
                    <th>Nivel de Afectación</th>
                    <th class="text-end">Empadronados</th>
                    <th class="text-end">Población Estimada</th>
                    <th style="width: 30%;">Avance de Cobertura</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($metricas['zonas'] ?? []) as $zona)
                    <tr>
                        <td class="fw-semibold">{{ $zona['nombre'] }}</td>
                        <td>
                            <span class="badge bg-warning text-dark text-uppercase">
                                {{ $zona['nivel_afectacion'] }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">{{ number_format($zona['empadronados']) }}</td>
                        <td class="text-end text-muted">{{ number_format($zona['poblacion_estimada']) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 10px;">
                                    <div
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        style="width: {{ $zona['porcentaje_cobertura'] }}%;"
                                        aria-valuenow="{{ $zona['porcentaje_cobertura'] }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                                <span class="small fw-semibold">{{ $zona['porcentaje_cobertura'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No existen métricas de zonas registradas aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
