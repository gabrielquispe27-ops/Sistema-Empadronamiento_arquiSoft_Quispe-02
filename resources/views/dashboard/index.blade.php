@extends('layouts.app')

@section('title', 'Dashboard · Padrón Único Consolidado')

@section('content')
@php
    $listaZonas = collect($zonas ?? []);
    $totalRegistros = method_exists($damnificados, 'total') ? $damnificados->total() : count($damnificados);
    $zonasActivas = $listaZonas->count();
    $zonasCriticas = $listaZonas->filter(fn ($z) => in_array(strtolower((string) $z->nivel_afectacion), ['critico', 'alto'], true))->count();
    $zonaActual = $listaZonas->firstWhere('id', $zonaSeleccionada);
    // Mismos datos que ya llegan a la vista, solo reformateados para Chart.js
    $chartZonas = $listaZonas->map(fn ($z) => [
        'id' => $z->id,
        'nombre' => (string) $z->nombre,
        'total' => (int) ($z->damnificados_count ?? 0),
    ])->values();
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end mb-4 gap-3">
    <div>
        <h1 class="page-title mb-1">Padrón Único Consolidado de Damnificados</h1>
        <p class="page-subtitle mb-0">
            Vista de explotación para el Coordinador de Defensa Civil (RF05, HU03) · Registros validados sin duplicados.
        </p>
    </div>

    <!-- Filtro por zona afectada (envía GET hacia PadronController::index) -->
    <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex gap-2">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-funnel"></i></span>
            <select name="zona_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todas las zonas afectadas</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" @selected($zonaSeleccionada === $zona->id)>
                        {{ $zona->nombre }} ({{ $zona->damnificados_count }} reg.)
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search d-sm-none"></i><span class="d-none d-sm-inline">Filtrar</span></button>
    </form>
</div>

<!-- Tarjetas KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-navy h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div><div class="kpi-label">Registros {{ $zonaActual ? 'en zona' : 'totales' }}</div><div class="kpi-value">{{ number_format($totalRegistros) }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-steel h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div><div class="kpi-label">Zonas activas</div><div class="kpi-value">{{ $zonasActivas }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-orange h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div><div class="kpi-label">Zonas riesgo alto / crítico</div><div class="kpi-value">{{ $zonasCriticas }}</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-green h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon"><i class="bi bi-check2-circle"></i></div>
            <div><div class="kpi-label">Mostrando en página</div><div class="kpi-value">{{ count($damnificados) }}</div></div>
        </div></div>
    </div>
</div>

<!-- Gráfico de avance por zona (Chart.js) -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-secondary"></i>Avance de empadronamiento por zona</h2>
        <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de gráfico">
            <button type="button" class="btn btn-outline-primary active" data-tipo-grafico="doughnut"><i class="bi bi-pie-chart-fill me-1"></i>Dona</button>
            <button type="button" class="btn btn-outline-primary" data-tipo-grafico="bar"><i class="bi bi-bar-chart-fill me-1"></i>Barras</button>
        </div>
    </div>
    <div class="card-body">
        <div id="chartZonasWrap" style="position: relative; height: 300px;">
            <canvas id="chartZonas" role="img" aria-label="Registros por zona afectada"></canvas>
        </div>
        <div id="chartZonasVacio" class="text-center text-muted py-5" hidden>
            <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>Aún no hay registros por zona para graficar.
        </div>
    </div>
</div>

<!-- Tabla del padrón consolidado -->
<div class="card">
    <div class="card-header py-3 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-table me-2 text-secondary"></i>Listado consolidado</h2>
            @if ($zonaActual)
                <span class="small text-muted">Filtrado: <strong>{{ $zonaActual->nombre }}</strong></span>
            @endif
        </div>
        <!-- Buscador rápido en tiempo real (DNI, nombre o zona) -->
        @include('partials.buscador-tabla', [
            'tabla' => 'tablaPadron',
            'cols' => [0, 1, 2],
            'placeholder' => 'Buscar por DNI, nombre o zona (en esta página)...',
        ])
    </div>
    <div class="table-responsive">
        <table id="tablaPadron" class="table table-dc table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Apellidos y Nombres</th>
                    <th>Zona Afectada</th>
                    <th>Prioridad</th>
                    <th>Daños Materiales Reportados</th>
                    <th>Última Actualización</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($damnificados as $persona)
                    <tr>
                        <td class="fw-bold font-monospace">{{ $persona->dni }}</td>
                        <td class="fw-semibold">{{ $persona->apellidos }}, {{ $persona->nombres }}</td>
                        <td>
                            <span class="d-inline-flex align-items-center gap-1 small fw-semibold" style="color: var(--dc-steel);">
                                <i class="bi bi-geo-alt"></i>{{ $persona->zona->nombre ?? 'Sin zona' }}
                            </span>
                        </td>
                        <td>@include('partials.nivel-badge', ['nivel' => $persona->zona->nivel_afectacion ?? null])</td>
                        <td class="small text-muted" style="min-width: 240px; max-width: 380px;">{{ $persona->danos_materiales }}</td>
                        <td class="small text-muted text-nowrap">
                            <i class="bi bi-clock me-1"></i>{{ $persona->updated_at?->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No se encontraron registros consolidados para el criterio seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($damnificados, 'links'))
        <div class="card-footer py-3">
            {{ $damnificados->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const canvas = document.getElementById('chartZonas');
        const vacio = document.getElementById('chartZonasVacio');
        const wrap = document.getElementById('chartZonasWrap');
        if (!canvas) return;

        const datos = @json($chartZonas);
        const totalGeneral = datos.reduce(function (a, z) { return a + z.total; }, 0);

        if (typeof Chart === 'undefined' || !datos.length || totalGeneral === 0) {
            if (wrap) wrap.hidden = true;
            if (vacio) vacio.hidden = false;
            document.querySelectorAll('[data-tipo-grafico]').forEach(function (b) { b.disabled = true; });
            return;
        }

        Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
        Chart.defaults.color = '#6b778c';

        const paleta = ['#1e3a5f', '#2f5d8a', '#e8730c', '#1f9254', '#c93a3a', '#6b8fb8', '#f2a65a', '#5fb88a', '#8a6a00', '#7c5cbf'];
        const colores = datos.map(function (_, i) { return paleta[i % paleta.length]; });
        let grafico = null;

        function construir(tipo) {
            if (grafico) grafico.destroy();
            const esDona = tipo === 'doughnut';
            grafico = new Chart(canvas, {
                type: tipo,
                data: {
                    labels: datos.map(function (z) { return z.nombre; }),
                    datasets: [{
                        label: 'Registros',
                        data: datos.map(function (z) { return z.total; }),
                        backgroundColor: colores,
                        borderWidth: esDona ? 3 : 0,
                        borderColor: '#fff',
                        borderRadius: esDona ? 0 : 8,
                        maxBarThickness: 48,
                        hoverOffset: esDona ? 8 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: esDona ? '62%' : undefined,
                    animation: { duration: 700, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: esDona, position: window.innerWidth < 768 ? 'bottom' : 'right', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    const v = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed;
                                    const pct = totalGeneral ? (v / totalGeneral * 100).toFixed(1) : 0;
                                    return ' ' + v + ' registros (' + pct + '%)';
                                }
                            }
                        }
                    },
                    scales: esDona ? {} : {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef1f6' } }
                    }
                }
            });
        }

        construir('doughnut');

        document.querySelectorAll('[data-tipo-grafico]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('[data-tipo-grafico]').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                construir(btn.getAttribute('data-tipo-grafico'));
            });
        });
    })();
</script>
@endpush
