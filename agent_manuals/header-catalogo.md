# Header de Catálogo — Patrón Estándar

Existen **dos variantes** del encabezado. Antes de aplicar, leer el archivo destino y elegir la correcta según los criterios de detección.

---

## Cómo detectar qué variante usar

Leer el archivo destino y responder estas preguntas:

| Pregunta | Sí → | No → |
|---|---|---|
| ¿El listado necesita filtrarse por algún campo (plaza, cliente, fecha, estado, etc.)? | **Variante A** (con filtros) | **Variante B** (sin filtros) |
| ¿La función `recargarLista()` recibe parámetros además de `idempresa`? | **Variante A** | **Variante B** |
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
<?
$idempresa = $_SESSION["infoUsuario"]["idempresa"];
$items1    = $clase->getCat[Entidad1]($idempresa);
$items2    = $clase->getCat[Entidad2]($idempresa);
// ...más catálogos según el módulo
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

                <!-- BOTÓN EXPORTAR EXCEL (opcional, con permiso) -->
                <? if ($permisos->tienePermiso($_SESSION["infoUsuario"]["idusuario"], "descargar[NombrePermiso]")) { ?>
                    <button
                        class="btn btn-success shadow-sm mr-2"
                        onclick="generarReporte()">
                        <i class="fas fa-file-excel"></i>
                        <span class="d-none d-md-inline">Exportar</span>
                    </button>
                <? } ?>

                <!-- BOTÓN AGREGAR (abre FancyBox ajax) -->
                <!-- NOTA: envolver en tienePermiso() SOLO si el módulo tiene ese permiso registrado.
                     Si no existe el permiso, mostrar el botón directamente sin el if. -->
                <a href="javascript:;"
                   data-fancybox
                   data-options='{
                        "src" : "/modulos/[ruta]/guardar_[entidad].php",
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
        $('.select2').select2();
    });

    // Solo si hay exportación a Excel
    function generarReporte() {
        var [filtro1] = $("#[idfiltro1]").val();
        // ...demás filtros...
        var idempresa = <?= $_SESSION["infoUsuario"]["idempresa"] ?>;
        window.open("/modulos/[ruta]/reporte_excel.php?[filtro1]=" + [filtro1] + "&idempresa=" + idempresa);
    }

    function recargarLista() {
        var [filtro1] = $("#[idfiltro1]").val();
        // ...demás filtros...
        cargarLista(
            "/modulos/[ruta]/lista_[entidad].php",
            "[filtro1]=" + [filtro1] + "&[filtro2]=" + [filtro2],
            "divLista"
        );
    }
</script>
```

### Reglas específicas de la Variante A
- El PHP superior carga solo los catálogos necesarios para los `<select>` del módulo.
- Los `<select>` siempre llevan `style="width: 100%;"` para que Select2 los tome.
- La opción default siempre tiene `value="0"` y texto en mayúsculas ("TODOS", "TODAS").
- El botón buscar ocupa `col-md-1`, solo ícono `fa-search`, alineado al fondo con `d-flex align-items-end`.
- Los anchos `col-md-N` de todos los filtros + `col-md-1` del botón deben sumar 12.
- `recargarLista()` y `generarReporte()` recogen exactamente los mismos filtros.
- `idempresa` en `generarReporte()` se inyecta desde PHP, no desde un campo oculto.
- Select2 se inicializa en `$(document).ready` junto con la primera carga.

---

## Variante B — Sin filtros

### Cuándo usarla
El catálogo es simple: solo se listan todos los registros sin necesidad de filtrar. `recargarLista()` no recoge campos del DOM, solo pasa `idempresa`.

### Estructura

```
[PHP: sin carga de catálogos extra, solo idempresa si hace falta]

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

                <!-- BOTÓN EXPORTAR EXCEL (opcional, con permiso) -->
                <? if ($permisos->tienePermiso($_SESSION["infoUsuario"]["idusuario"], "descargar[NombrePermiso]")) { ?>
                    <button
                        class="btn btn-success shadow-sm mr-2"
                        onclick="generarReporte()">
                        <i class="fas fa-file-excel"></i>
                        <span class="d-none d-md-inline">Exportar</span>
                    </button>
                <? } ?>

                <!-- BOTÓN AGREGAR (abre FancyBox ajax) -->
                <!-- NOTA: envolver en tienePermiso() SOLO si el módulo tiene ese permiso registrado.
                     Si no existe el permiso, mostrar el botón directamente sin el if. -->
                <a href="javascript:;"
                   data-fancybox
                   data-options='{
                        "src" : "/modulos/[ruta]/guardar_[entidad].php",
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
            "/modulos/[ruta]/lista_[entidad].php",
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
- Todos los botones se protegen con `$permisos->tienePermiso(...)`.
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
$idempresa = $_SESSION["infoUsuario"]["idempresa"];
$algo      = $clase->getDatos($idempresa);
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

Archivo ejemplo Variante A (generico): `modulos/moduloEjemplo/modulos/catalogos/catalogoEjemplo.php`
