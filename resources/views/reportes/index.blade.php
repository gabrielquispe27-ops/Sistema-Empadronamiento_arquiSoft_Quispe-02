@extends('layouts.app')

@section('title', 'Reportes Estadísticos por Zona · Defensa Civil')

@section('content')
@php
    $zonasMetricas = collect($metricas['zonas'] ?? []);
    $totalEmpadronados = (int) ($metricas['total_empadronados'] ?? 0);
    $totalZonas = (int) ($metricas['total_zonas'] ?? 0);
    $poblacionTotal = (int) $zonasMetricas->sum('poblacion_estimada');
    $coberturaGlobal = $poblacionTotal > 0 ? round($totalEmpadronados / $poblacionTotal * 100, 1) : 0;
    $zonasCriticas = $zonasMetricas->filter(fn ($z) => in_array(strtolower((string) ($z['nivel_afectacion'] ?? '')), ['critico', 'alto'], true))->count();
@endphp

<div class="mb-4">
    <h1 class="page-title mb-1">Reportes Estadísticos de Avance y Cobertura por Zona</h1>
    <p class="page-subtitle mb-0">
        Métricas consolidadas con respaldo en caché Redis para toma de decisiones logísticas (RF06, HU04).
    </p>
</div>

<!-- Tarjetas de resumen global -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-navy h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div><div class="kpi-label">Personas empadronadas</div><div class="kpi-value">{{ number_format($totalEmpadronados) }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-steel h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div><div class="kpi-label">Zonas monitoreadas</div><div class="kpi-value">{{ $totalZonas }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-green h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div><div class="kpi-label">Cobertura global</div><div class="kpi-value">{{ $coberturaGlobal }}%</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-orange h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div><div class="kpi-label">Zonas riesgo alto / crítico</div><div class="kpi-value">{{ $zonasCriticas }}</div></div>
        </div></div>
    </div>
</div>

<!-- Detalle de cobertura por zona afectada -->
<div class="card">
    <div class="card-header py-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-secondary"></i>Cobertura de Empadronamiento por Zona Geográfica</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-dc table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Zona Afectada</th>
                    <th>Nivel de Afectación</th>
                    <th class="text-end">Empadronados</th>
                    <th class="text-end">Población Estimada</th>
                    <th style="min-width: 220px; width: 30%;">Avance de Cobertura</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($metricas['zonas'] ?? []) as $zona)
                    @php
                        $pct = (float) $zona['porcentaje_cobertura'];
                        $colorBarra = $pct >= 75 ? 'bg-success' : ($pct >= 40 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <tr>
                        <td class="fw-semibold">
                            <i class="bi bi-geo-alt me-1" style="color: var(--dc-steel);"></i>{{ $zona['nombre'] }}
                        </td>
                        <td>@include('partials.nivel-badge', ['nivel' => $zona['nivel_afectacion']])</td>
                        <td class="text-end fw-bold">{{ number_format($zona['empadronados']) }}</td>
                        <td class="text-end text-muted">{{ number_format($zona['poblacion_estimada']) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 10px; border-radius: 99px; background: #e9eef5;">
                                    <div
                                        class="progress-bar {{ $colorBarra }}"
                                        role="progressbar"
                                        style="width: {{ $zona['porcentaje_cobertura'] }}%; border-radius: 99px;"
                                        aria-valuenow="{{ $zona['porcentaje_cobertura'] }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                                <span class="small fw-bold" style="min-width: 48px; text-align: right;">{{ $zona['porcentaje_cobertura'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No existen métricas de zonas registradas aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
