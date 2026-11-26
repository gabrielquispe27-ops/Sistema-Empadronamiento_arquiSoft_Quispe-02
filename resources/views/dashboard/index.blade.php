@extends('layouts.app')

@section('title', 'Padrón Consolidado de Damnificados · Defensa Civil')

@push('styles')
<style>
    .kpi-filter { cursor: pointer; user-select: none; }
    .kpi-filter.is-active { border: 2px solid var(--dc-orange) !important; box-shadow: 0 0 0 .2rem rgba(243, 156, 18, .18); }
    .kpi-hint { font-size: .68rem; color: var(--dc-muted); margin-top: .15rem; }
    .gps-pill { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .5rem; border-radius: 999px; background: #e1f4ea; color: #146c3d; font-size: .7rem; font-weight: 700; text-decoration: none; border: 1px solid #b6e2c9; transition: background-color .15s ease; }
    .gps-pill:hover { background: #c9ebd9; color: #0f3d24; }
    .danos-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; cursor: pointer; line-height: 1.45; }
    .danos-clamp.is-expanded { -webkit-line-clamp: unset; overflow: visible; }
</style>
@endpush

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
        <h1 class="page-title mb-1">Padrón Consolidado de Damnificados</h1>
        <p class="page-subtitle mb-0">
            Consulta general de familias y personas empadronadas por zona de emergencia.
        </p>
    </div>

    <!-- Filtro por zona afectada -->
    <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex gap-2">
        <div class="input-group">
            <span class="input-group-text" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </span>
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

<!-- Tarjetas de resumen (clicables como filtros rápidos de la tabla) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-navy kpi-filter is-active h-100" role="button" tabindex="0" data-kpi-filter="all" title="Mostrar todos los registros de la tabla"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div><div class="kpi-label">Registros {{ $zonaActual ? 'en zona' : 'totales' }}</div><div class="kpi-value">{{ number_format($totalRegistros) }}</div><div class="kpi-hint">Ver todos</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-steel kpi-filter h-100" role="button" tabindex="0" data-kpi-filter="gps" title="Filtrar registros con coordenadas GPS verificadas"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div><div class="kpi-label">Zonas activas</div><div class="kpi-value">{{ $zonasActivas }}</div><div class="kpi-hint">Filtrar con GPS</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-orange kpi-filter h-100" role="button" tabindex="0" data-kpi-filter="critico-alto" title="Filtrar damnificados en zonas de riesgo alto o crítico"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>
            </div>
            <div><div class="kpi-label">Zonas riesgo alto / crítico</div><div class="kpi-value">{{ $zonasCriticas }}</div><div class="kpi-hint">Filtrar prioridad alta</div></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-green kpi-filter h-100" role="button" tabindex="0" data-kpi-filter="all" title="Restablecer listado de la página"><div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="m9 11 3 3L22 4"/>
                </svg>
            </div>
            <div><div class="kpi-label">Mostrando en página</div><div class="kpi-value">{{ count($damnificados) }}</div><div class="kpi-hint">Página actual</div></div>
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
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-table me-2 text-secondary"></i>Listado consolidado</h2>
            @if ($zonaActual)
                <span class="small text-muted">· Zona: <strong>{{ $zonaActual->nombre }}</strong></span>
            @endif
            <span id="badgeFiltroRapido" class="badge rounded-pill text-bg-warning d-none"></span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Buscador rápido en tiempo real (DNI, nombre o zona) -->
            @include('partials.buscador-tabla', [
                'tabla' => 'tablaPadron',
                'cols' => [0, 1, 2],
                'placeholder' => 'Buscar por DNI, nombre o zona...',
            ])
            <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm no-print" title="Imprimir o guardar padrón en PDF">
                <svg class="dc-svg me-1" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Imprimir
            </button>
        </div>
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
                    @php
                        $nivelFila = strtolower((string) ($persona->zona->nivel_afectacion ?? 'nd'));
                        $tieneGps = $persona->latitud !== null && $persona->longitud !== null;
                    @endphp
                    <tr data-nivel="{{ $nivelFila }}" data-gps="{{ $tieneGps ? '1' : '0' }}">
                        <td class="fw-bold font-monospace">{{ $persona->dni }}</td>
                        <td class="fw-semibold">{{ $persona->apellidos }}, {{ $persona->nombres }}</td>
                        <td>
                            <div class="d-flex flex-column align-items-start gap-1">
                                <span class="d-inline-flex align-items-center gap-1 small fw-semibold" style="color: var(--dc-steel);">
                                    <i class="bi bi-geo-alt"></i>{{ $persona->zona->nombre ?? 'Sin zona' }}
                                </span>
                                @if ($tieneGps)
                                    <a
                                        href="https://www.openstreetmap.org/?mlat={{ $persona->latitud }}&mlon={{ $persona->longitud }}#map=18/{{ $persona->latitud }}/{{ $persona->longitud }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="gps-pill"
                                        title="Ver ubicación GPS exacta en el mapa ({{ $persona->latitud }}, {{ $persona->longitud }})"
                                    >
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                                        </svg>
                                        <span>GPS{{ $persona->precision_gps ? ' ±'.$persona->precision_gps.'m' : '' }}</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td>@include('partials.nivel-badge', ['nivel' => $persona->zona->nivel_afectacion ?? null])</td>
                        <td class="small text-muted" style="min-width: 240px; max-width: 380px;">
                            <div class="danos-clamp" title="Toque para expandir o contraer el texto completo">{{ $persona->danos_materiales }}</div>
                        </td>
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

        // Filtros rápidos al pulsar las tarjetas KPI + expansión de reportes largos de daños
        const tabla = document.getElementById('tablaPadron');
        const badgeFiltro = document.getElementById('badgeFiltroRapido');
        const tarjetasKpi = document.querySelectorAll('.kpi-filter');

        if (tabla) {
            tabla.addEventListener('click', function (e) {
                const clamp = e.target.closest('.danos-clamp');
                if (clamp) clamp.classList.toggle('is-expanded');
            });

            function aplicarFiltroKpi(modo, cardActiva) {
                tarjetasKpi.forEach(function (c) { c.classList.toggle('is-active', c === cardActiva); });
                const filas = tabla.tBodies[0] ? tabla.tBodies[0].querySelectorAll('tr[data-nivel]') : [];
                filas.forEach(function (tr) {
                    const nivel = tr.getAttribute('data-nivel');
                    const gps = tr.getAttribute('data-gps') === '1';
                    let mostrar = true;
                    if (modo === 'critico-alto') mostrar = (nivel === 'critico' || nivel === 'alto');
                    else if (modo === 'gps') mostrar = gps;
                    tr.hidden = !mostrar;
                });
                if (badgeFiltro) {
                    const textos = { 'critico-alto': 'Filtro: Riesgo alto / crítico', 'gps': 'Filtro: Con ubicación GPS' };
                    if (textos[modo]) {
                        badgeFiltro.textContent = textos[modo];
                        badgeFiltro.classList.remove('d-none');
                    } else {
                        badgeFiltro.classList.add('d-none');
                    }
                }
            }

            tarjetasKpi.forEach(function (card) {
                card.addEventListener('click', function () {
                    aplicarFiltroKpi(card.getAttribute('data-kpi-filter') || 'all', card);
                });
                card.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        card.click();
                    }
                });
            });
        }
    })();
</script>
@endpush
