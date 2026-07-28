# Header de Catálogo — Patrón Estándar

Existen **dos variantes** del encabezado. Antes de aplicar, leer el archivo destino y elegir la correcta según los criterios de detección.

> Este sistema es de un solo tenant: no hay `idempresa` ni clase `Permisos`. Las variables reales de sesión son `$_SESSION["infoUsuario"]["idadministrador"]` y `$_SESSION["infoUsuario"]["admin"]`, y el control de acceso a un módulo se hace con el método propio de cada `clase.php`: `tieneAccesoModulo($idadministrador)` (ver `agent_manuals/agents_CRUD.md` sección 2). Los nombres de archivo reales son siempre `agregar.php`/`lista.php`/`procesos.php` dentro de `modulos/<modulo>/` (estructura plana) — no `guardar_[entidad].php`/`lista_[entidad].php`. Ejemplos reales de Variante A: `modulos/productos.php`, `modulos/cortes.php`, `modulos/cajeros.php`. Ejemplo real de Variante B: `modulos/usuarios.php`, `modulos/sucursales.php`.

---

## Cómo detectar qué variante usar

Leer el archivo destino y responder estas preguntas:

| Pregunta | Sí → | No → |
|---|---|---|
| ¿El listado necesita filtrarse por algún campo (plaza, cliente, fecha, estado, etc.)? | **Variante A** (con filtros) | **Variante B** (sin filtros) |
| ¿La función `recargarLista()` recibe algún parámetro (filtro)? | **Variante A** | **Variante B** |
| ¿Hay catálogos que cargar en el PHP superior para poblar `<select>`? | **Variante A** | **Variante B** |

Si todas las respuestas apuntan a lo mismo, usar esa variante. Si hay duda, usar **Variante A**.

---

## Variante A — Con filtros

### Cuándo usarla
El listado tiene parámetros de búsqueda: por cliente, plaza, fecha, estado, tipo, etc. El script necesita recoger valores de `<select>` o inputs antes de llamar a `cargarLista()`.

### Estructura

```
[PHP: carga de catálogos para los <select>]

card.shadow-sm.border-0.mb-4
├── card-body.border-bottom    ← título + botones de acción
└── card-body.bg-light         ← selects de filtro + botón buscar

div#divLista
<script>                       ← recargarLista() con filtros, generarReporte() (opcional), select2 init
```

### PHP superior

```php
<?php
$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$sucursalesUsuario = $clase->getSucursalesUsuario($idadministrador); // whitelist real, nunca el catálogo completo sin filtrar
$mostrarSucursal   = $clase->mostrarSelectorSucursal($idadministrador); // si el filtro es condicional (ver modulos/cajeros)
// ...otros catálogos que necesite el módulo, ya acotados a lo que el usuario puede ver
?>
```

### HTML

```html
<div class="card shadow-sm border-0 mb-4">

    <!-- HEADER -->
    <div class="card-body border-bottom">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

            <div class="mb-3 mb-md-0">
                <h4 class="mb-1 font-weight-bold text-primary">
                    <i class="fas fa-[icono] mr-2"></i>
                    [Nombre del módulo]
                </h4>
            </div>

            <div class="d-flex align-items-center">

                <!-- BOTÓN EXPORTAR EXCEL (opcional) -->
                <!-- No hay todavía un permiso granular real distinto al acceso al módulo
                     (tieneAccesoModulo ya se validó arriba, antes de renderizar este bloque).
                     Si el módulo necesita restringir exportar a un subconjunto de usuarios
                     con acceso al módulo, confirmar el criterio con el equipo antes de inventar uno. -->
                <?php if ($tieneAcceso) { ?>
                    <button
                        class="btn btn-success shadow-sm mr-2"
                        onclick="generarReporte()">
                        <i class="fas fa-file-excel"></i>
                        <span class="d-none d-md-inline">Exportar</span>
                    </button>
                <?php } ?>

                <!-- BOTÓN AGREGAR (abre FancyBox ajax) -->
                <a href="javascript:;"
                   data-fancybox
                   data-options='{
                        "src" : "/modulos/<modulo>/agregar.php",
                        "type" : "ajax",
                        "closeExisting": true,
                        "clickSlide": false,
                        "touch": false
                   }'
                   class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-md-inline">Agregar</span>
                </a>

            </div>

        </div>

    </div>

    <!-- FILTROS -->
    <div class="card-body bg-light">

        <div class="row">

            <!-- FILTRO TIPO SELECT (repetir por cada filtro) -->
            <div class="col-md-[N] mb-3">
                <label class="small text-muted font-weight-bold mb-1">
                    [ETIQUETA]
                </label>
                <select class="form-control select2" style="width: 100%;" id="[idcampo]">
                    <option value="0">TODOS / TODAS</option>
                    <? foreach ($items as $tmp) { ?>
                        <option value="<?= $tmp['[idcampo]'] ?>">
                            <?= $tmp['[campo_texto]'] ?>
                        </option>
                    <? } ?>
                </select>
            </div>

            <!-- BOTÓN BUSCAR — siempre al final, col-md-1, alineado abajo -->
            <div class="col-md-1 mb-3 d-flex align-items-end">
                <button
                    type="button"
                    class="btn btn-primary btn-block"
                    onclick="recargarLista()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

        </div>

    </div>

</div>

<!-- LISTADO -->
<div id="divLista"></div>

<script>
    $(document).ready(function() {
        recargarLista();
    });

    // Solo si hay exportación a Excel
    function generarReporte() {
        var idsucursal = $("#filtroSucursal").val();
        // ...demás filtros...
        window.open("/modulos/<modulo>/reporte_excel.php?idsucursal=" + idsucursal);
    }

    function recargarLista() {
        var idsucursal = $("#filtroSucursal").length ? $("#filtroSucursal").val() : 0;
        // ...demás filtros...
        cargarLista(
            "/modulos/<modulo>/lista.php",
            { idsucursal: idsucursal /* , ...demás filtros */ },
            "divLista"
        );
    }
</script>
```

### Reglas específicas de la Variante A
- El PHP superior carga solo los catálogos necesarios para los `<select>`, ya acotados a lo que el usuario actual puede ver (ej. `getSucursalesUsuario($idadministrador)` — nunca el catálogo completo sin filtrar).
- Los `<select>` siempre llevan `style="width: 100%;"` para que Select2 los tome.
- La opción default siempre tiene `value="0"` y texto en mayúsculas ("TODOS", "TODAS").
- El botón buscar va en su propia columna (`col-md-1` con solo ícono, o `col-md-2` con ícono + texto "Buscar" si hay pocos filtros — ver `modulos/cortes.php`/`modulos/cajeros.php`), alineado al fondo con `d-flex align-items-end`.
- Los anchos `col-md-N` de todos los filtros + el del botón deben sumar 12.
- `recargarLista()` y `generarReporte()` (si existe) recogen exactamente los mismos filtros.
- Si el filtro de sucursal es condicional (solo tiene sentido con 2+ sucursales o admin), calcular esa condición en `clase.php` (`mostrarSelectorSucursal($idadministrador)`, ver `modulos/cajeros/clase.php`) y usarla para decidir si renderizar toda la fila de filtros — no asumir que siempre debe mostrarse.
- Select2 de estos filtros (fuera de cualquier fancy) ya se inicializa solo via el `$(document).ready` global de `home.php` — no hace falta llamarlo aquí tampoco.

---

## Variante B — Sin filtros

### Cuándo usarla
El catálogo es simple: solo se listan todos los registros sin necesidad de filtrar. `recargarLista()` no recoge campos del DOM ni pasa parámetros.

### Estructura

```
[PHP: sin carga de catálogos extra]

card.shadow-sm.border-0.mb-4
└── card-body.border-bottom    ← título + botones de acción (sin sección de filtros)

div#divLista
<script>                       ← recargarLista() sin filtros, sin select2 init
```

### HTML

```html
<div class="card shadow-sm border-0 mb-4">

    <!-- HEADER -->
    <div class="card-body">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

            <div class="mb-3 mb-md-0">
                <h4 class="mb-1 font-weight-bold text-primary">
                    <i class="fas fa-[icono] mr-2"></i>
                    [Nombre del módulo]
                </h4>
            </div>

            <div class="d-flex align-items-center">

                <!-- BOTÓN EXPORTAR EXCEL (opcional) -->
                <!-- No hay todavía un permiso granular real distinto al acceso al módulo
                     (tieneAccesoModulo ya se validó arriba, antes de renderizar este bloque).
                     Si el módulo necesita restringir exportar a un subconjunto de usuarios
                     con acceso al módulo, confirmar el criterio con el equipo antes de inventar uno. -->
                <?php if ($tieneAcceso) { ?>
                    <button
                        class="btn btn-success shadow-sm mr-2"
                        onclick="generarReporte()">
                        <i class="fas fa-file-excel"></i>
                        <span class="d-none d-md-inline">Exportar</span>
                    </button>
                <?php } ?>

                <!-- BOTÓN AGREGAR (abre FancyBox ajax) -->
                <a href="javascript:;"
                   data-fancybox
                   data-options='{
                        "src" : "/modulos/<modulo>/agregar.php",
                        "type" : "ajax",
                        "closeExisting": true,
                        "clickSlide": false,
                        "touch": false
                   }'
                   class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-md-inline">Agregar</span>
                </a>

            </div>

        </div>

    </div>

</div>

<!-- LISTADO -->
<div id="divLista"></div>

<script>
    $(document).ready(function() {
        recargarLista();
    });

    function recargarLista() {
        cargarLista(
            "/modulos/<modulo>/lista.php",
            "",
            "divLista"
        );
    }
</script>
```

### Reglas específicas de la Variante B
- No hay PHP superior con carga de catálogos.
- El `card-body` del header **no** lleva `border-bottom` porque no hay sección de filtros debajo.
- No se incluye el `<div class="card-body bg-light">` de filtros.
- `recargarLista()` pasa `""` como segundo argumento de `cargarLista()` (sin parámetros).
- No se inicializa Select2 porque no hay `<select>` en el header.

---

## Reglas comunes a ambas variantes

- El ícono del título usa FontAwesome 5 con `mr-2`.
- El texto de los botones se oculta en móvil con `d-none d-md-inline`.
- El botón Exportar lleva `mr-2` para separarse del botón Agregar.
- El acceso al módulo completo ya se valida una vez, arriba, con `$clase->tieneAccesoModulo($idadministrador)` — no hay un sistema de permisos granular por botón separado del acceso al módulo (ver nota sobre el botón Exportar).
- El botón Agregar abre FancyBox de tipo `ajax` con `closeExisting: true`, `clickSlide: false`, `touch: false`.
- El contenedor del listado siempre es `<div id="divLista"></div>`.
- `cargarLista(url, params, divId)` es la función global de `/js/funciones.js`.

---

## Reglas de preservación al aplicar el patrón

### PHP superior — conservar siempre

Si el archivo destino tiene un bloque PHP al inicio (variables, includes, consultas), **conservarlo íntegro**. Solo se reemplaza el HTML/JS del encabezado, nunca el PHP.

```php
<?php
// ← este bloque se conserva tal cual, sin modificar
$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$algo            = $clase->getDatos($idadministrador);
?>

<!-- A partir de aquí se aplica el patrón -->
<div class="card shadow-sm border-0 mb-4"> ...
```

### Card contenedora del listado — no tocar

Si el archivo ya tiene una estructura card envolviendo el `divLista` (por ejemplo el patrón antiguo con `card-header` + `card-body`), **dejarla exactamente como está**. Solo agregar o reemplazar el bloque de encabezado/filtros que va _antes_ de esa card.

Señales de que ya existe una card contenedora del listado (no modificar si se encuentra alguna):
- `<div class="card shadow mb-4">` o similar con `card-header` y `card-body` que contiene `id="divLista"`
- El `divLista` está anidado dentro de una card, no suelto en el DOM

En ese caso, el resultado final debe quedar:

```
[PHP superior conservado]

[card nueva del header — aplicar patrón]

[card antigua del listado — conservar sin cambios]

[<script> con recargarLista() — actualizar solo si usa función no estándar]
```

---

## Referencia

Archivos reales, Variante A (con filtros): `modulos/cortes.php` (varios filtros + botón buscar `col-md-2`), `modulos/cajeros.php` (filtro de sucursal condicional vía `mostrarSelectorSucursal()` + botón buscar), `modulos/productos.php` (filtro de sucursal siempre visible si hay 2+).

Archivos reales, Variante B (sin filtros): `modulos/usuarios.php`, `modulos/sucursales.php`.
