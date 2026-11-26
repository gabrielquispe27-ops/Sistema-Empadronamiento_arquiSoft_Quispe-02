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
    // Mismos datos que ya llegan a la vista, solo reformateados para Chart.js
    $chartCobertura = $zonasMetricas->map(fn ($z) => [
        'nombre' => (string) ($z['nombre'] ?? ''),
        'empadronados' => (int) ($z['empadronados'] ?? 0),
        'poblacion' => (int) ($z['poblacion_estimada'] ?? 0),
        'pct' => (float) ($z['porcentaje_cobertura'] ?? 0),
    ])->values();
@endphp

<div class="mb-4">
    <h1 class="page-title mb-1">Reportes de Avance y Cobertura por Zona</h1>
    <p class="page-subtitle mb-0">
        Avance del empadronamiento y cobertura poblacional por zona de emergencia para priorizar la ayuda humanitaria.
    </p>
</div>

<!-- Tarjetas de resumen global -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-navy h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div><div class="kpi-label">Personas empadronadas</div><div class="kpi-value">{{ number_format($totalEmpadronados) }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-steel h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div><div class="kpi-label">Zonas monitoreadas</div><div class="kpi-value">{{ $totalZonas }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-green h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                    <polyline points="16 7 22 7 22 13"/>
                </svg>
            </div>
            <div><div class="kpi-label">Cobertura global</div><div class="kpi-value">{{ $coberturaGlobal }}%</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-orange h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>
            </div>
            <div><div class="kpi-label">Zonas riesgo alto / crítico</div><div class="kpi-value">{{ $zonasCriticas }}</div></div>
        </div></div>
    </div>
</div>

<!-- Gráfico de avance de cobertura por zona (Chart.js) -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2 text-secondary"></i>Avance de cobertura por zona (%)</h2>
        <div class="d-flex flex-wrap gap-3 small text-muted">
            <span><i class="bi bi-square-fill me-1" style="color: #1f9254;"></i>≥ 75%</span>
            <span><i class="bi bi-square-fill me-1" style="color: #f0a30a;"></i>40 – 74%</span>
            <span><i class="bi bi-square-fill me-1" style="color: #c93a3a;"></i>&lt; 40%</span>
        </div>
    </div>
    <div class="card-body">
        <div id="chartCoberturaWrap" style="position: relative; height: 320px;">
            <canvas id="chartCobertura" role="img" aria-label="Porcentaje de cobertura por zona"></canvas>
        </div>
        <div id="chartCoberturaVacio" class="text-center text-muted py-5" hidden>
            <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>Aún no hay métricas por zona para graficar.
        </div>
    </div>
</div>

<!-- Detalle de cobertura por zona afectada -->
<div class="card">
    <div class="card-header py-3 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-secondary"></i>Cobertura de Empadronamiento por Zona Geográfica</h2>
        <!-- Buscador rápido en tiempo real (zona o nivel) -->
        @include('partials.buscador-tabla', [
            'tabla' => 'tablaReporteZonas',
            'cols' => [0, 1],
            'placeholder' => 'Filtrar por zona o nivel de afectación...',
        ])
    </div>
    <div class="table-responsive">
        <table id="tablaReporteZonas" class="table table-dc table-hover align-middle mb-0">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const canvas = document.getElementById('chartCobertura');
        const vacio = document.getElementById('chartCoberturaVacio');
        const wrap = document.getElementById('chartCoberturaWrap');
        if (!canvas) return;

        const datos = @json($chartCobertura);

        if (typeof Chart === 'undefined' || !datos.length) {
            if (wrap) wrap.hidden = true;
            if (vacio) vacio.hidden = false;
            return;
        }

        Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
        Chart.defaults.color = '#6b778c';

        // Mismos umbrales que las barras de progreso de la tabla
        const colores = datos.map(function (z) {
            return z.pct >= 75 ? '#1f9254' : (z.pct >= 40 ? '#f0a30a' : '#c93a3a');
        });
        const horizontal = window.innerWidth < 768;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: datos.map(function (z) { return z.nombre; }),
                datasets: [{
                    label: 'Cobertura (%)',
                    data: datos.map(function (z) { return z.pct; }),
                    backgroundColor: colores,
                    borderRadius: 8,
                    maxBarThickness: 44
                }]
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                const z = datos[ctx.dataIndex];
                                return [
                                    ' Cobertura: ' + z.pct + '%',
                                    ' Empadronados: ' + z.empadronados.toLocaleString('es-PE'),
                                    ' Población estimada: ' + z.poblacion.toLocaleString('es-PE')
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: horizontal
                        ? { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } }, grid: { color: '#eef1f6' } }
                        : { grid: { display: false } },
                    y: horizontal
                        ? { grid: { display: false } }
                        : { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } }, grid: { color: '#eef1f6' } }
                }
            }
        });
    })();
</script>
@endpush
