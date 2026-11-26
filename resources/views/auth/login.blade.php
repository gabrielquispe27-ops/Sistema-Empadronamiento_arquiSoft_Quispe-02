@extends('layouts.app')

@section('title', 'Acceso al Sistema · Empadronamiento')
@section('body_class', 'login-page')

@push('styles')
<style>
    /* Fondo institucional con patrón geométrico sutil */
    body.login-page {
        background-color: #0A2E50;
        background-image:
            radial-gradient(circle at 15% 10%, rgba(47, 93, 138, .28), transparent 45%),
            radial-gradient(circle at 90% 95%, rgba(243, 156, 18, .16), transparent 40%),
            linear-gradient(45deg, rgba(255,255,255,.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,.035) 75%),
            linear-gradient(45deg, rgba(255,255,255,.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,.035) 75%);
        background-size: auto, auto, 56px 56px, 56px 56px;
        background-position: 0 0, 0 0, 0 0, 28px 28px;
        background-attachment: fixed;
    }
    body.login-page .dc-footer { background: transparent; border-top: 1px solid rgba(255,255,255,.08); }
    body.login-page .dc-footer .container { color: rgba(255,255,255,.65) !important; }

    .login-wrap { min-height: calc(100vh - 260px); display: flex; align-items: center; }
    .login-card { overflow: hidden; border-radius: 20px; box-shadow: 0 24px 60px rgba(0, 0, 0, .45), 0 4px 12px rgba(0, 0, 0, .2); animation: loginRise .6s cubic-bezier(.2, .8, .2, 1) both; }
    @keyframes loginRise { from { opacity: 0; transform: translateY(24px) scale(.98); } to { opacity: 1; transform: none; } }

    .login-hero { background: linear-gradient(135deg, #0A2E50, #12406E); color: #fff; padding: 2rem 1.75rem 1.75rem; position: relative; text-align: center; overflow: hidden; }
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
                    <div class="login-emblem" aria-hidden="true">
                        <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M32 6L54 14V31C54 45.5 44.5 55.2 32 60C19.5 55.2 10 45.5 10 31V14L32 6Z" fill="#0A2E50" stroke="#F39C12" stroke-width="3.5" stroke-linejoin="round"/>
                            <path d="M32 11L49 17.2V31C49 42.6 41.4 50.6 32 54.6C22.6 50.6 15 42.6 15 31V17.2L32 11Z" stroke="#F39C12" stroke-opacity="0.45" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M28 20H36V28H44V36H36V44H28V36H20V28H28V20Z" fill="#FFFFFF"/>
                        </svg>
                    </div>
                    <h1 class="mb-1">Ingreso de Brigadistas y Coordinadores</h1>
                    <p class="mb-0">Sistema de Empadronamiento en Zonas de Emergencia</p>
                    <span class="login-badge">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/>
                        </svg>
                        SINAGERD · Defensa Civil Ayacucho
                    </span>
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
                                <span class="input-group-text" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="3"/>
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                    </svg>
                                </span>
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
                                <span class="input-group-text" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </span>
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

                    <p class="text-muted small text-center mt-4 mb-0 d-flex align-items-center justify-content-center gap-1">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Acceso exclusivo para personal autorizado de Defensa Civil.</span>
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
