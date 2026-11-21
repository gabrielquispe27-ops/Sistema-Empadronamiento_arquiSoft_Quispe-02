@extends('layouts.app')

@section('title', 'Dashboard · Padrón Único Consolidado')

@section('content')
@php
    $listaZonas = collect($zonas ?? []);
    $totalRegistros = method_exists($damnificados, 'total') ? $damnificados->total() : count($damnificados);
    $zonasActivas = $listaZonas->count();
    $zonasCriticas = $listaZonas->filter(fn ($z) => in_array(strtolower((string) $z->nivel_afectacion), ['critico', 'alto'], true))->count();
    $zonaActual = $listaZonas->firstWhere('id', $zonaSeleccionada);
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

<!-- Tabla del padrón consolidado -->
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-table me-2 text-secondary"></i>Listado consolidado</h2>
        @if ($zonaActual)
            <span class="small text-muted">Filtrado: <strong>{{ $zonaActual->nombre }}</strong></span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-dc table-hover align-middle mb-0">
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
