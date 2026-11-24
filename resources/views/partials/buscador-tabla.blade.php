{{--
    Buscador rápido de tabla (100% cliente, sin recargar la página).
    Parámetros:
      - tabla:       id de la <table> a filtrar
      - cols:        índices de columnas (base 0) donde buscar
      - placeholder: texto de ayuda del campo
--}}
@php
    $buscadorId = 'buscador_' . $tabla;
@endphp
<div class="d-flex align-items-center gap-3 flex-wrap">
    <div class="dc-search flex-grow-1">
        <i class="bi bi-search"></i>
        <input
            type="search"
            id="{{ $buscadorId }}"
            class="form-control"
            placeholder="{{ $placeholder ?? 'Buscar...' }}"
            data-buscador-tabla="{{ $tabla }}"
            data-cols="{{ implode(',', $cols ?? []) }}"
            autocomplete="off"
            aria-label="{{ $placeholder ?? 'Buscar' }}"
        >
        <button type="button" class="dc-search-clear" hidden aria-label="Limpiar búsqueda"><i class="bi bi-x-lg"></i></button>
    </div>
    <span id="{{ $buscadorId }}_contador" class="small text-muted text-nowrap" aria-live="polite"></span>
</div>

@once
    @push('scripts')
    <script>
        (function () {
            function normalizar(s) {
                return (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            }

            document.querySelectorAll('input[data-buscador-tabla]').forEach(function (input) {
                const tabla = document.getElementById(input.getAttribute('data-buscador-tabla'));
                if (!tabla || !tabla.tBodies[0]) return;

                const cuerpo = tabla.tBodies[0];
                const cols = (input.getAttribute('data-cols') || '').split(',').filter(Boolean).map(Number);
                const contador = document.getElementById(input.id + '_contador');
                const limpiar = input.parentElement.querySelector('.dc-search-clear');
                const colCount = tabla.tHead ? tabla.tHead.rows[0].cells.length : 1;

                // Filas de datos (excluye el estado vacío del servidor, que usa colspan)
                const filas = Array.prototype.filter.call(cuerpo.rows, function (tr) {
                    return !tr.querySelector('td[colspan]');
                });

                // Fila "sin coincidencias" creada solo en cliente
                const sinResultados = document.createElement('tr');
                sinResultados.hidden = true;
                sinResultados.innerHTML = '<td colspan="' + colCount + '" class="text-center text-muted py-5">' +
                    '<i class="bi bi-search fs-2 d-block mb-2"></i>No hay coincidencias para su búsqueda.</td>';
                cuerpo.appendChild(sinResultados);

                const indices = filas.map(function (tr) {
                    const texto = cols.map(function (i) {
                        return tr.cells[i] ? tr.cells[i].textContent : '';
                    }).join(' ');
                    return normalizar(texto);
                });

                function filtrar() {
                    const q = normalizar(input.value);
                    let visibles = 0;
                    filas.forEach(function (tr, i) {
                        const coincide = q === '' || indices[i].indexOf(q) !== -1;
                        tr.hidden = !coincide;
                        if (coincide) visibles++;
                    });
                    sinResultados.hidden = !(filas.length > 0 && visibles === 0);
                    if (limpiar) limpiar.hidden = q === '';
                    if (contador) {
                        contador.textContent = filas.length ? (visibles + ' de ' + filas.length + ' registros') : '';
                    }
                }

                input.addEventListener('input', filtrar);
                if (limpiar) {
                    limpiar.addEventListener('click', function () {
                        input.value = '';
                        filtrar();
                        input.focus();
                    });
                }
                filtrar();
            });
        })();
    </script>
    @endpush
@endonce
