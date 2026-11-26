# Auditoría Frontend y UX/UI — Sistema de Empadronamiento (Defensa Civil / SINAGERD)

Esta auditoría evalúa la experiencia de uso real del **Brigadista de Campo** (móvil, operación bajo luz solar, señal inestable y alta presión de tiempo) y del **Coordinador de Defensa Civil** (monitoreo de padrón consolidado y priorización logística), aplicando los lineamientos de [`.agent/agents/frontend-ux.md`](../.agent/agents/frontend-ux.md).

---

## Matriz de Priorización (6 Mejoras de Mayor Impacto)

| # | Perfil / Vista | Mejora propuesta | Impacto | Esfuerzo | Prioridad |
|---|---|---|---|---|---|
| 1 | **Brigadista (Móvil)** | Chips táctiles de daños frecuentes + botón de dictado por voz junto a «Mejorar con IA» | Alto | Bajo | **P1 — Inmediata** |
| 2 | **Brigadista (Móvil)** | Fijación automática de la última «Zona de Emergencia» seleccionada entre fichas consecutivas | Alto | Bajo | **P1 — Inmediata** |
| 3 | **Brigadista (Móvil)** | Botones secundarios con altura táctil mínima de 44 px (`#btnGps`, `#btnMejorarIa`, `#btnSincronizar`) | Medio-Alto | Bajo | **P2 — Alta** |
| 4 | **Coordinador (Dashboard)** | Tarjetas KPI clicables como filtros rápidos de prioridad y estado de zona | Alto | Bajo | **P1 — Inmediata** |
| 5 | **Coordinador (Dashboard)** | Indicador visual de geolocalización GPS en la tabla del padrón con apertura rápida de ubicación | Medio-Alto | Bajo | **P2 — Alta** |
| 6 | **Coordinador (Dashboard)** | Columna de daños con resumen limpio de 2 líneas y expansión bajo demanda + acción de exportar vista | Medio | Bajo | **P3 — Media** |

---

## A. Vista Móvil del Brigadista (`/captura`)

### 1. Chips táctiles de daños frecuentes y dictado por voz sobre el reporte de daños
- **Qué mejorar:** Agregar una fila horizontal de chips seleccionables (≥ 44 px de alto) encima de `#danos_materiales` con las afectaciones más habituales en campo (*«Techo de calamina colapsado»*, *«Paredes fisuradas»*, *«Vivienda inundada»*, *«Corte de agua/luz»*, *«Pérdida de enseres»*), acompañada de un botón de micrófono (Web Speech API) junto al botón **«Mejorar con IA»**.
- **Por qué hace más intuitivo el sistema:** En zona de desastre, escribir párrafos largos en teclado virtual bajo el sol es lento y propenso a errores. Tocar 2 o 3 chips o dictar una frase corta y luego pulsar **«Mejorar con IA»** reduce el tiempo de captura por familia de ~60 segundos a menos de 15 segundos.

### 2. Recordar automáticamente la «Zona de Emergencia Afectada» entre registros seguidos
- **Qué mejorar:** Conservar en el teléfono la última `zona_id` utilizada por el brigadista tras guardar una ficha (con un pequeño indicador *«Zona fijada para esta brigada · Cambiar»*), en lugar de reiniciar el selector a *«Seleccione una zona...»* en cada envío.
- **Por qué hace más intuitivo el sistema:** Un brigadista empadrona decenas de viviendas consecutivas dentro del mismo sector o quebrada. Mantener la zona activa elimina un paso repetitivo en cada familia y evita errores de selección por fatiga.

### 3. Ampliar los objetivos táctiles de acciones secundarias a ≥ 44 px en móvil
- **Qué mejorar:** Elevar la altura mínima de los botones `.btn-sm` en la vista de captura (`#btnGps` *«Capturar ubicación GPS»*, `#btnMejorarIa` *«Mejorar con IA»*, `#btnSincronizar` y `.cola-toggle` *«Ver detalle»*) a `min-height: 44px` con `padding: .55rem .95rem` en pantallas `< 768px`.
- **Por qué hace más intuitivo el sistema:** Los botones `.btn-sm` estándar de Bootstrap miden ~31 px de alto, lo que provoca toques fallidos cuando el brigadista opera el teléfono con una sola mano o caminando en terreno irregular. Cumplir el estándar táctil de 44 × 44 px garantiza precisión al primer toque.

---

## B. Dashboard del Coordinador (`/dashboard` y `/reportes`)

### 4. Tarjetas KPI interactivas como filtros directos del padrón
- **Qué mejorar:** Convertir las tarjetas superiores de resumen (*«Registros totales»*, *«Zonas activas»*, *«Zonas riesgo alto / crítico»*) en filtros rápidos clicables que filtren la tabla del padrón al instante (por ejemplo, al pulsar *«Zonas riesgo alto / crítico»*, mostrar únicamente los damnificados de zonas críticas y altas, resaltando la tarjeta activa con borde ámbar `#F39C12`).
- **Por qué hace más intuitivo el sistema:** El coordinador busca responder preguntas operativas urgentes (*«¿quiénes están en zona crítica ahora mismo?»*). Hacer que los propios números de resumen filtren la tabla evita pasos intermedios en menús desplegables y conecta el indicador general con el listado nominal.

### 5. Indicador de coordenadas GPS verificadas en cada fila del padrón
- **Qué mejorar:** Mostrar junto al nombre de la zona en `#tablaPadron` un chip o pin SVG discreto (*«GPS ±12 m»*) cuando el registro cuente con `latitud` y `longitud`, permitiendo abrir la ubicación exacta en el mapa con un clic o toque.
- **Por qué hace más intuitivo el sistema:** Ahora que las brigadas capturan coordenadas GPS satelitales en campo, el coordinador necesita distinguir de un vistazo qué fichas tienen georreferenciación exacta para despachar carpas, maquinaria o brigadas médicas sin ambigüedad.

### 6. Lectura escaneable en la columna «Daños Materiales Reportados»
- **Qué mejorar:** Limitar la celda de daños en `#tablaPadron` a 2 líneas visibles (`line-clamp: 2`) con expansión al hacer clic para leer el informe EDAN completo, y añadir en la cabecera de la tarjeta un botón limpio de impresión/exportación del listado filtrado (`window.print()` con hoja `@media print` institucional).
- **Por qué hace más intuitivo el sistema:** Los reportes técnicos redactados con IA pueden ocupar 3 o 4 líneas; truncarlos visualmente con expansión bajo demanda mantiene todas las filas de la tabla a una altura uniforme, facilita escanear decenas de familias rápidamente y permite entregar actas impresas en el Centro de Operaciones de Emergencia (COER).
