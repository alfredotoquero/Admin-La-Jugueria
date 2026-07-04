# Guia CRUD del Sistema

Este documento resume la arquitectura y patrones usados para construir CRUDs en este sistema, tomando como base los modulos CRUD recientes del sistema (ejemplo generico: `moduloEjemplo` y `otroModuloEjemplo`).

## 1) Arquitectura base

Cada CRUD sigue esta estructura:

1. Router del modulo principal:
- Archivo: `modulos/<modulo>.php`
- Se agrega un `case "<submodulo>"` que incluye el entrypoint.

2. Entrypoint del submodulo:
- Archivo: `modulos/<modulo>/modulos/<submodulo>.php`
- Contiene la pantalla principal (titulo, boton AGREGAR, filtros opcionales, y `div` contenedor de lista sin card — la card vive dentro de `lista.php`, ver seccion 7).
- Debe exponer `recargarLista()`.

3. Carpeta del CRUD:
- Ruta: `modulos/<modulo>/modulos/<submodulo>/`
- Archivos minimos:
  - `lista.php` (tabla/listado via AJAX)
  - `agregar.php` (modal alta/edicion)
  - `procesos.php` (dispatcher AJAX)
  - `clase.php` (logica SQL/negocio)

## 2) Flujo de ejecucion (R/C/U/D)

1. Vista principal (`<submodulo>.php`):
- Renderiza UI general.
- Llama `recargarLista()` al cargar.
- `recargarLista()` usa:
  - `cargarLista("/modulos/<modulo>/modulos/<submodulo>/lista.php", parametros, "divLista...")`.

2. Listado (`lista.php`):
- Incluye `session.php` y `clase.php`.
- Obtiene `idempresa` de sesion.
- Consulta datos con metodos de clase.
- Renderiza tabla DataTable (idioma espanol del sistema).
- Botones por fila:
  - Editar: abre `agregar.php?id...` con fancybox.
  - Eliminar: `eliminar('<procesoDelete>', '<rutaModuloProcesos>', id)`.

3. Modal (`agregar.php`):
- Incluye `session.php` y `clase.php`.
- Determina modo:
  - Alta: `proceso = agregar...`
  - Edicion: si llega id, carga info y `proceso = editar...`
- Boton guardar:
  - `guardar('<idFormulario>', '<modulo>/modulos/<submodulo>')`
  - Esto POSTea a `/modulos/<modulo>/modulos/<submodulo>/procesos.php`.

4. Dispatcher (`procesos.php`):
- Incluye `errors.php`, `session.php`, `generales.php`, `clase.php`.
- Toma `idempresa` de sesion.
- `switch($_POST["proceso"])` y llama metodo correspondiente.
- Responde JSON con estructura uniforme.

5. Capa de negocio (`clase.php`):
- Clase extiende `BaseClass`.
- Constructor abre conexion (si no se inyecta).
- Reglas:
  - scoping por empresa
  - validaciones de requeridos
  - validaciones de catalogos relacionados
  - validaciones de duplicado
  - soft delete cuando aplique

## 3) Convenciones de respuesta JSON

Se usan dos formatos en el sistema (compatibilidad):

1. Guardar/editar:
- Claves esperadas por `guardar()`:
  - `result`: `success|error`
  - `titulo`
  - `mensaje`

2. Eliminar:
- Claves usadas comunmente:
  - `result`: `success|error`
  - `titulo`
  - `texto`

Nota: respetar esto evita inconsistencias con `Swal`.

## 4) Convenciones de datos

1. Empresa:
- Nunca confiar en el cliente para `idempresa`.
- Siempre usar `$_SESSION["infoUsuario"]["idempresa"]`.

2. IDs:
- Mantener nombres de variable legibles (`idalmacen`, `idtipoalmacen`, etc.).
- En SQL usar la columna real de la tabla y alias si es necesario:
  - Ejemplo: `id AS idalmacen`.

3. Soft delete:
- Si el modulo usa inactivacion:
  - Alta: `activo = 1`
  - Eliminar: `UPDATE ... SET activo = 0 ...`
- Listado por defecto: solo activos.
- `activo`/`status` no debe editarse directamente en formularios de alta/edicion CRUD.
- El cambio de estado debe ocurrir mediante procesos explicitos accionados por el usuario
  (ejemplo: boton eliminar/inactivar, activar, aprobar, cancelar), con validacion backend.

4. Unicidad:
- Implementar helper tipo `existeCodigoDuplicado(...)`.
- En edicion excluir el propio registro.

5. Integridad de catalogos:
- Validar que FK seleccionadas pertenezcan a la empresa y esten activas.

## 5) Regla critica de validacion frontend

`js/funciones.js -> validateForm(form)` considera invalido cualquier `select.requerido` con valor `0`.

Implicacion:
- Si un booleano usa valores `0/1`, NO marcar ese `select` como `requerido`.
- Para booleans del tipo "Si/No":
  - usar select sin opcion vacia
  - valores `1` y `0`
  - validar `in_array(0,1)` en backend

Esto evita falsos errores de "campos requeridos".

## 5.1) Bug comun con Select2 en modales (fancybox)

Sintoma reportado:
- Hay filtros `select2` en la pantalla principal.
- Se abre/cierra el modal de agregar/editar.
- Despues de cerrar, los filtros de la pantalla principal dejan de abrir o no muestran opciones.

Causa:
- Inicializar `select2` de forma global dentro del modal:
  - `$(".select2").select2({ dropdownParent: $("#fancyModal") })`
- Eso reconfigura tambien `select2` fuera del modal (incluyendo filtros del padre) con `dropdownParent` incorrecto.

Regla obligatoria:
- En modales, siempre inicializar `select2` scoped al contenedor del modal.
- Patron correcto:

```js
$("#fancyModal .select2").select2({
    dropdownParent: $("#fancyModal")
});
```

Checklist rapido para evitar regresion:
1. Abrir modal de alta/edicion y cerrarlo.
2. Probar que los filtros `select2` del entrypoint siguen abriendo.
3. Verificar que en el modal los `select2` si abren correctamente.

## 6) Plantilla de metodos recomendados en `clase.php`

1. Utilidades:
- `respuestaError($mensaje, $titulo = "Error")`
- `existe<Campo>Duplicado(...)`
- `registroRelacionadoValido(...)` (si aplica)

2. Catalogos para selects/filtros:
- `getTipos...($idempresa)`
- `getUsuarios...($idempresa)` (si aplica)

3. CRUD:
- `get<Entidad>($id, $idempresa)`
- `get<Entidades>($idempresa, ...filtros)`
- `agregar<Entidad>($post, $idempresa)`
- `editar<Entidad>($post, $idempresa)`
- `eliminar<Entidad>($id, $idempresa)` (soft o hard segun regla)

## 7) Estructura visual recomendada

1. Encabezado:
- `h2` con icono + nombre.
- Boton AGREGAR en esquina superior derecha.

2. Filtros (opcionales):
- Encima de la card.
- `select2`, `onchange="recargarLista()"`.

3. Contenedor de lista (`<submodulo>.php`):
- Un `div` vacio con `id` (ejemplo `divLista`) donde se inyecta `lista.php` via `cargarLista()`.
- No agregar aqui la card visual: `lista.php` es responsable de su propio wrapper (ver punto 4).

4. Card dentro de `lista.php`:
- `lista.php` NO debe renderizar la tabla suelta dentro del `div` del entrypoint.
- Debe envolver el resultado (tabla o mensaje de "sin registros") en su propia card:
  ```html
  <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
          <!-- alert "no hay registros" O tabla dentro de .table-responsive -->
      </div>
  </div>
  ```
- Si no hay resultados, mostrar `<div class="alert alert-warning m-2 text-center">...</div>` dentro del mismo `card-body`, no fuera de la card.
- Motivo: el `div` del entrypoint (`divLista`) se recarga completo con cada `cargarLista()`/`recargarLista()`; si la card viviera en el entrypoint, se destruiria y recrearia en cada refresco junto con la tabla, y el fancybox de edicion no tendria una card estable que la contenga visualmente.

5. Tabla:
- `table table-hover mb-0 nowrap dataTable small` envuelta en `div.table-responsive`.
- Inicializar DataTable solo si existe la tabla.
- Columnas de fecha (ejemplo: `created_at`, `fecha`, `fcreado`) deben renderizarse en formato `dd/mm/YYYY`.
- El formateo debe hacerse del lado servidor en `lista.php` para mantener consistencia en todo el sistema.

## 8) Integracion entre CRUDs

Cuando un CRUD alimenta otro catalogo:

1. CRUD de catalogo (ejemplo tipos) debe filtrar por empresa y activo.
2. CRUD consumidor (ejemplo almacenes) debe leer ese catalogo con los mismos filtros.
3. Resultado esperado:
- alta/edicion del catalogo refleja cambios automaticamente en el consumidor.

## 9) Checklist de implementacion (rapido)

1. Agregar `case` en `modulos/<modulo>.php`.
2. Crear entrypoint `modulos/<modulo>/modulos/<submodulo>.php`.
3. Crear carpeta `modulos/<modulo>/modulos/<submodulo>/`.
4. Crear `lista.php`, `agregar.php`, `procesos.php`, `clase.php`.
5. Verificar rutas de `guardar()` y `eliminar()`.
6. Validar scoping por empresa en TODAS las consultas.
7. Validar duplicados y FKs.
8. Confirmar reglas de `activo` (soft delete o no).
9. Probar alta, edicion, borrado, duplicados, filtros.
10. Correr `php -l` en todos los archivos nuevos/modificados.

## 10) Checklist de pruebas funcionales

1. Navegacion:
- `/modulo/submodulo` carga pantalla + listado.

2. Alta:
- Guarda datos validos.
- Bloquea requeridos.
- Bloquea duplicados.

3. Edicion:
- Carga datos previos.
- Guarda cambios validos.

4. Eliminacion:
- Elimina/inactiva segun regla.
- Desaparece del listado si aplica.

5. Seguridad:
- No permite operar registros de otra empresa.

6. Filtros:
- Refrescan listado y aplican correctamente.

7. Integracion:
- Catalogos relacionados reflejan datos activos y de la empresa actual.

---

Usar esta guia como baseline para nuevos CRUDs y solo ajustar reglas de negocio especificas de cada tabla.
