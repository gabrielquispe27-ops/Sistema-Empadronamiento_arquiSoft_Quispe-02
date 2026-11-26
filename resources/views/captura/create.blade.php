@extends('layouts.app')

@section('title', 'Captura en Campo · Empadronamiento Mobile-First')

@push('styles')
<style>
    /* Reporte de daños: lectura limpia y formal en móvil (15px, interlineado relajado, gris oscuro) */
    textarea#danos_materiales.form-control {
        font-size: 15px;
        line-height: 1.625;
        color: #1f2937;
    }
    textarea#danos_materiales.form-control::placeholder { color: #8a94a6; }
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

    /* Modo offline: avisos y panel de cola */
    .avisos-captura { position: fixed; top: 76px; left: 50%; transform: translateX(-50%); width: min(94vw, 480px); z-index: 1080; display: flex; flex-direction: column; gap: .5rem; pointer-events: none; }
    .avisos-captura .alert { pointer-events: auto; margin: 0; font-size: .88rem; }
    .alert-warning { background: #fff4dc; color: #7a5200; border-left: 4px solid #f0a30a; }
    .alert-info { background: #e3effa; color: #1e4a73; border-left: 4px solid var(--dc-steel); }
    .cola-panel { background: linear-gradient(135deg, #fff8e8, #fff1d6); border: 1px solid #f3d9a0; border-radius: 16px; padding: .9rem 1rem; box-shadow: 0 4px 14px rgba(240, 163, 10, .15); animation: dcFadeIn .3s ease both; }
    .cola-icono { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: #ffe2a3; color: #9a5b00; font-size: 1.3rem; flex-shrink: 0; }
    .cola-titulo { font-weight: 700; font-size: .92rem; color: #6b4300; }
    .cola-numero { display: inline-block; min-width: 1.6em; padding: 0 .4em; text-align: center; border-radius: 999px; background: #9a5b00; color: #fff; }
    .cola-toggle { border: 0; background: transparent; padding: 0; font-size: .78rem; font-weight: 600; color: #9a5b00; }
    .cola-toggle .bi { display: inline-block; transition: transform .2s ease; }
    .cola-toggle[aria-expanded="true"] .bi { transform: rotate(180deg); }
    .cola-lista { margin-top: .75rem !important; border-top: 1px dashed #e8c987; max-height: 190px; overflow-y: auto; }
    .cola-item { display: flex; align-items: center; gap: .6rem; padding: .5rem .1rem; border-bottom: 1px solid #f3e3bd; font-size: .85rem; }
    .cola-item:last-child { border-bottom: 0; }
    .cola-item-nombre { font-weight: 600; color: var(--dc-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .btn-sincronizar .spinner-border { display: none; }
    .btn-sincronizar.is-loading .spinner-border { display: inline-block; }
    .btn-sincronizar.is-loading .bi-arrow-repeat { display: none; }
    @media (max-width: 575.98px) { .btn-sincronizar { width: 100%; } }

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
                <!-- Avisos flotantes del modo offline -->
                <div id="avisosCaptura" class="avisos-captura" aria-live="polite"></div>

                <!-- Panel de registros pendientes de sincronización (oculto si la cola está vacía) -->
                <div id="panelCola" class="cola-panel mb-4" hidden>
                    <div class="d-flex align-items-center gap-3 flex-wrap flex-sm-nowrap">
                        <div class="cola-icono"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="cola-titulo"><span id="colaContador" class="cola-numero">0</span> <span id="colaTexto">registros pendientes de envío</span></div>
                            <button class="cola-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#colaDetalle" aria-expanded="false" aria-controls="colaDetalle">
                                Ver detalle <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                        <button id="btnSincronizar" type="button" class="btn btn-sm btn-warning btn-sincronizar">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            <i class="bi bi-arrow-repeat me-1"></i>Sincronizar ahora
                        </button>
                    </div>
                    <div class="collapse" id="colaDetalle">
                        <ul id="colaLista" class="cola-lista list-unstyled mb-0"></ul>
                    </div>
                </div>

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

                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mt-4 mb-1">
                            <label for="danos_materiales" class="form-label fw-semibold mb-0">Daños Materiales Reportados</label>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="btnMejorarIa" class="btn btn-outline-primary btn-sm" title="Corrige y redacta el reporte con IA (requiere internet)">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-stars me-1"></i><span class="btn-ia-texto">Mejorar con IA</span>
                                </button>
                            </div>
                        </div>
                        <div id="iaEstado" class="small mb-2 d-none" role="status" aria-live="polite"></div>
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

        // ============================================================
        // MODO OFFLINE REAL: cola de sincronización en localStorage
        // El borrador actual se limpia solo tras envío exitoso o tras encolar offline.
        // ============================================================
        const COLA_KEY = 'cola_offline_empadronamiento';
        const btnEnviar = document.getElementById('btnEnviar');
        const textoBtnOriginal = btnEnviar ? btnEnviar.querySelector('.btn-enviar-texto').textContent : '';
        const panelCola = document.getElementById('panelCola');
        const colaContador = document.getElementById('colaContador');
        const colaTexto = document.getElementById('colaTexto');
        const colaLista = document.getElementById('colaLista');
        const btnSincronizar = document.getElementById('btnSincronizar');
        const contAvisos = document.getElementById('avisosCaptura');
        let enviando = false;
        let sincronizando = false;

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function leerCola() {
            try {
                const c = JSON.parse(localStorage.getItem(COLA_KEY) || '[]');
                return Array.isArray(c) ? c : [];
            } catch (e) { return []; }
        }
        function guardarCola(cola) {
            try {
                localStorage.setItem(COLA_KEY, JSON.stringify(cola));
            } catch (e) { return false; }
            window.dispatchEvent(new CustomEvent('cola-offline:cambio'));
            return true;
        }
        function quitarDeCola(dni) {
            guardarCola(leerCola().filter(function (r) { return r.dni !== dni; }));
        }
        function tokenCsrf() {
            const t = form.querySelector('input[name="_token"]');
            return t ? t.value : '';
        }

        // Avisos flotantes (success / warning / danger / info)
        function aviso(tipo, html, ms) {
            if (!contAvisos) return;
            const iconos = { success: 'check-circle-fill', warning: 'exclamation-triangle-fill', danger: 'x-octagon-fill', info: 'info-circle-fill' };
            const div = document.createElement('div');
            div.className = 'alert alert-' + tipo + ' alert-dismissible fade show d-flex align-items-start shadow';
            div.setAttribute('role', 'alert');
            div.innerHTML = '<i class="bi bi-' + (iconos[tipo] || 'info-circle-fill') + ' me-2 mt-1"></i><div class="pe-3">' + html + '</div>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
            contAvisos.appendChild(div);
            if (ms) {
                setTimeout(function () {
                    if (!div.isConnected) return;
                    if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(div).close();
                    else div.remove();
                }, ms);
            }
        }

        // Panel compacto de pendientes (oculto si la cola está vacía)
        function refrescarPanel() {
            const cola = leerCola();
            if (!panelCola) return;
            panelCola.hidden = cola.length === 0;
            if (colaContador) colaContador.textContent = cola.length;
            if (colaTexto) colaTexto.textContent = cola.length === 1 ? 'registro pendiente de envío' : 'registros pendientes de envío';
            if (colaLista) {
                colaLista.innerHTML = cola.map(function (r) {
                    const hora = r.fecha_hora ? new Date(r.fecha_hora).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) : '';
                    return '<li class="cola-item">' +
                        '<span class="font-monospace fw-bold">' + esc(r.dni) + '</span>' +
                        '<span class="cola-item-nombre">' + esc(r.apellidos) + ', ' + esc(r.nombres) + '</span>' +
                        '<small class="text-muted ms-auto text-nowrap">' + esc(r.zona_nombre || '') + (hora ? ' · ' + hora : '') + '</small></li>';
                }).join('');
            }
        }

        function setCargando(activo) {
            if (!btnEnviar) return;
            btnEnviar.classList.toggle('is-loading', activo);
            btnEnviar.disabled = activo;
            btnEnviar.querySelector('.btn-enviar-texto').textContent = activo ? 'Enviando registro...' : textoBtnOriginal;
        }

        function leerFormulario() {
            const get = function (id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; };
            const sel = document.getElementById('zona_id');
            const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
            return {
                dni: get('dni'),
                nombres: get('nombres'),
                apellidos: get('apellidos'),
                zona_id: sel ? sel.value : '',
                zona_nombre: opt && opt.value ? opt.text.replace(/\s+/g, ' ').trim() : '',
                danos_materiales: get('danos_materiales'),
                fecha_hora: new Date().toISOString()
            };
        }

        // Deja el formulario listo para la siguiente persona (sin tocar ids ni names)
        function limpiarFormulario() {
            campos.forEach(function (campo) {
                const el = document.getElementById(campo);
                if (el) el.value = '';
            });
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            const dniEl = document.getElementById('dni');
            if (dniEl) dniEl.dispatchEvent(new Event('input', { bubbles: true })); // reinicia estado RENIEC
            const zonaEl = document.getElementById('zona_id');
            if (zonaEl) zonaEl.dispatchEvent(new Event('change'));
            localStorage.removeItem(STORAGE_KEY);
            if (dniEl) dniEl.focus();
        }

        function encolarOffline(reg, mensajeHtml) {
            const cola = leerCola();
            if (cola.some(function (r) { return r.dni === reg.dni; })) {
                aviso('warning', 'El DNI <strong>' + esc(reg.dni) + '</strong> ya está en la cola local pendiente de envío. No se duplicó el registro.', 8000);
                return false;
            }
            cola.push(reg);
            if (!guardarCola(cola)) {
                aviso('danger', 'No se pudo guardar el registro en el dispositivo (almacenamiento lleno o bloqueado).');
                return false;
            }
            limpiarFormulario();
            refrescarPanel();
            aviso('warning', mensajeHtml || '<strong>Sin conexión.</strong> Registro guardado en el dispositivo. Se enviará al recuperar la señal.', 7000);
            return true;
        }

        function enviarRegistro(reg) {
            const fd = new FormData();
            fd.append('_token', tokenCsrf());
            ['dni', 'nombres', 'apellidos', 'zona_id', 'danos_materiales'].forEach(function (k) { fd.append(k, reg[k]); });
            return fetch(form.action, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': tokenCsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
        }

        // Si el token CSRF caducó (419), se obtiene uno nuevo de la propia página
        function refrescarToken() {
            return fetch(window.location.href, { credentials: 'same-origin' })
                .then(function (r) {
                    if (r.redirected && /\/login/.test(r.url)) return false;
                    return r.text().then(function (html) {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const nuevo = doc.querySelector('#formCaptura input[name="_token"]');
                        const actual = form.querySelector('input[name="_token"]');
                        if (nuevo && actual) { actual.value = nuevo.value; return true; }
                        return false;
                    });
                })
                .catch(function () { return false; });
        }

        // Envía con reintento único ante 419
        function enviarConReintento(reg) {
            return enviarRegistro(reg).then(function (res) {
                if (res.status !== 419) return res;
                return refrescarToken().then(function (ok) {
                    return ok ? enviarRegistro(reg) : res;
                });
            });
        }

        function sesionExpirada(res) {
            return res.status === 401 || res.status === 419 || (res.redirected && /\/login/.test(res.url));
        }

        function mensajesDeError(res) {
            return res.json().then(function (j) {
                if (j && j.errors) {
                    return Object.keys(j.errors).reduce(function (acc, k) { return acc.concat(j.errors[k]); }, []).join(' ');
                }
                return (j && j.message) || '';
            }).catch(function () { return ''; });
        }

        // Interceptar el envío del formulario (en línea → fetch; sin red → cola local)
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (enviando) return;
            const reg = leerFormulario();

            if (!navigator.onLine) {
                encolarOffline(reg);
                return;
            }

            enviando = true;
            setCargando(true);

            enviarConReintento(reg)
                .then(function (res) {
                    if (sesionExpirada(res)) {
                        encolarOffline(reg, '<strong>Sesión expirada.</strong> Registro guardado en el dispositivo. Inicie sesión de nuevo para sincronizarlo.');
                        return null;
                    }
                    if (res.status === 422 || res.status === 409) {
                        return mensajesDeError(res).then(function (msg) {
                            aviso('warning', esc(msg || (res.status === 409 ? 'El DNI ya se encuentra registrado.' : 'Revise los datos del formulario.')), 9000);
                            const dniEl = document.getElementById('dni');
                            if (dniEl && res.status === 409) dniEl.classList.add('is-invalid');
                        });
                    }
                    if (res.ok || res.redirected) {
                        limpiarFormulario();
                        aviso('success', '<strong>Registro enviado.</strong> Recibido y encolado para procesamiento asíncrono.', 5000);
                        return null;
                    }
                    encolarOffline(reg, '<strong>El servidor no respondió correctamente (HTTP ' + res.status + ').</strong> Registro guardado en el dispositivo para reintentar.');
                    return null;
                })
                .catch(function () {
                    // Error de red durante el fetch: se conserva el registro localmente
                    encolarOffline(reg);
                })
                .finally(function () {
                    enviando = false;
                    setCargando(false);
                });
        });
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { enviando = false; setCargando(false); refrescarPanel(); }
        });

        // Sincronización uno por uno de los registros pendientes
        async function sincronizar(manual) {
            if (sincronizando) return;
            if (!navigator.onLine) {
                if (manual) aviso('warning', '<strong>Sin conexión.</strong> Los registros se enviarán automáticamente al recuperar la señal.', 6000);
                return;
            }
            const pendientes = leerCola();
            if (!pendientes.length) return;

            sincronizando = true;
            if (btnSincronizar) { btnSincronizar.disabled = true; btnSincronizar.classList.add('is-loading'); }

            let enviados = 0;
            const rechazados = [];
            let interrumpido = false;

            for (const reg of pendientes) {
                try {
                    const res = await enviarConReintento(reg);
                    if (sesionExpirada(res)) {
                        aviso('warning', '<strong>Sesión expirada.</strong> Recargue la página e inicie sesión para sincronizar los pendientes.');
                        interrumpido = true;
                        break;
                    }
                    if (res.status === 422 || res.status === 409) {
                        const msg = await mensajesDeError(res);
                        rechazados.push('DNI <strong>' + esc(reg.dni) + '</strong>: ' + esc(msg || 'rechazado por el servidor'));
                        quitarDeCola(reg.dni); // no bloquea al resto
                        refrescarPanel();
                        continue;
                    }
                    if (res.ok || res.redirected) {
                        quitarDeCola(reg.dni);
                        enviados++;
                        refrescarPanel();
                        continue;
                    }
                    aviso('warning', 'El servidor respondió HTTP ' + res.status + '. Se reintentará más tarde.', 7000);
                    interrumpido = true;
                    break;
                } catch (err) {
                    aviso('warning', 'Conexión inestable. Quedan registros pendientes; se reintentará al volver la señal.', 7000);
                    interrumpido = true;
                    break;
                }
            }

            sincronizando = false;
            if (btnSincronizar) { btnSincronizar.disabled = false; btnSincronizar.classList.remove('is-loading'); }
            refrescarPanel();

            if (rechazados.length) {
                aviso('warning', '<strong>' + rechazados.length + ' registro(s) no se pudieron registrar y se descartaron de la cola:</strong><br>' + rechazados.join('<br>'));
            }
            if (!interrumpido && leerCola().length === 0 && enviados > 0) {
                aviso('success', '<strong>Sincronización completa.</strong> ' + enviados + (enviados === 1 ? ' registro enviado' : ' registros enviados') + ' correctamente.', 7000);
            }
        }

        if (btnSincronizar) btnSincronizar.addEventListener('click', function () { sincronizar(true); });
        window.addEventListener('online', function () {
            if (leerCola().length) {
                aviso('info', '<strong>Conexión recuperada.</strong> Sincronizando registros pendientes...', 4000);
                sincronizar(false);
            }
        });
        window.addEventListener('storage', function (e) { if (e.key === COLA_KEY) refrescarPanel(); });

        refrescarPanel();
        // Al cargar la página con conexión y pendientes → sincronizar automáticamente
        setTimeout(function () { if (navigator.onLine && leerCola().length) sincronizar(false); }, 600);

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

@push('scripts')
<script>
    // Mejorar reporte con IA (Gemini). Solo reescribe #danos_materiales; no interviene en cola offline, RENIEC ni chips.
    (function () {
        const btn = document.getElementById('btnMejorarIa');
        const area = document.getElementById('danos_materiales');
        const estado = document.getElementById('iaEstado');
        const form = document.getElementById('formCaptura');
        if (!btn || !area || !form) return;

        const spinner = btn.querySelector('.spinner-border');
        const etiqueta = btn.querySelector('.btn-ia-texto');
        const ETIQUETA_ORIGINAL = etiqueta.textContent;
        let ocupado = false;
        let timerEstado = null;

        function mostrar(tipo, msg) {
            const colores = { info: 'text-secondary', warning: 'text-warning-emphasis', danger: 'text-danger', success: 'text-success' };
            estado.className = 'small mb-2 ' + (colores[tipo] || 'text-secondary');
            estado.textContent = msg;
            clearTimeout(timerEstado);
            if (tipo !== 'info') timerEstado = setTimeout(function () { estado.classList.add('d-none'); }, 6000);
        }

        function ocupar(on) {
            ocupado = on;
            btn.disabled = on;
            spinner.classList.toggle('d-none', !on);
            etiqueta.textContent = on ? 'Mejorando...' : ETIQUETA_ORIGINAL;
        }

        btn.addEventListener('click', async function () {
            if (ocupado) return;
            const texto = area.value.trim();

            if (texto.length < 5) {
                mostrar('warning', 'Escriba primero el reporte de daños (mínimo 5 caracteres).');
                area.focus();
                return;
            }
            if (!navigator.onLine) {
                mostrar('warning', 'Requiere conexión a internet.');
                return;
            }

            const tokenInput = form.querySelector('input[name="_token"]');
            ocupar(true);
            mostrar('info', 'Mejorando redacción con IA...');

            try {
                const res = await fetch('/captura/mejorar-reporte', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': tokenInput ? tokenInput.value : ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ texto: texto })
                });
                const data = await res.json().catch(function () { return {}; });

                if (!res.ok || !data.texto_mejorado) {
                    let msg = data.message || 'No se pudo mejorar el reporte. Intente nuevamente.';
                    if (res.status === 419) msg = 'La sesión caducó. Recargue la página para usar la IA.';
                    if (res.status === 422 && data.errors && data.errors.texto) msg = data.errors.texto[0];
                    mostrar('danger', msg);
                    return;
                }

                const max = area.maxLength > 0 ? area.maxLength : 1000;
                area.value = String(data.texto_mejorado).slice(0, max);
                // Dispara el guardado automático del borrador local (listener 'input' del formulario).
                area.dispatchEvent(new Event('input', { bubbles: true }));
                mostrar('success', 'Reporte mejorado. Revíselo antes de enviar.');
            } catch (e) {
                mostrar('danger', 'No se pudo conectar con el servicio de IA. Verifique su conexión.');
            } finally {
                ocupar(false);
            }
        });
    })();
</script>
@endpush
