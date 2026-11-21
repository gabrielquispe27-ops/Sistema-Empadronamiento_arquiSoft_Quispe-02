@extends('layouts.app')

@section('title', 'Captura en Campo · Empadronamiento Mobile-First')

@push('styles')
<style>
    .captura-section { font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--dc-steel); display: flex; align-items: center; gap: .5rem; margin-bottom: .9rem; }
    .captura-section::after { content: ''; flex: 1; height: 1px; background: var(--dc-border); }
    .dni-group { background: var(--dc-steel-soft); border: 1px solid #d5e0ec; border-radius: 14px; padding: 1rem; }
    .dni-group .form-control { font-size: 1.5rem; font-weight: 700; letter-spacing: .25em; font-variant-numeric: tabular-nums; text-align: center; }
    .dni-group .input-group-text { background: #fff; }
    #dniEstado { min-height: 1.3rem; font-size: .8rem; color: var(--dc-muted); display: flex; align-items: center; gap: .45rem; }
    #dniSpinner { display: none; }
    #dniSpinner.is-visible { display: inline-block; }
    .form-control-lg, .form-select-lg { min-height: 54px; font-size: 1.02rem; }
    .btn-enviar { min-height: 58px; font-size: 1.08rem; letter-spacing: .01em; box-shadow: 0 6px 16px rgba(31, 146, 84, .28); }
    .captura-card .card-header h1 { font-size: 1.1rem; font-weight: 800; margin: 0; }
    @media (max-width: 575.98px) {
        .captura-card .card-body { padding: 1.1rem !important; }
        .captura-card { border-radius: 12px; }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-9 col-lg-7 col-xl-6">
        <div class="card captura-card border-0">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 py-3 px-3 px-sm-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill fs-4" style="color: var(--dc-orange);"></i>
                    <h1>Registro Rápido de Damnificado</h1>
                </div>
                <span id="badgeConexion" class="badge-conexion"><i class="bi bi-wifi"></i> En línea</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-2 text-muted small mb-4">
                    <i class="bi bi-info-circle mt-1"></i>
                    <span>Formulario optimizado para campo (<em>Mobile-First</em> - RF01). Ante microcortes de señal, el borrador se conserva automáticamente en el dispositivo (<code>localStorage</code>).</span>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger d-flex">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                        <ul class="mb-0 small ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="formCaptura" action="{{ route('captura.store') }}" method="POST">
                    @csrf

                    <div class="captura-section"><i class="bi bi-person-vcard"></i> Identificación</div>

                    <div class="dni-group mb-3">
                        <label for="dni" class="form-label fw-semibold">DNI del Damnificado (8 dígitos)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-credit-card-2-front"></i></span>
                            <input
                                type="text"
                                id="dni"
                                name="dni"
                                value="{{ old('dni') }}"
                                inputmode="numeric"
                                pattern="\d{8}"
                                maxlength="8"
                                class="form-control @error('dni') is-invalid @enderror"
                                placeholder="45892310"
                                autocomplete="off"
                                required
                            >
                            <span class="input-group-text" aria-hidden="true">
                                <span id="dniSpinner" class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                                <i id="dniIcono" class="bi bi-search"></i>
                            </span>
                        </div>
                        <div id="dniEstado" class="mt-2" aria-live="polite">
                            <i class="bi bi-shield-check"></i>
                            <span id="dniEstadoTexto">Validación inmediata de longitud e integridad única (RF02, RF03). Se autocompletará con RENIEC.</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label for="nombres" class="form-label fw-semibold">Nombres</label>
                            <input
                                type="text"
                                id="nombres"
                                name="nombres"
                                value="{{ old('nombres') }}"
                                maxlength="120"
                                class="form-control form-control-lg @error('nombres') is-invalid @enderror"
                                autocomplete="off"
                                required
                            >
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="apellidos" class="form-label fw-semibold">Apellidos</label>
                            <input
                                type="text"
                                id="apellidos"
                                name="apellidos"
                                value="{{ old('apellidos') }}"
                                maxlength="120"
                                class="form-control form-control-lg @error('apellidos') is-invalid @enderror"
                                autocomplete="off"
                                required
                            >
                        </div>
                    </div>

                    <div class="captura-section"><i class="bi bi-geo-alt"></i> Ubicación y daños</div>

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
                            rows="4"
                            maxlength="1000"
                            class="form-control form-control-lg @error('danos_materiales') is-invalid @enderror"
                            placeholder="Describa el estado de la vivienda, pérdida de enseres o servicios básicos..."
                            required
                        >{{ old('danos_materiales') }}</textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg btn-enviar fw-bold">
                            <i class="bi bi-send-check-fill me-2"></i>Enviar y Encolar Registro
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

        // Autocompletado de Nombres y Apellidos al ingresar un DNI de 8 dígitos
        const dniInput = document.getElementById('dni');
        const nombresInput = document.getElementById('nombres');
        const apellidosInput = document.getElementById('apellidos');

        // Indicadores visuales de consulta RENIEC (solo presentación)
        const dniSpinner = document.getElementById('dniSpinner');
        const dniIcono = document.getElementById('dniIcono');
        const dniEstadoTexto = document.getElementById('dniEstadoTexto');
        const textoEstadoOriginal = dniEstadoTexto ? dniEstadoTexto.textContent : '';

        function setConsultando(activo) {
            if (dniSpinner) dniSpinner.classList.toggle('is-visible', activo);
            if (dniIcono) dniIcono.style.display = activo ? 'none' : '';
            if (dniEstadoTexto) {
                dniEstadoTexto.textContent = activo ? 'Consultando RENIEC...' : textoEstadoOriginal;
            }
        }

        if (dniInput && nombresInput && apellidosInput) {
            const placeholderNombresOriginal = nombresInput.getAttribute('placeholder') || '';
            const placeholderApellidosOriginal = apellidosInput.getAttribute('placeholder') || '';
            let ultimoDniConsultado = '';

            dniInput.addEventListener('input', function () {
                const dni = dniInput.value.trim();

                if (!/^\d{8}$/.test(dni)) {
                    ultimoDniConsultado = '';
                    return;
                }

                if (dni === ultimoDniConsultado) {
                    return;
                }

                ultimoDniConsultado = dni;
                nombresInput.setAttribute('placeholder', 'Consultando RENIEC...');
                apellidosInput.setAttribute('placeholder', 'Consultando RENIEC...');
                setConsultando(true);

                fetch('/captura/consultar-dni/' + encodeURIComponent(dni), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.ok) {
                            if (data.nombres) {
                                nombresInput.value = data.nombres;
                                nombresInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            if (data.apellidos) {
                                apellidosInput.value = data.apellidos;
                                apellidosInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                    })
                    .catch(function () {
                        // Falla silenciosa o sin red: permite escritura manual sin bloquear el formulario
                    })
                    .finally(function () {
                        nombresInput.setAttribute('placeholder', placeholderNombresOriginal);
                        apellidosInput.setAttribute('placeholder', placeholderApellidosOriginal);
                        setConsultando(false);
                    });
            });
        }
    })();
</script>
@endpush
