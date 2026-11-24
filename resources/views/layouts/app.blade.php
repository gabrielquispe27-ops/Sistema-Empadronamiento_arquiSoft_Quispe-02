<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <!-- PWA-ready: comportamiento tipo app instalable en pantalla de inicio -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Defensa Civil">
    <title>@yield('title', 'Sistema de Empadronamiento en Zonas de Emergencia')</title>

    <!-- Tipografía: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS (Mobile-First - RC01, DA02) + Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --dc-navy: #14284b;
            --dc-navy-dark: #0f172a;
            --dc-navy-mid: #1e3a5f;
            --dc-steel: #2f5d8a;
            --dc-steel-soft: #e8eef5;
            --dc-bg: #f4f6f9;
            --dc-border: #e3e8ef;
            --dc-text: #1f2a3c;
            --dc-muted: #6b778c;
            --dc-orange: #e8730c;
            --dc-green: #1f9254;
            --dc-red: #c93a3a;
            --dc-radius: 14px;
            --dc-shadow: 0 1px 2px rgba(20, 40, 75, .05), 0 4px 14px rgba(20, 40, 75, .06);
            --dc-shadow-hover: 0 2px 4px rgba(20, 40, 75, .06), 0 12px 28px rgba(20, 40, 75, .12);
            --dc-bottom-nav-h: 68px;

            --bs-primary: var(--dc-navy);
            --bs-primary-rgb: 20, 40, 75;
            --bs-body-font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --bs-body-color: var(--dc-text);
            --bs-body-bg: var(--dc-bg);
            --bs-border-color: var(--dc-border);
            --bs-link-color: var(--dc-steel);
        }

        html { -webkit-tap-highlight-color: transparent; }
        body { font-family: var(--bs-body-font-family); background: var(--dc-bg); color: var(--dc-text); -webkit-font-smoothing: antialiased; }
        h1, h2, h3, h4, h5, h6 { letter-spacing: -.01em; color: var(--dc-navy); }
        main { animation: dcFadeIn .35s ease both; }
        @keyframes dcFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

        /* Botones */
        .btn { border-radius: 10px; font-weight: 600; transition: transform .15s ease, box-shadow .2s ease, background-color .2s ease; }
        .btn:active:not(:disabled) { transform: scale(.98); }
        .btn-primary { --bs-btn-bg: var(--dc-navy); --bs-btn-border-color: var(--dc-navy); --bs-btn-hover-bg: var(--dc-navy-dark); --bs-btn-hover-border-color: var(--dc-navy-dark); --bs-btn-active-bg: var(--dc-navy-dark); --bs-btn-active-border-color: var(--dc-navy-dark); --bs-btn-disabled-bg: var(--dc-navy); --bs-btn-disabled-border-color: var(--dc-navy); }
        .btn-outline-primary { --bs-btn-color: var(--dc-navy); --bs-btn-border-color: var(--dc-navy); --bs-btn-hover-bg: var(--dc-navy); --bs-btn-hover-border-color: var(--dc-navy); --bs-btn-active-bg: var(--dc-navy); --bs-btn-active-border-color: var(--dc-navy); }
        .btn-success { --bs-btn-bg: var(--dc-green); --bs-btn-border-color: var(--dc-green); --bs-btn-hover-bg: #187a46; --bs-btn-hover-border-color: #187a46; --bs-btn-active-bg: #187a46; --bs-btn-active-border-color: #187a46; }

        /* Formularios */
        .form-control, .form-select { border-radius: 10px; border-color: #d3dbe6; background-color: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
        .form-control:focus, .form-select:focus { border-color: var(--dc-steel); box-shadow: 0 0 0 .22rem rgba(47, 93, 138, .15); }
        .form-label { font-size: .85rem; color: var(--dc-navy); margin-bottom: .35rem; }
        .input-group-text { background: var(--dc-steel-soft); border-color: #d3dbe6; color: var(--dc-steel); border-radius: 10px; }

        /* Cards */
        .card { border: 1px solid var(--dc-border) !important; border-radius: var(--dc-radius); box-shadow: var(--dc-shadow); background: #fff; }
        .card-header { background: #fff; border-bottom: 1px solid var(--dc-border); border-radius: var(--dc-radius) var(--dc-radius) 0 0 !important; }
        .card-footer { background: #fff; border-top: 1px solid var(--dc-border); border-radius: 0 0 var(--dc-radius) var(--dc-radius) !important; }

        /* Navbar institucional (degradado azul marino) */
        .dc-navbar { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-bottom: 3px solid var(--dc-orange); padding-top: max(.5rem, env(safe-area-inset-top)); }
        .dc-navbar .navbar-brand { display: flex; align-items: center; gap: .65rem; font-weight: 700; line-height: 1.1; }
        .dc-brand-mark { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: rgba(255,255,255,.12); color: #fff; font-size: 1.2rem; box-shadow: inset 0 0 0 1px rgba(255,255,255,.12); }
        .dc-brand-sub { display: block; font-size: .68rem; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; opacity: .7; }
        .dc-navbar .nav-link { color: rgba(255,255,255,.78); font-weight: 500; font-size: .92rem; border-radius: 8px; padding: .5rem .8rem; transition: background-color .2s ease, color .2s ease; }
        .dc-navbar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); }
        .dc-navbar .nav-link.active { color: #fff; background: rgba(255,255,255,.16); box-shadow: inset 0 -2px 0 var(--dc-orange); }

        .dc-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 600; background: rgba(255,255,255,.12); color: #fff; white-space: nowrap; transition: background-color .3s ease; }
        .dc-chip .bi { font-size: .85rem; }
        .dc-dot { width: 8px; height: 8px; border-radius: 50%; background: #3ddc84; animation: dcPulseOn 2s ease-out infinite; }
        .dc-chip.is-offline { background: rgba(255, 176, 32, .18); }
        .dc-chip.is-offline .dc-dot { background: #ffb020; animation: dcPulseOff 1s ease-out infinite; }
        @keyframes dcPulseOn { 0% { box-shadow: 0 0 0 0 rgba(61, 220, 132, .55); } 70%, 100% { box-shadow: 0 0 0 7px rgba(61, 220, 132, 0); } }
        @keyframes dcPulseOff { 0% { box-shadow: 0 0 0 0 rgba(255, 176, 32, .65); } 70%, 100% { box-shadow: 0 0 0 7px rgba(255, 176, 32, 0); } }
        .dc-role-coordinador { background: rgba(232, 115, 12, .22); color: #ffd2a8; }
        .dc-role-brigadista { background: rgba(31, 146, 84, .25); color: #b9f0d2; }

        /* Barra de navegación inferior (solo móvil < 768px) */
        .dc-bottom-nav { position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030; display: flex; background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border-top: 2px solid var(--dc-orange); box-shadow: 0 -6px 20px rgba(15, 23, 42, .25); padding-bottom: env(safe-area-inset-bottom); }
        .dc-bottom-nav .dc-bn-form { flex: 1 1 0; margin: 0; display: flex; }
        .dc-bn-item { flex: 1 1 0; position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; min-height: var(--dc-bottom-nav-h); padding: .4rem .25rem; color: rgba(255,255,255,.65); text-decoration: none; font-size: .7rem; font-weight: 600; letter-spacing: .02em; background: transparent; border: 0; width: 100%; transition: color .2s ease, background-color .2s ease; }
        .dc-bn-item .bi { font-size: 1.6rem; line-height: 1; transition: transform .2s ease; }
        .dc-bn-item:hover, .dc-bn-item:focus-visible { color: #fff; }
        .dc-bn-item:active .bi { transform: scale(.88); }
        .dc-bn-item.active { color: #fff; background: rgba(255,255,255,.08); }
        .dc-bn-item.active::before { content: ''; position: absolute; top: 0; left: 28%; right: 28%; height: 3px; border-radius: 0 0 4px 4px; background: var(--dc-orange); }
        .dc-bn-item.active .bi { transform: translateY(-1px); }
        .dc-bn-salir { color: #ffb4b4; }
        .dc-bn-salir:hover { color: #fff; background: rgba(201, 58, 58, .3); }

        @media (max-width: 767.98px) {
            body.has-bottom-nav { padding-bottom: calc(var(--dc-bottom-nav-h) + env(safe-area-inset-bottom)); }
            .dc-footer { font-size: .75rem; }
        }

        /* Badge de conexión (formulario de captura) */
        .badge-conexion { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; border-radius: 999px; font-weight: 600; font-size: .75rem; background: #e6f6ed; color: var(--dc-green); }
        .badge-conexion.is-offline { background: #fff1e0; color: var(--dc-orange); }

        /* Badges de prioridad / riesgo */
        .badge-nivel { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .badge-nivel::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .badge-nivel-critico { background: #fbe4e4; color: var(--dc-red); }
        .badge-nivel-alto { background: #ffeddb; color: #c25e05; }
        .badge-nivel-medio { background: #fff6d6; color: #8a6a00; }
        .badge-nivel-bajo { background: #e1f4ea; color: var(--dc-green); }
        .badge-nivel-nd { background: #eceff4; color: var(--dc-muted); }

        /* KPI: iconos en círculos de color + hover sutil */
        .kpi-card { position: relative; overflow: hidden; transition: transform .22s ease, box-shadow .22s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: var(--dc-shadow-hover); }
        .kpi-card::after { content: ''; position: absolute; right: -28px; top: -28px; width: 90px; height: 90px; border-radius: 50%; background: currentColor; opacity: .05; transition: transform .35s ease; pointer-events: none; }
        .kpi-card:hover::after { transform: scale(1.5); }
        .kpi-card .kpi-icon { width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; font-size: 1.4rem; flex-shrink: 0; transition: transform .25s ease; }
        .kpi-card:hover .kpi-icon { transform: scale(1.08) rotate(-4deg); }
        .kpi-label { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--dc-muted); }
        .kpi-value { font-size: 1.85rem; font-weight: 800; line-height: 1.1; color: var(--dc-navy); }
        .kpi-navy { color: var(--dc-navy); }
        .kpi-steel { color: var(--dc-steel); }
        .kpi-orange { color: var(--dc-orange); }
        .kpi-green { color: var(--dc-green); }
        .kpi-red { color: var(--dc-red); }
        .kpi-navy .kpi-icon { background: #dbe4f0; color: var(--dc-navy); }
        .kpi-steel .kpi-icon { background: #d9e9f8; color: var(--dc-steel); }
        .kpi-orange .kpi-icon { background: #ffe4cb; color: var(--dc-orange); }
        .kpi-green .kpi-icon { background: #d5f0e1; color: var(--dc-green); }
        .kpi-red .kpi-icon { background: #f9d9d9; color: var(--dc-red); }

        /* Tablas */
        .table-dc { --bs-table-hover-bg: #f6f9fc; }
        .table-dc thead th { background: #f8fafc; color: var(--dc-muted); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; border-bottom: 1px solid var(--dc-border); padding: .85rem 1rem; white-space: nowrap; }
        .table-dc tbody td { padding: .9rem 1rem; border-color: #eef1f6; }
        .page-title { font-weight: 800; font-size: 1.45rem; }
        .page-subtitle { color: var(--dc-muted); font-size: .9rem; }

        /* Buscador rápido de tablas */
        .dc-search { position: relative; min-width: 240px; }
        .dc-search > .bi-search { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--dc-muted); pointer-events: none; }
        .dc-search .form-control { padding-left: 2.4rem; padding-right: 2.4rem; }
        .dc-search-clear { position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--dc-muted); width: 32px; height: 32px; border-radius: 50%; }
        .dc-search-clear:hover { background: var(--dc-steel-soft); color: var(--dc-navy); }

        .alert { border: 0; border-radius: 12px; font-size: .9rem; }
        .alert-success { background: #e1f4ea; color: #146c3d; border-left: 4px solid var(--dc-green); }
        .alert-danger { background: #fbe4e4; color: #9b2a2a; border-left: 4px solid var(--dc-red); }

        .dc-footer { background: #fff; border-top: 1px solid var(--dc-border); }
        .pagination { --bs-pagination-active-bg: var(--dc-navy); --bs-pagination-active-border-color: var(--dc-navy); --bs-pagination-color: var(--dc-navy); margin-bottom: 0; }

        @media (max-width: 575.98px) {
            .page-title { font-size: 1.25rem; }
            main.container { padding-left: 1rem; padding-right: 1rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 @auth has-bottom-nav @endauth @yield('body_class')">
    @php
        $usuarioActual = auth()->user();
        $rolActual = $usuarioActual?->role;
        $etiquetaRol = $rolActual === 'coordinador' ? 'Coordinador Defensa Civil' : ($rolActual === 'brigadista' ? 'Brigadista' : ucfirst((string) $rolActual));
    @endphp

    <!-- Barra de navegación principal (Componente VISTA) -->
    <nav class="navbar navbar-expand-md navbar-dark dc-navbar shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('captura.create') }}">
                <span class="dc-brand-mark"><i class="bi bi-shield-check"></i></span>
                <span>
                    Defensa Civil
                    <span class="dc-brand-sub d-none d-sm-block">Empadronamiento · Emergencias</span>
                </span>
            </a>

            <!-- Móvil (<768px): sin menú hamburguesa; solo chip de conexión. La navegación va en la barra inferior -->
            <span class="dc-chip dc-chip-conexion ms-auto d-md-none" title="Estado de conexión">
                <span class="dc-dot"></span><span class="nav-conexion-texto">En línea</span>
            </span>

            <!-- Escritorio (>=768px): menú horizontal limpio -->
            <div class="d-none d-md-flex align-items-center flex-grow-1" id="menuPrincipal">
                @auth
                    <ul class="navbar-nav flex-row me-auto ms-md-3 gap-1">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('captura.*') ? 'active' : '' }}" href="{{ route('captura.create') }}">
                                <i class="bi bi-pencil-square me-1"></i>Captura en Campo
                            </a>
                        </li>
                        @if ($rolActual === 'coordinador')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                                    <i class="bi bi-people me-1"></i>Padrón Consolidado
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                                    <i class="bi bi-bar-chart-line me-1"></i>Reportes por Zona
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <span id="navConexion" class="dc-chip dc-chip-conexion" title="Estado de conexión">
                            <span class="dc-dot"></span><span class="nav-conexion-texto">En línea</span>
                        </span>
                        <span class="dc-chip dc-role-{{ $rolActual }} d-none d-lg-inline-flex" title="Rol del usuario">
                            <i class="bi {{ $rolActual === 'coordinador' ? 'bi-person-badge' : 'bi-person-workspace' }}"></i>
                            <span>{{ $usuarioActual->name }} · {{ $etiquetaRol }}</span>
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="d-flex">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
                            </button>
                        </form>
                    </div>
                @else
                    <div class="ms-auto d-flex align-items-center">
                        <span id="navConexion" class="dc-chip dc-chip-conexion">
                            <span class="dc-dot"></span><span class="nav-conexion-texto">En línea</span>
                        </span>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Contenido principal renderizado por cada vista Blade -->
    <main class="container my-4 my-lg-5 flex-grow-1">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>{{ session('status') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="dc-footer py-3 mt-auto">
        <div class="container text-center text-muted small">
            <i class="bi bi-shield-check me-1"></i>Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia · Patrón MVC en Capas
        </div>
    </footer>

    @auth
        <!-- Bottom Navigation Bar (solo móvil, tipo app nativa) -->
        <nav class="dc-bottom-nav d-md-none" aria-label="Navegación principal móvil">
            <a class="dc-bn-item {{ request()->routeIs('captura.*') ? 'active' : '' }}" href="{{ route('captura.create') }}">
                <i class="bi {{ request()->routeIs('captura.*') ? 'bi-pencil-square' : 'bi-pencil' }}"></i>
                <span>Captura</span>
            </a>
            @if ($rolActual === 'coordinador')
                <a class="dc-bn-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                    <i class="bi {{ request()->routeIs('dashboard.*') ? 'bi-people-fill' : 'bi-people' }}"></i>
                    <span>Padrón</span>
                </a>
                <a class="dc-bn-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                    <i class="bi {{ request()->routeIs('reportes.*') ? 'bi-bar-chart-line-fill' : 'bi-bar-chart-line' }}"></i>
                    <span>Reportes</span>
                </a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="dc-bn-form">
                @csrf
                <button type="submit" class="dc-bn-item dc-bn-salir" aria-label="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Salir</span>
                </button>
            </form>
        </nav>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Indicador de estado de conexión (chips del navbar y #badgeConexion cuando existe)
        (function () {
            function actualizarConexion() {
                const enLinea = navigator.onLine;
                document.querySelectorAll('.dc-chip-conexion').forEach(function (chip) {
                    chip.classList.toggle('is-offline', !enLinea);
                    const t = chip.querySelector('.nav-conexion-texto');
                    if (t) t.textContent = enLinea ? 'En línea' : 'Sin conexión';
                });
                const badge = document.getElementById('badgeConexion');
                if (badge) {
                    badge.classList.toggle('is-offline', !enLinea);
                    badge.innerHTML = enLinea
                        ? '<i class="bi bi-wifi"></i> En línea'
                        : '<i class="bi bi-wifi-off"></i> Sin conexión · borrador local';
                }
            }
            window.addEventListener('online', actualizarConexion);
            window.addEventListener('offline', actualizarConexion);
            actualizarConexion();
        })();
    </script>
    @stack('scripts')
</body>
</html>
