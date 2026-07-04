# Guia General para Crear Modulos del Sistema

Este documento es una referencia operativa para agentes de IA que necesiten crear un modulo nuevo en este sistema con el menor numero de dudas posible.

Objetivo:
- Explicar arquitectura, convenciones visuales y flujo tecnico real del proyecto.
- Estandarizar la forma de crear pantallas, listas AJAX, fancys (Fancybox), formularios, procesos y clases.
- Reducir retrabajo por errores de rutas, formato de respuesta JSON, validaciones de frontend, fechas y scoping por empresa.

Este manual complementa `agent_manuals/agents_CRUD.md`.

---

## 1) Mapa mental rapido del sistema

Cuando construyes un modulo nuevo, piensa en estas piezas:

1. Router del modulo padre
- Archivo tipico: `modulos/<modulo>.php`.
- Aqui se agrega el `case` de `$_GET["modulo2"]` para que el sistema enrute al submodulo.

2. Entrypoint del submodulo
- Archivo: `modulos/<modulo>/modulos/<submodulo>.php`.
- Es la vista principal que pinta header, filtros, card y contenedor de lista.
- Debe exponer una funcion global `recargarLista()`.

3. Carpeta del submodulo
- Ruta: `modulos/<modulo>/modulos/<submodulo>/`.
- Archivos base para un CRUD:
  - `lista.php`
  - `agregar.php` (fancy alta/edicion)
  - `procesos.php`
  - `clase.php`

4. Helpers globales JS
- Archivo principal: `js/funciones.js`.
- Helpers clave:
  - `cargarLista(url, variable, div)`
  - `guardar(form, modulo, closefancybox)`
  - `eliminar(proceso, modulo, id, callback, anuncio)`
  - `validateForm(form)`

5. Backend de negocio
- `clase.php` del submodulo concentra SQL y reglas.
- En este proyecto se trabaja con clase tipo `extends BaseClass` para modulos nuevos de estilo CRUD.

---

## 2) Flujo tecnico extremo a extremo

Flujo tipico para alta y listado:

1. Usuario abre `/CODIGO_EMPRESA/<modulo1>/<submodulo>`.
2. Router del modulo (`modulos/<modulo>.php`) incluye el entrypoint del submodulo.
3. Entrypoint ejecuta `recargarLista()`.
4. `recargarLista()` llama `cargarLista('/modulos/.../lista.php', parametros, 'divListaX')`.
5. `lista.php` consulta datos (via `clase.php`) y devuelve HTML de tabla + JS DataTable.
6. Usuario abre fancy de alta con `data-fancybox` hacia `agregar.php`.
7. `agregar.php` contiene formulario con hidden `proceso` y boton que invoca `guardar(...)`.
8. `guardar(...)` hace POST a `/modulos/<modulo>/modulos/<submodulo>/procesos.php`.
9. `procesos.php` hace `switch($_POST['proceso'])` y llama metodo en `clase.php`.
10. Respuesta JSON `success` dispara Swal, cierra fancy y recarga lista.

Flujo tipico para eliminar:

1. Boton en `lista.php` llama `eliminar('procesoDelete', 'modulo/ruta', id)`.
2. `eliminar(...)` muestra confirmacion.
3. Si confirma, hace POST a `procesos.php` con `proceso` + `id`.
4. Si responde `success`, recarga lista y muestra mensaje.

---

## 3) Estructura minima recomendada de archivos

Para un modulo nuevo (ejemplo generico `submoduloEjemplo` dentro de `moduloEjemplo`):

- `modulos/moduloEjemplo/modulos/submoduloEjemplo.php` (entrypoint)
- `modulos/moduloEjemplo/modulos/submoduloEjemplo/lista.php`
- `modulos/moduloEjemplo/modulos/submoduloEjemplo/agregar.php`
- `modulos/moduloEjemplo/modulos/submoduloEjemplo/procesos.php`
- `modulos/moduloEjemplo/modulos/submoduloEjemplo/clase.php`

Y agregar case en:
- `modulos/moduloEjemplo.php`

---

## 4) Integracion de router

En `modulos/<modulo>.php`, agregar case del submodulo:

```php
case "<submodulo>":
    include("modulos/<modulo>/modulos/<submodulo>.php");
    break;
```

Reglas:
- Mantener orden alfabetico/estructura existente del switch.
- No romper casos existentes.
- Verificar sintaxis con `php -l`.

---

## 5) Entrypoint del submodulo (pantalla principal)

Checklist visual de entrypoint:

1. Header superior
- Titulo `h2` con icono Font Awesome.
- Boton `AGREGAR` a la derecha (si aplica) que abre fancy.

2. Card principal
- Header de card: "Listado de ...".
- Body vacio con `id` donde se inyecta `lista.php`.

3. JS local
- `$(document).ready(() => recargarLista())`.
- Funcion `recargarLista()` que usa `cargarLista(...)`.

Ejemplo base:

```php
<div class="card-body" id="divListaX"></div>
<script>
$(document).ready(function () {
    recargarLista();
});
function recargarLista() {
    var url = "/modulos/<modulo>/modulos/<submodulo>/lista.php";
    cargarLista(url, "", "divListaX");
}
</script>
```

---

### 5.1 Convenciones de nombres en helpers

Regla obligatoria:
- Nunca usar nombres ofuscados o ambiguos para funciones helper (ejemplo: `e()`).
- Usar nombres semanticos y legibles que describan su intencion.

Estandar para escape de salida en vistas:
- Para helpers basados en `htmlspecialchars(...)`, usar `formatearLabel($value)`.
- Mantener este nombre consistente dentro del archivo para evitar duplicidad de criterios.

---

## 6) Reglas para `lista.php`

Objetivo de `lista.php`:
- Recibir filtros por `$_POST` (si hay).
- Obtener `idempresa` desde sesion.
- Consultar en `clase.php`.
- Pintar tabla con acciones.

Convenciones:

1. Includes
- `session.php`
- `clase.php` del submodulo

2. Seguridad
- Nunca confiar en `idempresa` del cliente.
- Siempre usar `$_SESSION['infoUsuario']['idempresa']`.

3. Tabla
- Clases sugeridas:
  - `table table-hover mb-0 nowrap dataTable no-footer table-sm small`

4. Acciones por fila
- Editar/Ver por fancy
- Eliminar por helper `eliminar(...)`

5. DataTable
- Inicializar solo si existe la tabla.
- Usar idioma espanol (bloque de language estandar).
- Generalmente `ordering: false` si asi lo usan modulos similares.

6. Empty state
- Si no hay registros, mostrar alert:
  - `alert alert-warning m-2 text-center`

---

## 7) Reglas para fancys (`agregar.php`, detalle.php, etc.)

Un "fancy" es una vista cargada dentro de Fancybox (tipo `ajax` o `iframe`).

### 7.1 Abrir fancy desde botones/enlaces

Patron recomendado:

```html
<a
  href="javascript:;"
  data-fancybox
  data-options='{"src":"/modulos/<modulo>/modulos/<submodulo>/agregar.php","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
  class="btn btn-sm btn-primary"
>
  <i class="fas fa-plus"></i> AGREGAR
</a>
```

Notas:
- `type: "ajax"` para formularios HTML.
- `type: "iframe"` para PDFs u otras paginas completas.
- `touch: false` evita comportamientos raros en scroll dentro del modal.

### 7.2 Estructura base de `agregar.php`

1. Includes de sesion y clase.
2. Cargar catalogos para selects.
3. Form con hidden `proceso`.
4. Boton con `id="btnAccion"` para compatibilidad con `guardar()`.
5. Inicializar `select2` con `dropdownParent` apuntando al contenedor del fancy.

Ejemplo:

```php
<form id="frmEntidad">
  <input type="hidden" name="proceso" value="agregarEntidad">
  <select class="form-control select2 requerido" name="idrelacion" id="idrelacion">
    <option value="0">-- SELECCIONA --</option>
  </select>
  <button type="button" id="btnAccion" onclick="guardar('frmEntidad','<modulo>/modulos/<submodulo>')">Agregar</button>
</form>
<script>
$(function(){
  $('#idrelacion').select2({
    dropdownParent: $('#fancyModalEntidad')
  });
});
</script>
```

### 7.3 Regla obligatoria de botones en fancys y swals

Aplicar siempre este estandar visual:

- Fancys:
  - El grupo de botones debe estar alineado a la derecha.
  - En acciones duales, la opcion negativa (`Cancelar`, `Cerrar`, `No`) va a la izquierda.
  - En acciones duales, la opcion positiva (`Guardar`, `Agregar`, `Crear`, `Continuar`, `Si`) va a la derecha.

- Swals:
  - El grupo de acciones debe estar centrado.
  - En acciones duales, mantener orden negativo izquierda / positivo derecha.
  - Si se usa SweetAlert2 con `showCancelButton: true`, forzar el orden visual con `reverseButtons: true` cuando sea necesario.

---

## 8) Validaciones frontend obligatorias

Funcion clave: `validateForm(form)` en `js/funciones.js`.

Reglas importantes:

1. Campos requeridos
- Marcar con clase `.requerido`.
- Inputs texto/password/email/file se validan por valor.
- Textarea se valida no vacio.

2. `select.requerido`
- Si valor es `0`, se considera invalido.
- Por eso, para select requerido, usa opcion default en `0`.

3. Caso booleanos (`0/1`)
- Si el select usa 0 como valor valido de negocio, evita marcarlo como `requerido` o ajusta estrategia.

4. UX
- `validateForm` muestra Swal warning cuando falla.

5. Regla de decimales para dinero
- Todo valor monetario (incluyendo tipo de cambio cuando se trate como monto operativo) debe manejarse con maximo 2 decimales al mostrar y al guardar.
- En frontend: usar `step="0.01"` para inputs monetarios y formateo a 2 decimales.
- En backend: normalizar y persistir montos a 2 decimales (sin confiar en el formato enviado por el cliente).

---

## 9) `procesos.php` (dispatcher AJAX)

Estructura recomendada:

1. Includes
- `includes/errors.php`
- `includes/session.php`
- `includes/generales.php`
- `clase.php` del submodulo

2. Variables base
- `$idempresa` desde sesion
- `$proceso` desde `$_POST['proceso']`

3. Switch
- Un case por accion.
- Default retorna error consistente.

4. Respuesta
- Siempre `Content-Type: application/json`.
- Siempre `json_encode` de arreglo.

Plantilla:

```php
switch ($proceso) {
    case 'agregarEntidad':
        $respuesta = $obj->agregarEntidad($_POST, $idempresa);
        break;
    case 'eliminarEntidad':
        $respuesta = $obj->eliminarEntidad($_POST, $idempresa);
        break;
    default:
        $respuesta = [
            'result' => 'error',
            'titulo' => 'Error',
            'mensaje' => 'No se encontro el proceso solicitado.',
            'texto' => 'No se encontro el proceso solicitado.',
        ];
}
```

---

## 10) `clase.php` (logica SQL/negocio)

### 10.1 Estructura recomendada

1. `require vendor/autoload.php`
2. `include baseClass.php`
3. `class <Nombre> extends BaseClass`
4. Constructor con conexion y `Queries`.

### 10.2 Reglas de negocio

1. Scoping por empresa
- Todas las lecturas y escrituras sensibles deben validar empresa.

2. Validaciones previas
- Requireds
- FK valida y de la empresa
- Duplicados (si aplica)

3. Integridad de salida
- Guardar/editar: devolver `result`, `titulo`, `mensaje`.
- Eliminar: devolver `result`, `titulo`, `texto`.
- Puedes incluir ambos (`mensaje` y `texto`) para mayor compatibilidad.

4. Errores
- No exponer SQL ni errores internos al usuario.
- Devolver mensaje funcional y loggear detalles si aplica.

5. Transacciones
- Usar transacciones cuando una accion afecta multiples tablas.

---

## 11) Convenciones de respuesta JSON (muy importante)

Para `guardar()`:
- Espera `response.mensaje`.

Para `eliminar()`:
- Espera `response.texto`.

Estrategia segura recomendada:
- En respuestas de exito y error devolver ambos campos:
  - `mensaje`
  - `texto`

Ejemplo:

```php
return [
  'result' => 'success',
  'titulo' => 'Bien',
  'mensaje' => 'Registro guardado correctamente.',
  'texto' => 'Registro guardado correctamente.'
];
```

---

## 12) Reglas visuales del sistema

### 12.1 Header de modulo

- Titulo principal con icono.
- Boton AGREGAR arriba derecha.
- Separador `hr` antes de la card.

### 12.2 Card de listado

- Header con texto "Listado de ...".
- Body con `id` para inyeccion AJAX.

### 12.3 Tabla estandar

- Clases: `table table-hover mb-0 nowrap dataTable table-sm small`.
- Columna de acciones al final.
- Botones `btn btn-sm` con iconos FA.

### 12.4 Botones

- Primario agregar/editar: `btn-primary`.
- Eliminar: `btn-danger`.
- Ver/Detalle: `btn-info`.

### 12.5 Tooltips

- Inicializar `$("[data-toggle='tooltip']").tooltip();` en listas.

---

## 13) Convenciones de fechas y horas

Reglas practicas del proyecto:

1. En BD
- Guardar fechas como `DATE` o `DATETIME` en formato SQL (`Y-m-d`, `Y-m-d H:i:s`).

2. En inputs de fecha
- Usar jQuery UI Datepicker.
- Formato habitual de captura: `yy-mm-dd`.
- Input readonly cuando se quiere forzar selector calendario.

3. En listados (visual)
- Mostrar amigable:
  - Fecha: `d/m/Y`
  - Fecha hora: `d/m/Y H:i`

4. Filtros por rango
- Validar que fecha inicial <= fecha final.
- Mantener el mismo formato en todo el modulo.

5. Zona horaria
- Seguir timezone del servidor/sesion actual del sistema.
- Evitar conversiones de timezone en frontend salvo requerimiento explicito.

---

## 14) Select2: reglas para no romper fancys

1. Dentro de fancy, siempre usar:

```js
$('#miSelect').select2({
  dropdownParent: $('#contenedorFancy')
});
```

2. Sin `dropdownParent`, el dropdown puede quedar fuera del modal o detras del overlay.

3. Para placeholders:
- Opcion default en `value="0"` o `""` segun validacion.
- `style="width: 100%;"` en el `select`.

---

## 15) Seguridad y aislamiento por empresa

Reglas no negociables:

1. `idempresa` siempre desde sesion.
2. Nunca aceptar `idempresa` del cliente para operaciones criticas.
3. Validar pertenencia en `SELECT`, `UPDATE`, `DELETE`.
4. En deletes, usar joins o condiciones que impidan borrar registros de otra empresa.
5. Escapar/parametrizar entradas.

---

## 16) Integracion con menu/permisos (contexto)

Importante:
- Que exista el `case` en router NO garantiza que el modulo salga en menu.
- El menu de varios modulos depende de submodulos/permisos en BD.
- Si el modulo no aparece en menu, revisar configuracion de submodulo y permisos de usuario.

---

## 17) Checklist de implementacion de un modulo nuevo

1. Definir alcance funcional exacto.
2. Crear carpeta de submodulo y archivos base.
3. Agregar `case` en router padre.
4. Crear entrypoint con `recargarLista()`.
5. Implementar `lista.php` con DataTable y acciones.
6. Implementar `agregar.php` en fancy con formulario y `guardar()`.
7. Implementar `procesos.php` con switch por `proceso`.
8. Implementar `clase.php` con scoping por empresa.
9. Verificar JSON (`mensaje` para guardar, `texto` para eliminar).
10. Correr `php -l` en todos los archivos.
11. Probar alta/lista/eliminacion en UI real.
12. Validar permisos/menu si aplica despliegue funcional completo.

---

## 17.1) Migraciones SQL (convencion operativa)

Regla del proyecto:
- Las migraciones o ajustes de BD pueden crearse como archivos `.sql` dentro de `scripts/`.
- Estos scripts son operativos/locales; no es obligatorio que Git los rastree.
- Se prioriza que el script exista para ejecucion en ambiente, aunque quede ignorado por `.gitignore`.

---

## 18) Checklist de QA funcional

1. Navegacion
- El modulo abre sin errores PHP/JS.

2. Lista
- Carga via AJAX.
- DataTable funciona (buscar/paginar).
- Empty state correcto cuando no hay datos.

3. Fancy alta
- Abre correctamente.
- Select2 se ve y selecciona bien.
- Validaciones frontend funcionan.
- Guardado crea registro real.
- Se cierra fancy y recarga lista.

4. Eliminacion
- Pide confirmacion.
- Elimina registro correcto.
- No elimina registros fuera de empresa.
- Refresca lista y muestra mensaje.

5. Fechas
- Formato consistente en captura y visualizacion.

6. Seguridad
- Intentos con IDs de otra empresa son bloqueados.

---

## 19) Errores comunes que debes evitar

1. Romper ruta de `guardar()`
- Recuerda que `guardar(form, modulo)` arma URL como `/modulos/` + modulo + `/procesos.php`.
- Si modulo es subruta, enviar string completo: `moduloEjemplo/modulos/<submodulo>`.

2. Falta de `id="btnAccion"`
- `guardar()` manipula `#btnAccion` durante el submit.

3. Select2 en fancy sin `dropdownParent`
- Causa dropdown cortado o invisible.

4. Responder solo `mensaje` o solo `texto`
- Inconsistencia con helpers.

5. No filtrar por empresa en queries
- Riesgo de fuga/modificacion de datos cruzados.

6. Marcar select booleano como requerido con valor `0` valido
- `validateForm` tomara 0 como invalido.

7. Inicializar DataTable cuando la tabla no existe
- Debe haber condicion `if ($('#miTabla').length) { ... }`.

8. Utilizar const o let en variables definidas en un fancy
- Al ser propensos a abrirse y cerrarse varias veces, usar este tipo de definiciones puede romper fancys.
- Todas las variables globales en fancys deben ser definidas con var.
- const y let pueden seguir usandose dentro del scope de una funcion definida en un fancy.
---

## 20) Plantilla base resumida (copiar/pegar)

### 20.1 Entrypoint (`<submodulo>.php`)

```php
<div class="card-body" id="divListaX"></div>
<script>
$(document).ready(function(){ recargarLista(); });
function recargarLista(){
  cargarLista('/modulos/<modulo>/modulos/<submodulo>/lista.php', '', 'divListaX');
}
</script>
```

### 20.2 Fancy (`agregar.php`)

```php
<form id="frmX">
  <input type="hidden" name="proceso" value="agregarX">
  <input type="text" class="form-control requerido" name="nombre">
  <button type="button" id="btnAccion" onclick="guardar('frmX','<modulo>/modulos/<submodulo>')">Agregar</button>
</form>
```

### 20.3 Dispatcher (`procesos.php`)

```php
switch($_POST['proceso'] ?? '') {
  case 'agregarX':
    $respuesta = $x->agregarX($_POST, $idempresa);
    break;
  case 'eliminarX':
    $respuesta = $x->eliminarX($_POST, $idempresa);
    break;
  default:
    $respuesta = ['result'=>'error','titulo'=>'Error','mensaje'=>'Proceso no encontrado','texto'=>'Proceso no encontrado'];
}
```

### 20.4 Lista (`lista.php`)

```php
<a href="javascript:;" onclick="eliminar('eliminarX','<modulo>/modulos/<submodulo>','<?= (int)$id ?>')" class="btn btn-danger btn-sm">
  <i class="fas fa-trash"></i>
</a>
```

---

## 21) Definicion de terminado (DoD)

Un modulo se considera terminado cuando:

1. Tiene estructura completa y enrutable.
2. Lista carga por AJAX y muestra datos reales.
3. Fancy de alta funcional con validaciones.
4. Eliminacion funcional con confirmacion.
5. Scoping por empresa validado en backend.
6. Respuestas JSON compatibles con helpers.
7. `php -l` sin errores.
8. Prueba manual E2E pasada.

---

## 22) Uso recomendado para otros agentes de IA

Antes de programar un modulo nuevo, la instancia debe:

1. Leer este documento completo.
2. Leer `agent_manuals/agents_CRUD.md`.
3. Identificar modulo padre y router correcto.
4. Confirmar si es CRUD simple, modulo de flujo o reporte.
5. Crear primero el esqueleto de archivos.
6. Implementar en orden: router -> entrypoint -> lista -> fancy -> procesos -> clase.
7. Validar sintaxis y flujo UI.

Con esto, en la mayoria de casos no deberia necesitar preguntas adicionales salvo reglas de negocio particulares.
