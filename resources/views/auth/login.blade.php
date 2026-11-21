@extends('layouts.app')

@section('title', 'Acceso al Sistema · Empadronamiento')

@push('styles')
<style>
    .login-wrap { min-height: calc(100vh - 260px); display: flex; align-items: center; }
    .login-card { overflow: hidden; }
    .login-hero { background: linear-gradient(135deg, var(--dc-navy-dark), var(--dc-navy) 65%, #24477c); color: #fff; padding: 2rem 1.75rem 1.75rem; position: relative; }
    .login-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: var(--dc-orange); }
    .login-hero h1 { color: #fff; font-size: 1.25rem; font-weight: 800; }
    .login-emblem { width: 56px; height: 56px; border-radius: 16px; background: rgba(255,255,255,.14); display: grid; place-items: center; font-size: 1.75rem; margin-bottom: 1rem; }
    .login-hero p { color: rgba(255,255,255,.75); font-size: .85rem; }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 px-0">
            <div class="card login-card border-0">
                <div class="login-hero">
                    <div class="login-emblem"><i class="bi bi-shield-lock"></i></div>
                    <h1 class="mb-1">Ingreso de Brigadistas y Coordinadores</h1>
                    <p class="mb-0">Sistema de Empadronamiento en Zonas de Emergencia</p>
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

                    <form action="{{ route('login.submit') }}" method="POST" novalidate>
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
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
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
