# Guia CRUD del Sistema

Este documento resume la arquitectura y patrones **reales, verificados en codigo vivo** para construir CRUDs en este sistema. Los modulos de referencia son `productos`, `sucursales`, `usuarios` y `cajeros` (`modulos/productos/`, `modulos/sucursales/`, `modulos/usuarios/`, `modulos/cajeros/`). `cortes` (`modulos/cortes/`) es la referencia para listados de solo lectura con filtros.

> Este sistema es de un solo tenant (una sola "empresa"/negocio con varias sucursales). **No existe `idempresa`, no existe la clase `Permisos`, no existe la clase `Modulos`.** Si encuentras esos nombres en codigo viejo o en otro documento, son vestigios de una plantilla multi-tenant heredada (`intranet-xensei`) que nunca se conecto al flujo real de este proyecto. No los repliques en modulos nuevos.

---

## 1) Arquitectura base (real)

Estructura **plana**, sin submodulos anidados:

```
modulos/<modulo>.php          Router + vista principal (se incluye desde home.php)
modulos/<modulo>/clase.php    Logica SQL/negocio (extends BaseClass)
modulos/<modulo>/lista.php    Listado via AJAX (tabla DataTable)
modulos/<modulo>/agregar.php  Fancy de alta/edicion
modulos/<modulo>/procesos.php Dispatcher AJAX (switch por proceso)
```

No hay `modulos/<modulo>/modulos/<submodulo>/`. No hay `case` de `modulo2` que registrar en ningun router padre. Si el modulo necesita mas de una pantalla en el futuro, no hay todavia un patron vivo de sub-navegacion — no inventar uno sin antes confirmarlo con el equipo.

### Como se registra un modulo nuevo

No existe un router central con `switch`. `home.php` arma el sidebar dinamicamente desde la tabla `topciones` y resuelve `modulos/{primer-segmento-de-topciones.url}.php`. Para dar de alta un modulo:

1. Crear un script en `scripts/00N_<descripcion>.sql` (numeracion secuencial, ver todos los `scripts/*.sql` existentes para el siguiente numero libre) con:
   ```sql
   INSERT INTO topciones (nombre, url, icono) VALUES ('NombreVisible', 'urlmodulo', 'fas fa-icono');
   ```
2. Si el sistema usa permisos granulares por administrador (`admin = 0`), agregar tambien filas en `tradministradoropciones` por cada administrador que deba ver el modulo. Documentar este paso en un comentario dentro del mismo `.sql`.
3. Ejecutar el script manualmente en el entorno correspondiente (no hay migraciones automaticas).

---

## 2) Sesion y control de acceso (real)

No hay `idempresa`. Las claves reales de sesion son:

```php
$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$esAdmin          = ($_SESSION["infoUsuario"]["admin"] ?? 0) == 1; // 1 = superadmin, acceso total
```

Cada `clase.php` implementa su propio metodo de acceso al modulo (no existe una clase `Permisos` central):

```php
public function tieneAccesoModulo($idadministrador) {
    if (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1)
        return true;

    $query = "
    select 1
    from tradministradoropciones ao
    inner join topciones o on o.idopcion = ao.idopcion
    where ao.idadministrador = ? and o.url = '<url-del-modulo>'
    limit 1
    ";
    $fila = $this->claseQueries->fetchResults($query, array($idadministrador), false);
    return !empty($fila);
}
```

Esta validacion debe llamarse en **los cuatro archivos** (`<modulo>.php`, `lista.php`, `agregar.php`, `procesos.php`) — el menu oculto no es suficiente, cada entrypoint AJAX es alcanzable directamente por URL.

---

## 3) Aislamiento por sucursal (reemplaza "scoping por empresa")

No hay empresas que aislar; hay **sucursales** (`tsucursales`) y cada administrador limitado (`admin = 0`) tiene acceso solo a un subconjunto via la tabla puente `tradminsucursales`. El patron obligatorio en **todo** modulo cuyos datos se relacionan con una sucursal:

```php
/**
 * Sucursales que el usuario actual puede ver/asignar: todas las activas si
 * es admin, o solo las que tiene explicitamente en tradminsucursales.
 */
public function getSucursalesUsuario($idadministrador) {
    if (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1) {
        $query = "select idsucursal, nombre from tsucursales where status = 1 order by nombre";
        return $this->claseQueries->fetchResults($query);
    }

    $query = "
    select s.idsucursal, s.nombre
    from tsucursales s
    inner join tradminsucursales a on a.idsucursal = s.idsucursal
    where a.idadministrador = ? and s.status = 1
    order by s.nombre
    ";
    return $this->claseQueries->fetchResults($query, array($idadministrador));
}
```

Reglas no negociables:

1. **Listados**: filtrar siempre `WHERE idsucursal IN (whitelist)` — nunca listar todo y dejar el filtro solo del lado visual.
2. **Alta/edicion**: la(s) sucursal(es) que llegan por `$_POST` deben intersectarse/validarse contra `getSucursalesUsuario($idadministrador)` **en el servidor**, sin importar que el `<select>`/checkboxes del frontend ya esten filtrados. Nunca confiar en el id de sucursal que manda el cliente.
   - Relacion 1 sucursal por registro (ej. `cajeros`, `idsucursal` es columna directa): validar con `in_array($idsucursal, $sucursalesPermitidas)`.
   - Relacion N sucursales por registro (ej. `productos` via tabla puente `tproductosucursales`): usar `array_intersect()` entre lo recibido y `getSucursalesUsuario()`.
3. **Edicion/eliminacion de un registro puntual**: al cargar por id, validar que su sucursal (o al menos una de sus sucursales) este dentro de la whitelist del usuario actual; si no, tratarlo como "no encontrado" (evita fuga de datos de sucursales ajenas via URL manipulada).

Ejemplo real de intersección (alta con una sola sucursal, `modulos/cajeros/clase.php`):

```php
private function resolverSucursal($post, $sucursalesPermitidas) {
    $idsucursal = (int) ($post["idsucursal"] ?? 0);
    if ($idsucursal <= 0 || !in_array($idsucursal, $sucursalesPermitidas))
        throw new Exception("Selecciona una sucursal a la que tengas acceso.|Atencion|mensaje|warning", 1);
    return $idsucursal;
}
```

---

## 4) Flujo de ejecucion (R/C/U/D)

1. **Router + vista (`<modulo>.php`)**: se incluye desde `home.php`, que YA incluyo `session.php` y `conn.php` antes — no los vuelvas a incluir aqui. Si usas `formatearLabel()`, incluye `includes/generales.php` explicitamente (el router NO lo hereda de `home.php`). Renderiza titulo, boton Agregar, filtros (si aplica) y `<div id="divLista"></div>`. Expone `recargarLista()`.

2. **Listado (`lista.php`)**: entrypoint AJAX independiente — **debe bootstrapear todo por su cuenta**:
   ```php
   include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
   include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
   include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
   include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
   include($_SERVER["DOCUMENT_ROOT"] . "/modulos/<modulo>/clase.php");
   ```
   Valida `tieneAccesoModulo()`, obtiene filtros de `$_POST`, consulta via `clase.php` (siempre acotado a `getSucursalesUsuario()`), renderiza tabla DataTable envuelta en su propia card.

3. **Fancy de alta/edicion (`agregar.php`)**: mismos includes que `lista.php`. Determina modo por `$_GET["id"]`. Sigue el patron de `agent_manuals/agents_FANCYS.md`.

4. **Dispatcher (`procesos.php`)**: mismos includes + `header("Content-Type: application/json")`. Ver seccion 5 (manejo de errores) — **es la parte donde mas se ha fallado en la practica**.

5. **Capa de negocio (`clase.php`)**: `class <Nombre> extends BaseClass`, constructor estandar (ver seccion 6).

---

## 5) Manejo de errores en `procesos.php` (CRITICO — leer antes de escribir el dispatcher)

`BaseClass` tiene un mecanismo de manejo de excepciones via el metodo magico `__call()` (y su alias `execute()`), que en teoria captura excepciones de negocio (codigo `1`, formato `"mensaje|titulo|tipo|icono"`) y las convierte en JSON amigable para SweetAlert.

**Ese mecanismo NO se usa en la practica y ademas esta roto en este entorno**: `__call()` llama a `logCall()`, que hace `INSERT`/`SELECT` contra la tabla `tzllamadasmetodos`, la cual **no existe en la base de datos actual** (confirmado en `txts/excepciones.txt`). Todos los modulos reales (`productos`, `sucursales`, `usuarios`, `cortes`) llaman los metodos de negocio **directamente** (`$productos->agregarProducto($_POST)`), lo cual bypassa `__call()` por completo — PHP solo invoca `__call()` para metodos inexistentes/inaccesibles, nunca para metodos publicos que si existen.

**Consecuencia real ya reproducida en produccion**: cuando `agregarCajero()` lanzaba `throw new Exception("Ya existe un cajero...|Atencion|mensaje|warning", 1)`, la excepcion quedaba **sin capturar**, PHP la trataba como error fatal, y el usuario veia `"Error inesperado (Codigo #003-XXXXXXXX)"` sin ningun SweetAlert — en vez del mensaje de negocio esperado.

### Regla obligatoria para todo `procesos.php` nuevo

Envolver el `switch` en su propio `try/catch` que reconoce el formato `"mensaje|titulo|tipo|icono"` y arma el JSON amigable manualmente, **sin depender de `__call()`/`execute()`**:

```php
$proceso = $_POST["proceso"] ?? "";

try {
    switch ($proceso) {
        case "agregarEntidad":
            $respuesta = $entidad->agregarEntidad($_POST);
            break;
        case "editarEntidad":
            $respuesta = $entidad->editarEntidad($_POST);
            break;
        case "eliminarEntidad":
            $respuesta = $entidad->eliminarEntidad($_POST);
            break;
        default:
            $respuesta = array(
                "result" => "error",
                "titulo" => "Error",
                "mensaje" => "No se encontro el proceso solicitado.",
                "texto" => "No se encontro el proceso solicitado.",
            );
            break;
    }
} catch (Exception $e) {
    // Excepciones de negocio (validaciones, duplicados, permisos) se lanzan
    // con el formato "mensaje|titulo|tipo|icono" y codigo 1.
    if ($e->getCode() === 1 && strpos($e->getMessage(), "|") !== false) {
        list($mensaje, $titulo, $tipo, $icono) = array_pad(explode("|", $e->getMessage(), 4), 4, "");
        $respuesta = array(
            "result" => "error",
            "titulo" => ($titulo !== "") ? $titulo : "Atencion",
            "mensaje" => $mensaje,
            "texto" => $mensaje,
            "icono" => ($icono !== "") ? $icono : "warning",
        );
    } else {
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/txts/excepciones.txt", "<modulo>/procesos.php ($proceso): " . $e->getMessage() . " -- " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
        $respuesta = array(
            "result" => "error",
            "titulo" => "Error",
            "mensaje" => "Ocurrio un error inesperado. Intenta de nuevo.",
            "texto" => "Ocurrio un error inesperado. Intenta de nuevo.",
            "icono" => "error",
        );
    }
}

echo json_encode($respuesta);
```

Referencia real: `modulos/cajeros/procesos.php`. **Si vas a corregir modulos viejos** (`productos`, `sucursales`, `usuarios`), este mismo `try/catch` les falta — es deuda tecnica conocida, no un patron a copiar tal cual esta hoy.

No uses `accion()`/`input()`/`generarBreadCrumb()` de `BaseClass` — son helpers de otra arquitectura de respuesta (mas declarativa, con `opciones_swal`, `abrir_fancybox`, etc.) que ningun modulo real usa hoy. Los modulos reales devuelven arreglos planos `result/titulo/mensaje/texto/icono` directamente.

---

## 6) `clase.php`: estructura y metodos recomendados

### 6.1 Constructor estandar (copiar tal cual)

```php
<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Entidad extends BaseClass {

    private $con, $isDebugger;
    protected $claseQueries;

    public function __construct($con = null, $pdo = null) {
        if ($con === null)
            include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
        include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/queries.php");
        include_once($_SERVER["DOCUMENT_ROOT"] . "/config/environment.php");
        $this->con = $con;
        $this->isDebugger = $_SESSION["infoUsuario"]["debugger"] ?? 0;
        $this->claseQueries = new Queries($con, $pdo, $this->isDebugger);
        register_shutdown_function(array($this, "handleFatalError"));
    }

    public function isDebugger() {
        return $this->isDebugger;
    }
    // ...
}
?>
```

### 6.2 Metodos tipicos

1. `tieneAccesoModulo($idadministrador)` — ver seccion 2.
2. `getSucursalesUsuario($idadministrador)` — ver seccion 3 (solo si el modulo se relaciona con sucursales).
3. `mostrarSelectorSucursal($idadministrador)` — si el modulo muestra un filtro/selector de sucursal, decide cuando tiene sentido mostrarlo (ej. `admin == 1 || count(getSucursalesUsuario()) > 1`; ver `modulos/cajeros/clase.php`).
4. `get<Entidades>($idadministrador, $filtros = array())` — listado acotado a sucursales permitidas + filtros opcionales.
5. `get<Entidad>($id, $idadministrador)` — un registro, validando pertenencia a sucursal permitida.
6. `existe<Campo>Duplicado($valor, ...$idExcluir)` — unicidad. **Definir explicitamente el alcance**: unico global (ej. correo de administrador) vs unico por sucursal (ej. usuario de cajero por sucursal) — no asumir, confirmar con la regla de negocio real.
7. `agregar<Entidad>($post)`, `editar<Entidad>($post)`, `eliminar<Entidad>($post)` — ver seccion 7.

### 6.3 Excepciones de negocio

```php
throw new Exception("El nombre es obligatorio.|Atencion|mensaje|warning", 1);
```

Formato: `"mensaje|titulo|tipo|icono"`, codigo siempre `1` para que `procesos.php` (seccion 5) lo distinga de un error real/inesperado. `tipo` en la practica siempre es el literal `"mensaje"` (no se usan las variantes `single_toast`/`notificacion` de `BaseClass::handleMyException`, que tampoco se invoca).

### 6.4 Transacciones

Cuando una accion escribe en mas de una tabla (ej. entidad + tabla puente de sucursales):

```php
mysqli_begin_transaction($this->con);
try {
    // inserts/updates
    mysqli_commit($this->con);
} catch (Exception $e) {
    mysqli_rollback($this->con);
    throw $e;
}
```

---

## 7) Convenciones de respuesta JSON

```php
return array(
    "result"  => "success",         // success|error|info|warning
    "titulo"  => "Listo",
    "mensaje" => "Registro guardado correctamente.",
    "texto"   => "Registro guardado correctamente.", // mismo texto, para eliminar()
);
```

`guardar()` (en `js/funciones.js`) lee `response.texto || response.mensaje`; `eliminar()` tambien. Regresar ambas claves siempre evita inconsistencias.

---

## 8) Convenciones de datos

1. **Password de credenciales gestionadas por un admin (no su propio login)**: `AES_ENCRYPT(?, '<SEED>')` con un seed propio por tipo de credencial definido en `config/environment.php` (ej. `SEED_ADMINISTRADORES`, `SEED_CAJEROS`) — no reusar el mismo seed entre contextos distintos.
   - En el `<input type="password">`, usar `autocomplete="off"` — **nunca** `autocomplete="new-password"`. Ese valor le indica activamente a Chrome "aqui se define una contrasena nueva, ofrecete a guardarla", lo cual dispara el aviso nativo de "guardar contrasena" incluso cuando el admin esta fijando la contrasena de OTRO usuario (cajero, empleado), no la suya. `autocomplete="off"` reduce (no garantiza al 100%) ese comportamiento.
   - El cierre de cualquier fancy ya limpia los `input[type=password]` automaticamente (handler global `beforeClose.fb` en `js/funciones.js`) — no repetir esa logica por modulo.
2. **Soft delete**: revisar el tipo real de la columna de estado antes de asumir — no todas las tablas usan `int 1/0` (`tsucursales`, `tadministradores`, `tproductos` si; `tusuarios` de cajeros usa `varchar(3)` con `'A'`/`'I'`). Nunca hacer `DELETE` en tablas maestras.
3. **Fechas**: BD en `Y-m-d`/`Y-m-d H:i:s`. Inputs de captura con `<input type="date">` nativo (no jQuery UI Datepicker — no se usa en los modulos reales). Listados con `fecha_display($fecha)` de `includes/generales.php` (formatea a `d/m/Y`, maneja `0000-00-00`).
4. **Montos**: maximo 2 decimales, tanto en front (`step="0.01"`) como en backend (`round((float) $valor, 2)`).
5. **IDs**: nombres de columna reales de la tabla (`idsucursal`, `idusuario`, etc.), sin abstraer con alias genericos salvo necesidad real.

---

## 9) Regla critica de validacion frontend

`js/funciones.js -> validateForm(form)` considera invalido cualquier `select.requerido` con valor `"0"`. Si un booleano usa valores `0/1` en un `<select>`, **no** marcarlo `requerido`; validar `in_array($valor, [0,1])` en backend en su lugar.

---

## 10) Select2 dentro de fancys — no inicializar manualmente

`inicializarFancyX(selector)` (en `js/funciones.js`) ya inicializa **todos** los `.select2` dentro del fancy con `dropdownParent` scoped automaticamente:

```js
$(selector + " .select2").select2({ dropdownParent: $(selector) });
```

Por lo tanto, dentro de un fancy: agrega la clase `select2` al `<select>` y llama `inicializarFancyX("#fancyX")` en `$(document).ready` — **no** escribas tu propio `.select2({...})` manual, ni siquiera para pasar `dropdownParent`. Escribirlo manual y ademas de forma global (`$(".select2").select2(...)` sin scoping) es el bug clasico que rompe los filtros `select2` de la pantalla principal cuando se abre/cierra un modal.

Fuera de fancys (ej. filtros de encabezado en `<modulo>.php`), el `.select2` se inicializa solo, via el `$(document).ready` global de `home.php` — tampoco hace falta llamarlo ahi.

---

## 11) Checklist de implementacion

1. Confirmar si el modulo se relaciona con sucursales (si si, todo dato debe pasar por `getSucursalesUsuario()`).
2. Crear los 4 archivos: `<modulo>.php`, `<modulo>/clase.php`, `<modulo>/lista.php`, `<modulo>/agregar.php`, `<modulo>/procesos.php`.
3. `clase.php`: constructor estandar, `tieneAccesoModulo()`, `getSucursalesUsuario()` si aplica, metodos CRUD.
4. `procesos.php`: dispatcher con `try/catch` (seccion 5) — no omitir.
5. Validar duplicados con el alcance correcto (global vs por sucursal).
6. Crear `scripts/00N_<modulo>_menu.sql` para registrar el modulo en `topciones`.
7. Confirmar formato JSON (`result/titulo/mensaje/texto/icono`).
8. Revisar balance de llaves/parentesis (no hay `php -l` disponible en algunos entornos de desarrollo; verificar con cuidado).
9. Probar: alta, edicion, eliminacion (soft delete), duplicados, filtros, y — si aplica — un administrador limitado con 1 sola sucursal vs 2+ sucursales vs admin.
10. Probar explicitamente el camino de error de negocio (ej. forzar un duplicado) y confirmar que aparece un SweetAlert con el mensaje correcto, no un error generico.

---

Usar esta guia como baseline y ajustar solo reglas de negocio especificas de cada tabla. Ante cualquier duda sobre si un patron es real, verificar contra `modulos/productos/`, `modulos/sucursales/`, `modulos/usuarios/`, `modulos/cortes/` o `modulos/cajeros/` — son la fuente de verdad, no este documento.
