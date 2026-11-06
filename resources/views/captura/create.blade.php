@extends('layouts.app')

@section('title', 'Captura en Campo · Empadronamiento Mobile-First')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                <h1 class="h5 mb-0 fw-bold">Registro Rápido de Damnificado</h1>
                <span id="badgeConexion" class="badge bg-light text-primary">En línea</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Formulario optimizado para campo (<em>Mobile-First</em> - RF01). Ante microcortes de señal, el borrador se conserva automáticamente en el dispositivo (<code>localStorage</code>).
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="formCaptura" action="{{ route('captura.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="dni" class="form-label fw-semibold">DNI del Damnificado (8 dígitos)</label>
                        <input
                            type="text"
                            id="dni"
                            name="dni"
                            value="{{ old('dni') }}"
                            inputmode="numeric"
                            pattern="\d{8}"
                            maxlength="8"
                            class="form-control form-control-lg @error('dni') is-invalid @enderror"
                            placeholder="Ej. 45892310"
                            required
                        >
                        <div class="form-text">Validación inmediata de longitud e integridad única (RF02, RF03).</div>
                    </div>

                    <div class="mb-3">
                        <label for="nombres" class="form-label fw-semibold">Nombres</label>
                        <input
                            type="text"
                            id="nombres"
                            name="nombres"
                            value="{{ old('nombres') }}"
                            maxlength="120"
                            class="form-control form-control-lg @error('nombres') is-invalid @enderror"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="apellidos" class="form-label fw-semibold">Apellidos</label>
                        <input
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            value="{{ old('apellidos') }}"
                            maxlength="120"
                            class="form-control form-control-lg @error('apellidos') is-invalid @enderror"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="zona_id" class="form-label fw-semibold">Zona de Emergencia Afectada</label>
                        <select
                            id="zona_id"
                            name="zona_id"
                            class="form-select form-select-lg @error('zona_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Seleccione una zona...</option>
                            @foreach (($zonas ?? []) as $zona)
                                <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>
                                    {{ $zona->nombre }} ({{ strtoupper($zona->nivel_afectacion) }})
                                </option>
                            @endforeach
                            @if (empty($zonas))
                                <option value="1" @selected(old('zona_id') == 1)>Zona Norte - Sector A (CRÍTICO)</option>
                                <option value="2" @selected(old('zona_id') == 2)>Zona Centro - Sector B (ALTO)</option>
                                <option value="3" @selected(old('zona_id') == 3)>Zona Sur - Quebrada C (ALTO)</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="danos_materiales" class="form-label fw-semibold">Daños Materiales Reportados</label>
                        <textarea
                            id="danos_materiales"
                            name="danos_materiales"
                            rows="3"
                            maxlength="1000"
                            class="form-control @error('danos_materiales') is-invalid @enderror"
                            placeholder="Describa el estado de la vivienda, pérdida de enseres o servicios básicos..."
                            required
                        >{{ old('danos_materiales') }}</textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg fw-bold">
                            Enviar y Encolar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Respaldo local en navegador ante microcortes de señal en campo (RC07, DA02)
    (function () {
        const STORAGE_KEY = 'empadronamiento_borrador_actual';
        const form = document.getElementById('formCaptura');
        const campos = ['dni', 'nombres', 'apellidos', 'zona_id', 'danos_materiales'];

        if (!form) return;

        // Restaurar borrador si existe
        try {
            const guardado = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            campos.forEach(function (campo) {
                const input = document.getElementById(campo);
                if (input && !input.value && guardado[campo]) {
                    input.value = guardado[campo];
                }
            });
        } catch (e) {}

        // Guardar cambios en localStorage mientras el brigadista escribe
        form.addEventListener('input', function () {
            const estado = {};
            campos.forEach(function (campo) {
                const input = document.getElementById(campo);
                if (input) estado[campo] = input.value;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(estado));
        });

        // Limpiar borrador tras envío exitoso
        form.addEventListener('submit', function () {
            localStorage.removeItem(STORAGE_KEY);
        });
    })();
</script>
@endpush
