# Guia General para Crear Modulos del Sistema

Este documento es una referencia operativa para agentes de IA que necesiten crear un modulo nuevo en este sistema con el menor numero de dudas posible.

Objetivo:
- Explicar arquitectura, convenciones visuales y flujo tecnico **real** del proyecto (verificado contra `modulos/productos/`, `modulos/sucursales/`, `modulos/usuarios/`, `modulos/cortes/` y `modulos/cajeros/`).
- Estandarizar la forma de crear pantallas, listas AJAX, fancys (Fancybox), formularios, procesos y clases.
- Reducir retrabajo por errores de rutas, formato de respuesta JSON, validaciones de frontend, fechas y control de acceso por sucursal.

Este manual complementa `agent_manuals/agents_CRUD.md` (arquitectura de datos/negocio y manejo de errores) y `agent_manuals/agents_FANCYS.md` (estilo visual de modales). Para el encabezado con/sin filtros, ver `agent_manuals/header-catalogo.md`.

> **Este proyecto es de un solo tenant.** No hay `idempresa`, no hay clase `Permisos`, no hay clase `Modulos`, no hay breadcrumbs (`generarBreadCrumb()` de `BaseClass` no se usa). Si un documento o codigo viejo los menciona, son vestigios de una plantilla heredada — no los repliques.

---

## 1) Mapa mental rapido del sistema

1. **Router + vista del modulo**
   - Archivo: `modulos/<modulo>.php`.
   - Se incluye automaticamente desde `home.php` cuando `$_GET["modulo1"]` coincide con el primer segmento de `topciones.url`. No hay `switch`/`case` que editar a mano.
   - Pinta header, filtros (si aplica) y contenedor de lista. Expone `recargarLista()`.

2. **Carpeta del modulo**
   - Ruta: `modulos/<modulo>/` (plana, sin `modulos/<modulo>/modulos/<submodulo>/`).
   - Archivos base: `clase.php`, `lista.php`, `agregar.php`, `procesos.php`.

3. **Helpers globales JS** (`js/funciones.js`):
   - `cargarLista(url, variable, div)`
   - `guardar(form, modulo, closefancybox, callback)`
   - `eliminar(proceso, modulo, id, callback, anuncio)`
   - `validateForm(form)`
   - `inicializarFancyX(selector)` — ajusta el contenedor Fancybox e inicializa todos los `.select2` internos con `dropdownParent` scoped. Ver seccion 7.

4. **Backend de negocio**
   - `clase.php` extiende `BaseClass` (`controlador/clases/baseClass.php`).
   - Control de acceso y aislamiento por sucursal: ver `agent_manuals/agents_CRUD.md` secciones 2 y 3.

---

## 2) Flujo tecnico extremo a extremo

Alta y listado:

1. Usuario navega a `home.php?modulo1=<modulo>`.
2. `home.php` incluye `modulos/<modulo>.php` (ya con `session.php`/`conn.php` cargados).
3. El router llama `recargarLista()`.
4. `recargarLista()` llama `cargarLista('/modulos/<modulo>/lista.php', parametros, 'divLista')`.
5. `lista.php` (bootstrap propio completo) consulta via `clase.php`, acotado a `getSucursalesUsuario()`, y devuelve HTML de tabla + init de DataTable.
6. Usuario abre el fancy de alta con `data-fancybox` hacia `agregar.php`.
7. `agregar.php` (bootstrap propio) renderiza el formulario; el boton invoca `guardar('frmX', '<modulo>')`.
8. `guardar()` hace POST a `/modulos/<modulo>/procesos.php`.
9. `procesos.php` hace `switch($_POST['proceso'])` dentro de un `try/catch` (obligatorio — ver `agents_CRUD.md` seccion 5) y llama el metodo de `clase.php`.
10. Respuesta JSON `success` dispara SweetAlert, cierra el fancy y recarga la lista.

Eliminar:

1. Boton en `lista.php` llama `eliminar('eliminarX', '<modulo>', id)`.
2. `eliminar()` muestra confirmacion (SweetAlert2).
3. Si se confirma, POST a `procesos.php` con `proceso` + `id`.
4. Si responde `success`, recarga lista y muestra mensaje.

---

## 3) Estructura minima de archivos

Para un modulo nuevo `entidad`:

```
modulos/entidad.php
modulos/entidad/clase.php
modulos/entidad/lista.php
modulos/entidad/agregar.php
modulos/entidad/procesos.php
```

Y un script de migracion para darlo de alta en el menu (ver seccion 9).

---

## 4) Includes por tipo de archivo (regla real, no obvia)

`modulos/<modulo>.php` **no** incluye `session.php`/`conn.php` — ya llegan incluidos desde `home.php` antes de que este archivo se ejecute. Si usa `formatearLabel()` u otro helper de `includes/generales.php`, debe incluirlo explicitamente (no se hereda):

```php
<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/<modulo>/clase.php");
```

`lista.php`, `agregar.php` y `procesos.php` en cambio se golpean via AJAX **directamente** (no pasan por `home.php`), asi que cada uno debe bootstrapear todo desde cero:

```php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php"); // si usan formatearLabel/fecha_display
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/<modulo>/clase.php");
```

Un error comun (ya reproducido en produccion) es agregar una llamada a `formatearLabel()` en `modulos/<modulo>.php` sin este include — produce `Uncaught Error: Call to undefined function formatearLabel()`, capturado como fatal por `BaseClass::handleFatalError()`, que rompe el render de la pagina completa (no solo del modulo) mostrando un JSON crudo incrustado en el HTML.

---

## 5) Entrypoint (`<modulo>.php`): header con o sin filtros

Ver `agent_manuals/header-catalogo.md` para el patron completo y como decidir entre variante con filtros y sin filtros. Resumen:

- **Sin filtros** (ej. `usuarios`, `sucursales`): una sola `card-body` con titulo + boton Agregar.
- **Con filtros** (ej. `productos`, `cortes`, `cajeros` con 2+ sucursales): `card-body.border-bottom` (titulo + acciones) seguido de `card-body.bg-light` (selects de filtro + boton "Buscar"), dentro del mismo `card`.

Si el filtro depende de cuantas sucursales puede ver el usuario actual (patron `cajeros`), calcula esa condicion en `clase.php` (`mostrarSelectorSucursal($idadministrador)`, ver `agents_CRUD.md` 6.2) y usala para decidir si renderizar la fila de filtros — no lo calcules de forma duplicada en cada vista.

```php
$(document).ready(function () {
    recargarLista();
});

function recargarLista() {
    var idsucursal = $("#filtroSucursal").length ? $("#filtroSucursal").val() : 0;
    cargarLista("/modulos/<modulo>/lista.php", { idsucursal: idsucursal }, "divLista");
}
```

---

## 6) Reglas para `lista.php`

- Bootstrap completo (seccion 4) + `clase.php`.
- Validar `tieneAccesoModulo()` al inicio; si falla, `echo` de un alert y `exit` (no `json_encode`, esto no es un endpoint JSON).
- Leer filtros desde `$_POST` (llegan via el objeto que pasa `cargarLista()`).
- Acotar toda consulta a `getSucursalesUsuario($idadministrador)` cuando el modulo se relacione con sucursales.
- Envolver tabla o mensaje "sin registros" en su propia card — este `div` se reemplaza completo en cada `cargarLista()`/`recargarLista()`, asi que la card debe vivir aqui, no en el entrypoint:
  ```html
  <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
          <!-- alert "no hay registros" O tabla dentro de .table-responsive -->
      </div>
  </div>
  ```
- Tabla: `table table-hover mb-0 nowrap dataTable no-footer table-sm small`, envuelta en `div.table-responsive`, columna de acciones al final con `columnDefs: [{ orderable: false, targets: -1 }]`.
- Fechas: formatear en servidor con `fecha_display($fecha)` (de `includes/generales.php`), nunca crudas de BD.
- Si no hay registros: `<div class="alert alert-warning m-2 text-center">...</div>` dentro del mismo `card-body`.
- Inicializar DataTable solo si la tabla existe: `if ($("#tablaX").length) { ... }`.
- Bloque `language` en espanol estandar (copiar de cualquier `lista.php` real) + `$("[data-toggle='tooltip']").tooltip();`.

---

## 7) Reglas para fancys (`agregar.php`)

Ver `agent_manuals/agents_FANCYS.md` para el estilo visual completo (`fancy-x-root`, `fancy-x-header`, `fancy-x-body`, ancho responsivo). Resumen tecnico:

1. Bootstrap completo (seccion 4) + `clase.php`. Determinar modo por `$_GET["id"]`.
2. Cargar catalogos necesarios para `<select>` **ya acotados** (ej. `getSucursalesUsuario($idadministrador)`, nunca el catalogo completo sin filtrar).
3. Formulario con `<input type="hidden" name="proceso" value="agregarX|editarX">` y, si edita, `<input type="hidden" name="id" ...>`.
4. Boton de accion principal con `id="btnAccion"` — `guardar()` lo manipula durante el submit (deshabilita, cambia texto).
5. `<select class="form-control select2">` — **no** inicializar Select2 manualmente. `inicializarFancyX("#fancyX")` ya lo hace, scoped, para todos los `.select2` del fancy:
   ```js
   $(function () {
       inicializarFancyX("#fancyCajero");
   });
   ```
   Ver `agents_CRUD.md` seccion 10 para el porque (bug real que rompe filtros del padre si se inicializa mal).
6. Botones alineados a la derecha: negativo (`Cancelar`, `data-fancybox-close`) a la izquierda del grupo, positivo (`Guardar`) a la derecha.
7. Guardado: `onclick="guardar('frmX', '<modulo>')"`, con `validateForm` aplicado automaticamente dentro de `guardar()` via la clase `.requerido`.
8. Campos `type="password"` que el admin usa para fijar la credencial de **otro** usuario/registro: `autocomplete="off"` (nunca `"new-password"`). Ver `agents_CRUD.md` seccion 8.1.

---

## 8) Validaciones frontend obligatorias

`validateForm(form)` en `js/funciones.js`:

1. Campos con clase `.requerido` (inputs texto/password/email/file por valor, textarea por no-vacio).
2. `select.requerido` con valor `"0"` se considera **invalido**. Si un booleano de negocio usa `0` como valor legitimo, no marques ese `select` como `requerido`; valida `in_array($valor, [0,1])` en backend.
3. Muestra SweetAlert warning automaticamente si falla — no dupliques ese aviso a mano.
4. Montos: `step="0.01"` en el input + backend normaliza a 2 decimales siempre (nunca confiar en el string que llega del cliente).

---

## 9) `procesos.php` y `clase.php`

Ambos se documentan en profundidad en `agent_manuals/agents_CRUD.md`:

- Estructura y bootstrap de `procesos.php`: seccion 4.
- **Manejo de errores de negocio (`try/catch` obligatorio) — la parte mas propensa a fallar si se omite**: seccion 5.
- Constructor estandar, `tieneAccesoModulo()`, `getSucursalesUsuario()`, metodos CRUD: secciones 2, 3 y 6.

No los repitas de memoria distinto a como estan ahi — son la fuente de verdad tecnica.

---

## 10) Reglas visuales del sistema

1. **Header de modulo**: `h4` con icono FontAwesome + `mr-2`, dentro de `card-body` (no hay `hr` separador suelto; el separador es `border-bottom` de la propia card cuando hay fila de filtros debajo).
2. **Card de listado**: sin `card-header` con texto "Listado de..." — los modulos reales solo usan `card-body` (ver `lista.php` de cualquier modulo real).
3. **Tabla estandar**: `table table-hover mb-0 nowrap dataTable no-footer table-sm small`; columna de acciones al final; botones `btn btn-sm` con iconos FA.
4. **Botones**: agregar/editar `btn-primary`, eliminar `btn-danger`. No hay convencion viva de `btn-info` para "ver/detalle" (no se ha implementado ese caso en los modulos actuales).
5. **Tooltips**: `$("[data-toggle='tooltip']").tooltip();` despues de pintar la tabla.

---

## 11) Convenciones de fechas y horas

1. **BD**: `DATE`/`DATETIME` en formato `Y-m-d` / `Y-m-d H:i:s`.
2. **Inputs de captura**: `<input type="date">` nativo del navegador. No se usa jQuery UI Datepicker en ningun modulo real — no lo agregues salvo que el modulo lo requiera explicitamente por otra razon.
3. **Listados (visual)**: `fecha_display($fecha)` de `includes/generales.php` — formatea a `d/m/Y` (o `d/m/Y H:i:s`), y normaliza `0000-00-00` a `-`.
4. **Filtros por rango**: validar `fechadesde <= fechahasta` si el modulo lo requiere; ver `modulos/cortes/clase.php::getCortes()` como referencia de filtro de rango sobre una columna de fecha.

---

## 12) Select2

Ver `agent_manuals/agents_CRUD.md` seccion 10. Regla corta: **nunca** escribas tu propio `.select2({...})` a mano dentro de un fancy — usa la clase `select2` + `inicializarFancyX(selector)`. Fuera de fancys (filtros de encabezado), el `$(document).ready` global de `home.php` ya inicializa `.select2` — tampoco hace falta llamarlo ahi.

---

## 13) Seguridad y aislamiento por sucursal

Ver `agent_manuals/agents_CRUD.md` seccion 3. Regla corta: todo dato relacionado con sucursal se filtra en el servidor contra `getSucursalesUsuario($idadministrador)` — nunca contra lo que manda el cliente, aunque el frontend ya lo tenga filtrado visualmente.

---

## 14) Integracion con menu (`topciones`)

- Que existan los 4 archivos no hace que el modulo aparezca en el sidebar. El sidebar de `home.php` lee `topciones` (catalogo de opciones) y, para administradores no-admin, lo cruza con `tradministradoropciones`.
- Alta de un modulo nuevo: `INSERT INTO topciones (nombre, url, icono) VALUES (...)` (ver seccion 9 y `agents_CRUD.md` seccion 1).
- Si el modulo no aparece en el menu para un administrador limitado, revisar que exista su fila en `tradministradoropciones` — no hay UI todavia para gestionar esto, se inserta manualmente via SQL.

---

## 15) Migraciones SQL (convencion real)

- Archivos en `scripts/`, numeracion secuencial (`001_...`, `002_...`, etc. — revisar el ultimo numero usado en `scripts/` antes de crear uno nuevo).
- Un script puede ser un cambio de esquema (ej. `001_productos_multisucursal.sql`) o solo el registro de menu de un modulo nuevo (ej. `004_cajeros_menu.sql`, patron minimo: `INSERT INTO topciones ...` + comentario indicando que falta agregar filas en `tradministradoropciones` si aplica).
- Se ejecutan manualmente por ambiente (dev/staging/produccion) — no hay corredor de migraciones automatico.

---

## 16) Checklist de implementacion de un modulo nuevo

1. Definir alcance funcional y si se relaciona con sucursales.
2. Crear `modulos/<modulo>.php` + carpeta `modulos/<modulo>/` con los 4 archivos.
3. `clase.php`: constructor estandar, `tieneAccesoModulo()`, `getSucursalesUsuario()` si aplica, metodos CRUD.
4. `lista.php` con DataTable y acciones, acotado a sucursales permitidas.
5. `agregar.php` en fancy con formulario, `select2` scoped via `inicializarFancyX`, `guardar()`.
6. `procesos.php` con `switch` + `try/catch` obligatorio (`agents_CRUD.md` seccion 5).
7. Crear `scripts/00N_<modulo>_menu.sql` para registrar el modulo en `topciones`.
8. Verificar JSON (`result/titulo/mensaje/texto/icono`).
9. Probar alta/edicion/eliminacion, duplicados (incluyendo forzar el error para confirmar que sale SweetAlert y no un error generico), filtros, y los tres escenarios de sucursal: admin, no-admin con 1 sucursal, no-admin con 2+ sucursales.
10. Revisar balance de llaves/parentesis en todos los archivos nuevos.

---

## 17) Checklist de QA funcional

1. **Navegacion**: el modulo abre sin errores PHP/JS visibles en el HTML.
2. **Lista**: carga via AJAX, DataTable funciona, empty state correcto.
3. **Fancy alta**: abre, Select2 se ve y selecciona bien (scoped, no rompe filtros del padre al cerrar), validaciones frontend funcionan, guarda registro real, cierra fancy y recarga lista.
4. **Edicion**: carga datos previos correctamente, incluyendo password en blanco = "no cambiar" si aplica.
5. **Eliminacion**: pide confirmacion, aplica soft delete con el valor correcto de status, refresca lista.
6. **Errores de negocio**: forzar un duplicado/validacion fallida y confirmar SweetAlert con mensaje correcto (no un "Error inesperado" generico ni un 500).
7. **Seguridad por sucursal**: un administrador limitado no puede ver ni asignar sucursales fuera de su whitelist, ni editar registros de sucursales ajenas via id manipulado en la URL.
8. **Filtros**: refrescan el listado correctamente; si el filtro de sucursal es condicional (solo aparece con 2+ sucursales o admin), probar los tres escenarios.

---

## 18) Errores comunes que debes evitar

1. **Omitir el `try/catch` en `procesos.php`** — cualquier excepcion de negocio (duplicado, validacion) se vuelve un error fatal generico sin SweetAlert. Ver `agents_CRUD.md` seccion 5.
2. **Confiar en `idsucursal`/`sucursales[]` del `$_POST` sin validar contra `getSucursalesUsuario()`** — fuga de control de acceso.
3. **Inicializar Select2 manualmente dentro de un fancy** (en vez de dejarlo a `inicializarFancyX`) — puede romper los `select2` de filtros de la pantalla padre al cerrar el modal.
4. **`autocomplete="new-password"` en campos de password que el admin fija para otro usuario** — dispara agresivamente el aviso nativo de "guardar contrasena" del navegador.
5. **Romper la ruta de `guardar()`**: `guardar(form, modulo)` arma la URL como `/modulos/` + `modulo` + `/procesos.php` — pasar el string completo si el modulo tiene sub-ruta.
6. **Falta de `id="btnAccion"`** en el boton de guardar — `guardar()` lo manipula durante el submit.
7. **Responder solo `mensaje` o solo `texto`** — inconsistencia con `guardar()`/`eliminar()`.
8. **No acotar listados/altas a sucursal** — riesgo de fuga/modificacion de datos entre sucursales.
9. **Marcar un `select` booleano `0/1` como `requerido`** — `validateForm` tratara `0` como invalido.
10. **Inicializar DataTable sin verificar que la tabla existe** — usar `if ($("#miTabla").length) { ... }`.
11. **Usar `const`/`let` para variables globales dentro de un fancy** — al abrirse/cerrarse varias veces puede romper por redeclaracion; usar `var` para variables globales del fancy (`const`/`let` si son locales al scope de una funcion).
12. **Agregar una llamada a `formatearLabel()`/`fecha_display()` en `modulos/<modulo>.php` sin incluir `includes/generales.php`** — fatal error que rompe el render de toda la pagina, no solo del modulo (ver seccion 4).

---

## 19) Plantilla base resumida (copiar/pegar)

### 19.1 Router + vista (`<modulo>.php`)

```php
<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/<modulo>/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$entidadClase = new Entidad($con);
$tieneAcceso = $entidadClase->tieneAccesoModulo($idadministrador);
?>

<?php if (!$tieneAcceso) { ?>
    <div class="alert alert-warning">No tienes permiso para administrar entidad.</div>
<?php } else { ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h4 class="mb-1 font-weight-bold text-primary">
                        <i class="fas fa-icono mr-2"></i>
                        Entidad
                    </h4>
                </div>
                <div class="d-flex align-items-center">
                    <a href="javascript:;" data-fancybox
                       data-options='{"src":"/modulos/<modulo>/agregar.php","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
                       class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus"></i>
                        <span class="d-none d-md-inline">Agregar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="divLista"></div>

    <script>
        $(document).ready(function () { recargarLista(); });
        function recargarLista() {
            cargarLista("/modulos/<modulo>/lista.php", "", "divLista");
        }
    </script>
<?php } ?>
```

### 19.2 Dispatcher (`procesos.php`)

Ver plantilla completa (con `try/catch`) en `agent_manuals/agents_CRUD.md` seccion 5 — no la resumas al copiarla, el manejo de errores es la parte critica.

### 19.3 Eliminar (dentro de `lista.php`)

```html
<a href="javascript:;" onclick="eliminar('eliminarX','<modulo>','<?= (int) $id ?>')" class="btn btn-danger btn-sm" data-toggle="tooltip" title="Eliminar">
    <i class="fas fa-trash"></i>
</a>
```

---

## 20) Definicion de terminado (DoD)

Un modulo se considera terminado cuando:

1. Estructura plana completa (`<modulo>.php` + 4 archivos en `modulos/<modulo>/`).
2. Registrado en `topciones` (y `tradministradoropciones` si aplica permisos granulares).
3. Lista carga por AJAX con datos reales, acotados a sucursales permitidas.
4. Fancy de alta/edicion funcional, con Select2 scoped via `inicializarFancyX` (no manual).
5. `procesos.php` con `try/catch` que traduce excepciones de negocio a SweetAlert real.
6. Eliminacion (soft delete) funcional con el valor de status correcto para esa tabla.
7. Respuestas JSON compatibles con `guardar()`/`eliminar()` (`result/titulo/mensaje/texto`).
8. Prueba manual E2E pasada, incluyendo el camino de error de negocio y los escenarios de sucursal (admin / 1 sucursal / 2+ sucursales) si aplica.

---

## 21) Uso recomendado para otros agentes de IA

Antes de programar un modulo nuevo:

1. Leer este documento completo.
2. Leer `agent_manuals/agents_CRUD.md` (arquitectura de datos y manejo de errores — no te lo saltes, es donde esta el bug mas costoso de repetir).
3. Leer `agent_manuals/agents_FANCYS.md` si el modulo tiene fancy de alta/edicion.
4. Leer `agent_manuals/header-catalogo.md` para decidir el patron de encabezado.
5. Si algo en estos documentos parece no coincidir con el codigo real, **confia en el codigo** (`modulos/productos/`, `modulos/sucursales/`, `modulos/usuarios/`, `modulos/cortes/`, `modulos/cajeros/`) y senala la discrepancia para actualizar el manual.
6. Implementar en orden: `clase.php` -> `procesos.php` -> `lista.php` -> `agregar.php` -> router/vista -> migracion de menu.
