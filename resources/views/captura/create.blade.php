@extends('layouts.app')

@section('title', 'Captura en Campo · Empadronamiento Mobile-First')

@push('styles')
<style>
    .captura-section { font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--dc-steel); display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; }
    .captura-section .paso { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: var(--dc-navy); color: #fff; font-size: .72rem; letter-spacing: 0; }
    .captura-section::after { content: ''; flex: 1; height: 1px; background: var(--dc-border); }

    /* Bloque DNI destacado */
    .dni-group { background: var(--dc-steel-soft); border: 2px solid #d5e0ec; border-radius: 16px; padding: 1.1rem; transition: border-color .3s ease, background-color .3s ease, box-shadow .3s ease; }
    .dni-group:focus-within { border-color: var(--dc-steel); box-shadow: 0 0 0 .25rem rgba(47, 93, 138, .12); }
    .dni-group .form-control { font-size: 1.6rem; font-weight: 700; letter-spacing: .25em; font-variant-numeric: tabular-nums; text-align: center; }
    .dni-group .input-group-text { background: #fff; transition: border-color .3s ease; min-width: 54px; justify-content: center; }
    #dniEstado { min-height: 1.3rem; font-size: .8rem; color: var(--dc-muted); display: flex; align-items: center; gap: .45rem; transition: color .3s ease; }
    #dniSpinner { display: none; }
    #dniSpinner.is-visible { display: inline-block; }
    #dniCheck { display: none; color: var(--dc-green); font-size: 1.5rem; line-height: 1; }

    /* Feedback visual: RENIEC autocompleta con éxito */
    .dni-group.is-success { border-color: var(--dc-green); background: #effaf4; box-shadow: 0 0 0 .25rem rgba(31, 146, 84, .13); }
    .dni-group.is-success .form-control,
    .dni-group.is-success .input-group-text { border-color: var(--dc-green); }
    .dni-group.is-success .form-control { color: #146c3d; }
    .dni-group.is-success #dniEstado { color: #146c3d; font-weight: 600; }
    .dni-group.is-success #dniCheck { display: inline-block; animation: checkPop .55s cubic-bezier(.34, 1.56, .64, 1) both; }
    @keyframes checkPop { 0% { transform: scale(0) rotate(-90deg); opacity: 0; } 100% { transform: scale(1) rotate(0); opacity: 1; } }
    .campo-autocompletado { border-color: var(--dc-green) !important; background-color: #f6fcf8 !important; animation: campoFlash .9s ease; }
    @keyframes campoFlash { 0% { box-shadow: 0 0 0 .3rem rgba(31, 146, 84, .3); } 100% { box-shadow: 0 0 0 0 rgba(31, 146, 84, 0); } }

    .form-control-lg, .form-select-lg { min-height: 54px; font-size: 1.02rem; }

    /* Zona de emergencia */
    .zona-box { background: #f8fafc; border: 1px solid var(--dc-border); border-radius: 16px; padding: 1.1rem; }
    .zona-nivel { display: none; margin-top: .75rem; }
    .zona-nivel.is-visible { display: flex; align-items: center; gap: .5rem; font-size: .8rem; color: var(--dc-muted); animation: dcFadeIn .3s ease both; }

    .btn-enviar { position: relative; min-height: 60px; font-size: 1.1rem; letter-spacing: .01em; box-shadow: 0 8px 20px rgba(31, 146, 84, .3); }
    .btn-enviar:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(31, 146, 84, .38); }
    .btn-enviar .spinner-border { display: none; }
    .btn-enviar.is-loading { pointer-events: none; opacity: .92; }
    .btn-enviar.is-loading .spinner-border { display: inline-block; }
    .btn-enviar.is-loading .bi-send-check-fill { display: none; }
    .captura-card .card-header h1 { font-size: 1.1rem; font-weight: 800; margin: 0; }

    /* Espacio inferior para que la barra móvil no tape el botón de registro */
    .captura-page { padding-bottom: 1rem; }
    @media (max-width: 767.98px) {
        .captura-page { padding-bottom: calc(var(--dc-bottom-nav-h) + 2rem + env(safe-area-inset-bottom)); }
        .captura-card .card-body { padding: 1.1rem !important; }
        .captura-card { border-radius: 18px; }
        .dni-group .form-control { font-size: 1.45rem; }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center captura-page">
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

                    <div class="captura-section"><span class="paso">1</span><i class="bi bi-person-vcard"></i> Identificación</div>

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
                                <i id="dniCheck" class="bi bi-check-circle-fill"></i>
                            </span>
                        </div>
                        <div id="dniEstado" class="mt-2" aria-live="polite">
                            <i id="dniEstadoIcono" class="bi bi-shield-check"></i>
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

                    <div class="captura-section"><span class="paso">2</span><i class="bi bi-geo-alt"></i> Ubicación y daños</div>

                    <div class="zona-box mb-4">
                        <label for="zona_id" class="form-label fw-semibold">Zona de Emergencia Afectada</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-geo-alt-fill" style="color: var(--dc-orange);"></i></span>
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
                        <div id="zonaNivel" class="zona-nivel" aria-live="polite"></div>

                        <label for="danos_materiales" class="form-label fw-semibold mt-4">Daños Materiales Reportados</label>
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
                        <button type="submit" id="btnEnviar" class="btn btn-success btn-lg btn-enviar fw-bold">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <i class="bi bi-send-check-fill me-2"></i><span class="btn-enviar-texto">Enviar y Encolar Registro</span>
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

        // Efecto de carga en el botón de registro (solo presentación)
        const btnEnviar = document.getElementById('btnEnviar');
        const textoBtnOriginal = btnEnviar ? btnEnviar.querySelector('.btn-enviar-texto').textContent : '';
        form.addEventListener('submit', function () {
            if (!btnEnviar) return;
            btnEnviar.classList.add('is-loading');
            btnEnviar.querySelector('.btn-enviar-texto').textContent = 'Enviando registro...';
            // Se deshabilita tras despachar el envío para evitar doble registro
            setTimeout(function () { btnEnviar.disabled = true; }, 0);
        });
        window.addEventListener('pageshow', function (e) {
            if (e.persisted && btnEnviar) {
                btnEnviar.classList.remove('is-loading');
                btnEnviar.disabled = false;
                btnEnviar.querySelector('.btn-enviar-texto').textContent = textoBtnOriginal;
            }
        });

        // Indicador de nivel de afectación de la zona elegida (solo presentación)
        const zonaSelect = document.getElementById('zona_id');
        const zonaNivel = document.getElementById('zonaNivel');
        function mostrarNivelZona() {
            if (!zonaSelect || !zonaNivel) return;
            const opt = zonaSelect.options[zonaSelect.selectedIndex];
            const m = opt && opt.value ? opt.text.trim().match(/\(([^()]+)\)\s*$/) : null;
            if (!m) {
                zonaNivel.classList.remove('is-visible');
                zonaNivel.innerHTML = '';
                return;
            }
            const nivel = m[1].toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const clase = ['critico', 'alto', 'medio', 'bajo'].indexOf(nivel) >= 0 ? nivel : 'nd';
            zonaNivel.innerHTML = 'Nivel de afectación: <span class="badge-nivel badge-nivel-' + clase + '">' + m[1] + '</span>';
            zonaNivel.classList.add('is-visible');
        }
        if (zonaSelect) {
            zonaSelect.addEventListener('change', mostrarNivelZona);
            mostrarNivelZona();
        }

        // Autocompletado de Nombres y Apellidos al ingresar un DNI de 8 dígitos
        const dniInput = document.getElementById('dni');
        const nombresInput = document.getElementById('nombres');
        const apellidosInput = document.getElementById('apellidos');

        // Indicadores visuales de consulta RENIEC (solo presentación)
        const dniGroup = dniInput ? dniInput.closest('.dni-group') : null;
        const dniSpinner = document.getElementById('dniSpinner');
        const dniIcono = document.getElementById('dniIcono');
        const dniEstadoTexto = document.getElementById('dniEstadoTexto');
        const dniEstadoIcono = document.getElementById('dniEstadoIcono');
        const textoEstadoOriginal = dniEstadoTexto ? dniEstadoTexto.textContent : '';

        function setConsultando(activo) {
            if (dniSpinner) dniSpinner.classList.toggle('is-visible', activo);
            if (dniIcono) dniIcono.style.display = activo ? 'none' : '';
            if (dniEstadoTexto) {
                dniEstadoTexto.textContent = activo ? 'Consultando RENIEC...' : textoEstadoOriginal;
            }
        }

        // Éxito RENIEC: borde verde + check animado
        function mostrarExito() {
            if (dniGroup) dniGroup.classList.add('is-success');
            if (dniIcono) dniIcono.style.display = 'none';
            if (dniEstadoTexto) dniEstadoTexto.textContent = 'Datos verificados con RENIEC. Revise nombres y apellidos.';
            if (dniEstadoIcono) dniEstadoIcono.className = 'bi bi-patch-check-fill';
        }
        function limpiarExito() {
            if (dniGroup) dniGroup.classList.remove('is-success');
            if (dniEstadoIcono) dniEstadoIcono.className = 'bi bi-shield-check';
            [nombresInput, apellidosInput].forEach(function (el) {
                if (el) el.classList.remove('campo-autocompletado');
            });
        }
        function marcarAutocompletado(el) {
            el.classList.remove('campo-autocompletado');
            void el.offsetWidth; // reinicia la animación
            el.classList.add('campo-autocompletado');
        }

        if (dniInput && nombresInput && apellidosInput) {
            const placeholderNombresOriginal = nombresInput.getAttribute('placeholder') || '';
            const placeholderApellidosOriginal = apellidosInput.getAttribute('placeholder') || '';
            let ultimoDniConsultado = '';

            dniInput.addEventListener('input', function () {
                const dni = dniInput.value.trim();

                if (!/^\d{8}$/.test(dni)) {
                    ultimoDniConsultado = '';
                    limpiarExito();
                    if (dniIcono) dniIcono.style.display = '';
                    if (dniEstadoTexto) dniEstadoTexto.textContent = textoEstadoOriginal;
                    return;
                }

                if (dni === ultimoDniConsultado) {
                    return;
                }

                ultimoDniConsultado = dni;
                limpiarExito();
                let autocompletado = false;
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
                                marcarAutocompletado(nombresInput);
                                autocompletado = true;
                            }
                            if (data.apellidos) {
                                apellidosInput.value = data.apellidos;
                                apellidosInput.dispatchEvent(new Event('input', { bubbles: true }));
                                marcarAutocompletado(apellidosInput);
                                autocompletado = true;
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
                        // Solo si el DNI sigue siendo el mismo que se consultó
                        if (autocompletado && dniInput.value.trim() === dni) {
                            mostrarExito();
                        }
                    });
            });
        }
    })();
</script>
@endpush
