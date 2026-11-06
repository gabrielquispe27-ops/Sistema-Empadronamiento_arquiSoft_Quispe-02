@extends('layouts.app')

@section('title', 'Dashboard · Padrón Único Consolidado')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Padrón Único Consolidado de Damnificados</h1>
        <p class="text-muted small mb-0">
            Vista de explotación para el Coordinador de Defensa Civil (RF05, HU03) · Registros validados sin duplicados.
        </p>
    </div>

    <!-- Filtro por zona afectada (envía GET hacia PadronController::index) -->
    <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex gap-2">
        <select name="zona_id" class="form-select" onchange="this.form.submit()">
            <option value="">Todas las zonas afectadas</option>
            @foreach ($zonas as $zona)
                <option value="{{ $zona->id }}" @selected($zonaSeleccionada === $zona->id)>
                    {{ $zona->nombre }} ({{ $zona->damnificados_count }} reg.)
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline-primary">Filtrar</button>
    </form>
</div>

<!-- Tabla del padrón consolidado -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>DNI</th>
                    <th>Apellidos y Nombres</th>
                    <th>Zona Afectada</th>
                    <th>Daños Materiales Reportados</th>
                    <th>Última Actualización</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($damnificados as $persona)
                    <tr>
                        <td class="fw-bold font-monospace">{{ $persona->dni }}</td>
                        <td>{{ $persona->apellidos }}, {{ $persona->nombres }}</td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $persona->zona->nombre ?? 'Sin zona' }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $persona->danos_materiales }}</td>
                        <td class="small text-muted">{{ $persona->updated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No se encontraron registros consolidados para el criterio seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($damnificados, 'links'))
        <div class="card-footer bg-white">
            {{ $damnificados->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
