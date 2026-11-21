@php
    $nivelKey = strtolower((string) ($nivel ?? ''));
    $nivelKey = in_array($nivelKey, ['critico', 'alto', 'medio', 'bajo'], true) ? $nivelKey : 'nd';
    $nivelTexto = [
        'critico' => 'Crítico',
        'alto' => 'Alto',
        'medio' => 'Medio',
        'bajo' => 'Bajo',
        'nd' => 'Sin dato',
    ][$nivelKey];
@endphp
<span class="badge-nivel badge-nivel-{{ $nivelKey }}">{{ $nivelTexto }}</span>
