# CLAUDE.md — Admin La Juguería

## Descripción del Proyecto

Sistema administrativo interno en PHP tradicional. Cada empresa/sucursal accede por `?cd=CODIGO` en la URL (o por el código ya guardado en sesión). Sin frameworks modernos — PHP 8.2 con MySQLi directo, jQuery en frontend.

---

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2, MySQLi (sin ORM) |
| Frontend | jQuery 3.x, Bootstrap 4 |
| Modales | FancyBox 4 |
| Alertas | SweetAlert 2 |
| Tablas | DataTables |
| Selects | Select2 |

No hay `composer.json` todavía — se agrega cuando el proyecto lo necesite (ej. `phpmailer/phpmailer` para correo, `dompdf/dompdf` para PDFs, `phpoffice/phpspreadsheet` para Excel). No instalar nada de esto por adelantado.

---

## Estructura de Carpetas

```
/
├── includes/          Utilidades compartidas (clases, helpers, correos, conexión)
│   ├── conn.example.php     Plantilla de conexión MySQLi (copiar como conn.php, ignorado en git)
│   ├── session.example.php  Plantilla de arranque de sesión (copiar como session.php, ignorado en git)
│   ├── hosts.example.php    Plantilla de configuración de entorno (copiar como hosts.php, ignorado en git)
│   ├── generales.php        Funciones globales PHP
│   └── seguridad2.php       Control de expiración de sesión (timeout, redirect, JSON 401)
├── controlador/       Dispatcher AJAX central
│   ├── procesos.php   Router AJAX principal (switch por módulo)
│   ├── procesos_*.php Procesadores por módulo
│   └── clases/        Clases base y servicios (baseClass.php, permisos.php, modulos.php, queries.php)
├── modulos/           Módulos de funcionalidad (ver estructura abajo)
│   └── reusables/     Traits, helpers y componentes genéricos reutilizables entre módulos
├── js/
│   └── funciones.js   Funciones JS globales (solicitudServidor, cargarLista, guardar, eliminar, validateForm)
├── css/               Hojas de estilo globales (incluye clases fancy-x-* para modales)
├── vendor/            Librerías frontend estáticas (jQuery, Bootstrap, DataTables, Select2,
│                      FancyBox, SweetAlert2, jQuery Validate, Font Awesome Pro 5)
├── agent_manuals/     Guías de desarrollo (LEER ANTES DE IMPLEMENTAR)
├── index.php          Login (punto de entrada)
└── home.php           Dashboard + router de módulos
```

---

## Sistema de Routing

**Sin router HTTP.** Navegación por query strings y `switch/case` en PHP.

```
GET /?cd=CODIGO                          → index.php (login)
GET /home.php?modulo1=moduloEjemplo      → carga modulos/moduloEjemplo.php
GET /home.php?modulo1=moduloEjemplo&modulo2=submoduloEjemplo → carga sub-módulo
```

El dispatcher AJAX en `/controlador/procesos.php` hace switch por `modulo1` y delega a `procesos_[modulo].php`.

---

## Patrón AJAX

**Función JS central:** `solicitudServidor()` en `/js/funciones.js`

```javascript
solicitudServidor({
    controlador: "procesos.php",
    accion: "nombreAccion",
    datos: { key: value },
    tipo_confirmacion: "success" // opcional
});
```

**Formato JSON de respuesta (SIEMPRE este formato):**

```json
{
    "result": "success|error|info|warning",
    "titulo": "Título del mensaje",
    "mensaje": "Descripción del resultado",
    "icono": "success|error|warning|info"
}
```

Para eliminaciones usar `"texto"` en vez de `"mensaje"`.

---

## Sesión y Permisos

```php
// Siempre incluir al inicio de cada archivo PHP
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");

// idempresa SIEMPRE desde sesión, nunca del cliente
$idempresa = $_SESSION["infoUsuario"]["idempresa"];
$idusuario = $_SESSION["infoUsuario"]["idusuario"];

// Colores de empresa disponibles en sesión
$color1 = $_SESSION["infoUsuario"]["color1"];
$color2 = $_SESSION["infoUsuario"]["color2"];

// Verificar permiso granular
$permisos = new Permisos();
if ($permisos->tienePermiso($idusuario, "btnNombreBoton")) { ... }
```

Timeout de sesión controlado por `/includes/seguridad2.php`. AJAX recibe HTTP 401 con JSON + `authToken` renovado. Páginas normales redirigen a login.

---

## Estructura de un Módulo

```
modulos/<modulo>/
├── <modulo>.php          Router del módulo (switch modulo2)
├── clase.php             Lógica de BD del módulo
├── procesos.php          Dispatcher AJAX del módulo
├── funciones.js          JS específico del módulo
├── menu_<modulo>.php     Menú lateral
└── modulos/
    └── <submodulo>/
        ├── lista.php     Vista de tabla (DataTables)
        ├── agregar.php   Formulario alta/edición (FancyBox)
        ├── procesos.php  AJAX del submodulo
        └── clase.php     Lógica específica
```

---

## Convenciones de Código

- **Todo en español**: variables, funciones, comentarios, UI
- **Rutas absolutas**: siempre `$_SERVER["DOCUMENT_ROOT"] . "/ruta/archivo.php"`
- **Soft delete**: campo `activo = 0` o `status = <status de eliminacion>`, nunca `DELETE` en tablas maestras
- **Fechas en BD**: formato `Y-m-d` y `Y-m-d H:i:s`
- **Montos**: máximo 2 decimales
- **`idempresa`**: Priorizar desde `$_SESSION`, si no esta disponible, desde `$_POST` o `$_GET`
- **Consultas SQL**: MySQLi directo, usar sentencias preparadas o `mysqli_real_escape_string()` para inputs
- **Clases PHP**: heredan de `BaseClass` en `/controlador/clases/baseClass.php`

---

## UI/UX — Patrones Obligatorios

### FancyBox (Modales)
- Las clases base `fancy-x-*` están definidas globalmente en `css/style.php`. No duplicarlas en cada fancy.
- El único `<style>` inline necesario por fancy es el bloque que inyecta los colores de empresa via PHP (`--fancy-color-1`, `--fancy-color-2`) en el selector del fancy.
- Llamar `inicializarFancyX("#fancyId")` en `$(document).ready` para ajustar el contenedor Fancybox.
- Ver `agent_manuals/agents_FANCYS.md` para el patrón completo.

### SweetAlert 2
Usar para confirmaciones y mensajes resultado de AJAX. La función `solicitudServidor()` ya lo maneja automáticamente.

### Select2
- Siempre scoped con `dropdownParent: $("#fancyId")` dentro de un fancy.
- Nunca llamar `.select2("destroy")` sin verificar antes que ya está inicializado — lanza un error. Verificar con `$el.hasClass("select2-hidden-accessible")`.
- Cuando el contenido del select cambia dinámicamente, mantener el estado de opciones en un array JS propio (no leer del DOM con `$select.find("option")`). Usar ese array como fuente de verdad y re-inicializar desde él.

```js
// Patrón seguro de reinicialización
function reinicializarSelect2() {
    var $select = $("#miSelect");
    if ($select.hasClass("select2-hidden-accessible")) {
        $select.select2("destroy");
    }
    $select.empty().select2({ dropdownParent: $("#fancyId"), data: misOpciones });
}
```

### DataTables + AJAX
Las listas se cargan con `cargarLista(url, variable, divId)` desde `/js/funciones.js`.

---

## Manuales de Desarrollo (Leer antes de implementar)

Ubicados en `/agent_manuals/`:

| Manual | Contenido |
|--------|-----------|
| `agents_CRUD.md` | Flujo completo CRUD, formato JSON, convenciones |
| `agents_MODULO.md` | Crear módulos desde cero, rutas, breadcrumbs, permisos |
| `agents_FANCYS.md` | Principios visuales, clases fancy-x-* globales, colores por empresa, Select2 en fancys |
| `header-catalogo.md` | Patrón estándar de encabezado de catálogo (con y sin filtros) |

---

## Archivos Ignorados en Git (Credenciales)

Los siguientes archivos **no están en el repositorio** y deben crearse localmente a partir de su plantilla `*.example.php`:

- `/includes/conn.php` — Credenciales de BD (plantilla: `conn.example.php`)
- `/includes/session.php` — Configuración de sesión (plantilla: `session.example.php`)
- `/includes/hosts.php` — Configuración de entorno/hosts (plantilla: `hosts.example.php`)

---

## Multi-empresa / Multi-tenant

- Empresa identificada por `$_SESSION["infoUsuario"]["idempresa"]`
- Código de empresa para routing/login: `$_SESSION["infoUsuario"]["codigo"]` (o `codigo_empresa`)
- Parámetros por empresa consultados vía capa de negocio (`clase.php` de cada módulo)
