@extends('layouts.app')

@section('title', 'Acceso al Sistema · Empadronamiento')
@section('body_class', 'login-page')

@push('styles')
<style>
    /* Fondo institucional con patrón geométrico sutil (tablero de rombos + resplandores) */
    body.login-page {
        background-color: #0f172a;
        background-image:
            radial-gradient(circle at 15% 10%, rgba(59, 130, 246, .20), transparent 45%),
            radial-gradient(circle at 90% 95%, rgba(232, 115, 12, .14), transparent 40%),
            linear-gradient(45deg, rgba(255,255,255,.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,.035) 75%),
            linear-gradient(45deg, rgba(255,255,255,.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,.035) 75%);
        background-size: auto, auto, 56px 56px, 56px 56px;
        background-position: 0 0, 0 0, 0 0, 28px 28px;
        background-attachment: fixed;
    }
    body.login-page .dc-footer { background: transparent; border-top: 1px solid rgba(255,255,255,.08); }
    body.login-page .dc-footer .container { color: rgba(255,255,255,.55) !important; }

    .login-wrap { min-height: calc(100vh - 260px); display: flex; align-items: center; }
    .login-card { overflow: hidden; border-radius: 20px; box-shadow: 0 24px 60px rgba(0, 0, 0, .45), 0 4px 12px rgba(0, 0, 0, .2); animation: loginRise .6s cubic-bezier(.2, .8, .2, 1) both; }
    @keyframes loginRise { from { opacity: 0; transform: translateY(24px) scale(.98); } to { opacity: 1; transform: none; } }

    .login-hero { background: linear-gradient(135deg, #0f172a, #1e3a5f); color: #fff; padding: 2rem 1.75rem 1.75rem; position: relative; text-align: center; overflow: hidden; }
    .login-hero::before { content: ''; position: absolute; inset: 0; background-image: linear-gradient(45deg, rgba(255,255,255,.05) 25%, transparent 25%, transparent 75%, rgba(255,255,255,.05) 75%); background-size: 28px 28px; opacity: .6; }
    .login-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: var(--dc-orange); }
    .login-hero > * { position: relative; z-index: 1; }
    .login-hero h1 { color: #fff; font-size: 1.2rem; font-weight: 800; }
    .login-hero p { color: rgba(255,255,255,.75); font-size: .85rem; }
    .login-emblem { position: relative; width: 72px; height: 72px; margin: 0 auto 1rem; border-radius: 50%; background: rgba(255,255,255,.12); display: grid; place-items: center; font-size: 2.1rem; box-shadow: inset 0 0 0 1px rgba(255,255,255,.2); }
    .login-emblem::after { content: ''; position: absolute; inset: -6px; border-radius: 50%; border: 2px solid rgba(232, 115, 12, .65); animation: emblemPulse 2.4s ease-out infinite; }
    @keyframes emblemPulse { 0% { transform: scale(.92); opacity: .9; } 100% { transform: scale(1.3); opacity: 0; } }
    .login-badge { display: inline-flex; align-items: center; gap: .35rem; margin-top: .75rem; padding: .25rem .65rem; border-radius: 999px; background: rgba(232, 115, 12, .2); color: #ffd2a8; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }

    .login-card .input-group { transition: transform .2s ease, box-shadow .2s ease; border-radius: 10px; }
    .login-card .input-group:focus-within { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(30, 58, 95, .12); }
    .login-card .input-group:focus-within .input-group-text { color: var(--dc-navy); background: #dbe7f5; }
    .btn-toggle-pass { border: 1px solid #d3dbe6; border-left: 0; background: #fff; color: var(--dc-muted); border-radius: 0 10px 10px 0 !important; padding: 0 1rem; transition: color .2s ease, background-color .2s ease; }
    .btn-toggle-pass:hover { color: var(--dc-navy); background: var(--dc-steel-soft); }
    .btn-toggle-pass:focus-visible { outline: 0; box-shadow: 0 0 0 .22rem rgba(47, 93, 138, .2); }
    .btn-toggle-pass .bi { display: inline-block; transition: transform .25s ease; }
    .btn-toggle-pass.is-visible .bi { transform: scale(1.12); color: var(--dc-steel); }
    .input-group > .form-control:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; }

    .btn-login { position: relative; min-height: 54px; box-shadow: 0 8px 18px rgba(20, 40, 75, .3); }
    .btn-login:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(20, 40, 75, .38); }
    .btn-login .bi-box-arrow-in-right { transition: transform .25s ease; }
    .btn-login:hover .bi-box-arrow-in-right { transform: translateX(4px); }
    .btn-login .spinner-border { display: none; }
    .btn-login.is-loading .spinner-border { display: inline-block; }
    .btn-login.is-loading .bi-box-arrow-in-right { display: none; }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 px-0">
            <div class="card login-card border-0">
                <div class="login-hero">
                    <div class="login-emblem"><i class="bi bi-shield-fill-check"></i></div>
                    <h1 class="mb-1">Ingreso de Brigadistas y Coordinadores</h1>
                    <p class="mb-0">Sistema de Empadronamiento en Zonas de Emergencia</p>
                    <span class="login-badge"><i class="bi bi-exclamation-triangle-fill"></i> Defensa Civil · Ayacucho</span>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger d-flex">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="formLogin" action="{{ route('login.submit') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Correo institucional</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="brigadista@defensacivil.gob.pe"
                                    autocomplete="username"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="current-password"
                                    required
                                >
                                <button type="button" id="togglePassword" class="btn btn-toggle-pass" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar / ocultar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="btnLogin" class="btn btn-primary btn-lg btn-login">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <i class="bi bi-box-arrow-in-right me-2"></i><span class="btn-login-texto">Iniciar Sesión</span>
                            </button>
                        </div>
                    </form>

                    <p class="text-muted small text-center mt-4 mb-0">
                        <i class="bi bi-lock-fill me-1"></i>Autenticación protegida mediante tokens de <strong>Laravel Sanctum</strong> (ADR-004).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        // Mostrar / ocultar contraseña (solo presentación)
        const pass = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        if (pass && toggle) {
            toggle.addEventListener('click', function () {
                const visible = pass.type === 'password';
                pass.type = visible ? 'text' : 'password';
                toggle.classList.toggle('is-visible', visible);
                toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
                toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.innerHTML = '<i class="bi ' + (visible ? 'bi-eye-slash' : 'bi-eye') + '"></i>';
                pass.focus();
            });
        }

        // Efecto de carga al enviar
        const form = document.getElementById('formLogin');
        const btn = document.getElementById('btnLogin');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.classList.add('is-loading');
                btn.disabled = true;
                const t = btn.querySelector('.btn-login-texto');
                if (t) t.textContent = 'Verificando...';
            });
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    const t = btn.querySelector('.btn-login-texto');
                    if (t) t.textContent = 'Iniciar Sesión';
                }
            });
        }
    })();
</script>
@endpush
