@extends('layouts.app')

@section('title', 'Acceso al Sistema · Empadronamiento')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h1 class="h5 mb-0 fw-bold">Ingreso de Brigadistas y Coordinadores</h1>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    Autenticación protegida mediante tokens de <strong>Laravel Sanctum</strong> (ADR-004).
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

                <form action="{{ route('login.submit') }}" method="POST" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo institucional</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control form-control-lg @error('email') is-invalid @enderror"
                            placeholder="brigadista@defensacivil.gob.pe"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                            required
                        >
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
