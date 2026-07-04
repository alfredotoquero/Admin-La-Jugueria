# Guia de Estilos para Fancys (Fancybox)

Este documento estandariza el estilo visual y la estructura tecnica de los fancys del sistema.

Objetivo:
- Tener una base visual consistente, limpia y moderna.
- Evitar el efecto "card dentro de card".
- Reutilizar el estilo del fancy informativo de referencia ya validado en el sistema.
- Definir una base clara para variante informativa y variante formulario.

Referencia base (ejemplo generico):
- `modulos/moduloEjemplo/modulos/submoduloEjemplo/detalle.php`

---

## 1) Principios visuales obligatorios

1. El contenedor raiz del fancy es el panel final.
- El `div` raiz debe tener border radius, sombra y fondo.
- No crear una segunda tarjeta envolvente dentro del contenedor.

2. Encabezado con identidad de empresa.
- Usar degradado con `color1` y `color2` de empresa.
- Si no hay colores validos, usar fallback seguro.

3. Contenido con superficie clara.
- Fondo suave (`gris muy claro`) para separar del encabezado.
- Tarjetas y tabla con bordes sutiles y tipografia legible.

3.1 Bordes redondeados completos (obligatorio).
- El fancy debe mostrar redondeo visible en los 4 bordes (superior izq/der e inferior izq/der).
- El header debe respetar radios superiores del panel.
- Si Fancybox recorta mal el radio superior, ajustar el contenedor scoped con `overflow: hidden`.

4. Boton de cierre visible.
- Debe tener contraste suficiente contra el encabezado.
- Ajustes de posicion y color deben aplicarse de forma scoped a ese fancy.

5. Sin impacto global.
- Cualquier override de Fancybox debe ir limitado al modal actual.
- Nunca usar selectores globales que afecten otros fancys.

6. Homogeneidad estricta entre fancys.
- No introducir variaciones visuales "creativas" por fancy si no estan definidas en esta guia.
- Si un fancy nuevo rompe la logica visual comun, debe corregirse para mantener consistencia del sistema.

---

## 2) Estructura tecnica base (comun)

1. Includes minimos:
- `session.php`
- `clase.php` del submodulo

2. Helper de salida:
- Usar `formatearLabel($value)` con `htmlspecialchars`.

3. Root container:
- ID unico por fancy, por ejemplo `#fancyDetalle...`.
- El root define ancho y estilo principal.

4. CSS inline del fancy:
- Las clases `fancy-x-*` ya estan definidas globalmente en `css/style.php`. No copiarlas en el fancy.
- El unico `<style>` inline necesario por fancy es el bloque que inyecta los colores de empresa via PHP en el ID del fancy (variables `--fancy-color-1` y `--fancy-color-2`).
- En caso de requerir una clase nueva, puedes crearla inline en el archivo, pero proriza estilos globales reutilizables

5. JS scoped:
- En `$(document).ready`, ubicar el `.fancybox-content` padre y ajustarlo.
- Agregar clase de tema al `.fancybox-container` solo para este fancy.

---

## 3) Variante A: Fancy Informativo (estandar actual)

Usar cuando:
- El modal solo muestra informacion.
- No requiere guardado directo de formulario.

### 3.1 Layout recomendado

1. Bloques:
- `encabezado-...` (gradiente, titulo, subtitulo, badge de estado global).
- `contenido-...` (resumen y tabla/detalle).

2. Superficies:
- Tarjetas de resumen con borde suave.
- Contenedor de tabla con radio y fondo blanco.

3. Tabla:
- `table table-hover table-sm small`
- Encabezados uppercase con tracking ligero.

4. Regla de informacion por tarjetas (obligatoria):
- La seccion de "informacion de entidad" debe mostrarse en tarjetas individuales por dato.
- No usar una tarjeta unica grande con todos los campos agregados dentro.
- Ejemplo de datos por tarjeta: folio, fecha, status, prioridad, solicitante, centro de costos, etc.

5. Regla de header (obligatoria):
- El header debe ser visualmente equivalente entre fancys informativos.
- No usar fondo adicional en el icono del titulo.
- Evitar subir datos de negocio al header si en otros fancys van en tarjetas de resumen.
- El folio/numero de documento debe ir en tarjetas de informacion, no como chip flotante en header.

### 3.2 Encabezado dinamico por empresa

Regla:
- Las variables `--fancy-color-1` y `--fancy-color-2` ya estan disponibles globalmente, inyectadas por el layout a partir de la sesion del usuario.
- Las clases `.fancy-x-header`, `.fancy-x-body`, etc. ya las consumen desde `css/style.php`.
- **No agregar** `normalizarColorHex`, variables `$color1`/`$color2` ni ningun `<style>` de colores dentro del archivo fancy.
- El gradiente del header funciona automaticamente al aplicar la clase `fancy-x-header`.

### 3.3 Ajuste de contenedor Fancybox

Para evitar borde blanco externo, usar la funcion global `inicializarFancyX()` de `/js/funciones.js`:

```js
$(document).ready(function() {
    inicializarFancyX("#fancyX");
});
```

Esto ajusta `.fancybox-content` (padding, background, overflow, border-radius) e inicializa Select2.

Nota:
- Si necesitas mostrar elementos que sobresalen del contenido, evaluar `overflow: visible`.
- Si se pierde el redondeo superior, priorizar `overflow: hidden`.

### 3.4 Boton de cierre

El boton de cierre se estiliza automaticamente via CSS en `css/style.php` con el selector:

```css
.fancybox-container:has(.fancy-x-root) .fancybox-close-small {
    color: #ffffff !important;
    opacity: 1 !important;
}
```

Fancybox appende `.fancybox-close-small` como hermano siguiente de `.fancy-x-root`
dentro de `.fancybox-content`. El combinador `~` lo detecta sin necesidad de
agregar clases tema via JS.

Regla:
- No agregar clases tema al `.fancybox-container` para el close button.
- Nunca sobrescribir cierre global del sistema.

---

## 4) Variante B: Fancy Formulario (base recomendada)

Usar cuando:
- El modal captura datos y ejecuta `guardar(...)`.

Esta variante todavia no esta consolidada en un solo ejemplo visual definitivo,
pero debe heredar la misma base del informativo con estos ajustes:

1. Header y contenedor:
- Mismo root panel, mismo gradiente dinamico por empresa.

2. Cuerpo:
- Formulario por secciones (`form-row`, labels, requireds).
- Inputs con espaciado uniforme y lectura clara.

3. Select2:
- Siempre scoped al contenedor del fancy:

```js
$("#fancyModalFormulario .select2").select2({
  dropdownParent: $("#fancyModalFormulario")
});
```

4. Botones:
- Alineados a la derecha.
- Negativo izquierda, positivo derecha.
- Boton de accion principal con `id="btnAccion"`.

5. Guardado:
- `onclick="guardar('formX','modulo/modulos/submodulo')"`
- Usar `validateForm` con `.requerido`.

6. Reglas de validacion:
- `select.requerido` con opcion default en `0`.
- Montos con maximo 2 decimales en UI y backend.

---

## 5) Ancho responsivo (obligatorio por fancy)

Cada fancy debe incluir un bloque `<style>` scoped a su ID para controlar el ancho en desktop y mobile.

Reglas:
- Usar siempre porcentajes, nunca pixeles fijos.
- El ancho desktop se define segun el contenido del fancy (formulario simple: 35-50%, detalle amplio: 70-80%).
- En `max-width: 740px` el ancho SIEMPRE debe ser `90%`.

Patron requerido (usar el ID unico del fancy):

```css
<style>
    #fancyNombreX {
        width: 50%; /* ajustar segun contenido */
    }

    @media only screen and (max-width: 740px) {
        #fancyNombreX {
            width: 90%;
        }
    }
</style>
```

Referencia de anchos por tipo de fancy:
- Fancy formulario simple (pocos campos): `35%`
- Fancy formulario medio (muchos campos): `50%`
- Fancy informativo / detalle con tabla: `70%`

Nota: este bloque `<style>` es la excepcion permitida al principio de no agregar CSS en fancys —
unicamente para el control de ancho responsivo scoped al ID del fancy.

---

## 6) Checklist rapido de implementacion

1. Definir ID unico del root del fancy.
2. Aplicar clase `fancy-x-root` al root (las clases estan en `css/style.php`, no copiar).
3. Llamar `inicializarFancyX("#fancyX")` en `$(document).ready` — ajusta el contenedor Fancybox automaticamente.
4. Agregar bloque `<style>` de ancho responsivo scoped al ID (ver seccion 5).
5. Verificar desktop y mobile.
6. Verificar que no haya side effects en otros fancys.

---

## 7) Checklist QA visual

1. No hay "marco blanco" entre contenido y contenedor fancy.
2. Header se ve consistente con colores de empresa.
3. Boton cerrar tiene contraste y posicion correcta.
4. Bordes y radios se ven continuos entre header y body.
5. Los 4 bordes externos del fancy se perciben redondeados (no solo los inferiores).
6. Tabla/tarjetas mantienen legibilidad.
7. En mobile no hay cortes ni desbordes.
8. El header no tiene variaciones no estandar (icono con fondo, escalas de titulo fuera de patron, chips de folio en header).
9. La informacion principal esta segmentada en tarjetas separadas (no en una tarjeta unificada gigante).

---

## 8) Antipatrones a evitar

1. Crear `card` adicional envolviendo el root del modal.
2. Usar colores hardcodeados cuando el tema debe ser por empresa.
3. Usar CSS global para `.fancybox-content` o `.fancybox-button`.
4. Inicializar select2 sin `dropdownParent` dentro del fancy.
5. Mezclar logica de backend en decisiones puramente visuales del modal.
6. Meter datos de resumen (folio/status) en header cuando el patron definido los ubica en tarjetas de informacion.
7. Usar una sola tarjeta para toda la seccion informativa en fancys tipo detalle.
8. Agregar la funcion `normalizarColorHex`, las variables `$color1`/`$color2` o un `<style>` con `--fancy-color-1`/`--fancy-color-2` dentro del archivo fancy. Los colores ya son globales.

---

## 9) Definicion de terminado (DoD)

Un fancy se considera alineado a esta guia cuando:

1. Usa root panel sin doble tarjeta.
2. Respeta tema visual y jerarquia del contenido.
3. Tiene close button visible y accesible.
4. No rompe otros fancys del sistema.
5. Pasa validacion visual en desktop y mobile.

---

## 10) Catalogo de clases CSS reutilizables (referencia directa)

Esta seccion define un set base de clases para implementar fancys sin depender de
"basate en X archivo".

### 9.1 Referencia de clases disponibles en `css/style.php`

> Estas clases ya estan definidas globalmente. No copiarlas en cada fancy.
> No agregar variables de color ni funcion normalizarColorHex en el fancy. Los colores son globales (ver seccion 3.2).

```css
/* Root del fancy */
.fancy-x-root {
    --fancy-color-1: #103B74;
    --fancy-color-2: #3884D0;
    width: 94%;
    max-width: 1280px;
    border-radius: 14px;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 12px 28px -16px rgba(23, 43, 77, 0.45);
    padding: 0 !important;
}

@media only screen and (max-width: 700px) {
    .fancy-x-root {
        width: 96%;
    }
}

/* Header */
.fancy-x-header {
    padding: 16px 18px;
    background: linear-gradient(
        130deg,
        var(--fancy-color-1) 0%,
        var(--fancy-color-2) 100%
    );
    color: #ffffff;
    border-radius: 14px 14px 0 0;
}

.fancy-x-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.2px;
}

.fancy-x-subtitle {
    margin-top: 4px;
    opacity: 0.94;
    font-size: 13px;
    font-weight: 500;
}

.fancy-x-header-badge {
    font-size: 12px;
    letter-spacing: 0.3px;
    padding: 6px 12px;
}

/* Body */
.fancy-x-body {
    padding: 16px;
    background: #f4f7fb;
    border-radius: 0 0 14px 14px;
}

@media only screen and (max-width: 991px) {
    .fancy-x-body {
        padding: 12px;
    }
}

/* Tarjetas de resumen */
.fancy-x-summary-card {
    border: 1px solid #dde5f0;
    border-radius: 10px;
    background: #ffffff;
    padding: 10px 12px;
    min-height: 78px;
}

.fancy-x-summary-label {
    color: #5f6b7a;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.45px;
    margin-bottom: 4px;
}

.fancy-x-summary-value {
    color: #1c2a3a;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.25;
    word-break: break-word;
}

/* Tabla */
.fancy-x-table-wrap {
    border: 1px solid #d8e1ed;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
}

.fancy-x-table {
    margin-bottom: 0;
}

.fancy-x-table thead th {
    background: #eef3f9;
    color: #3f4f63;
    border-bottom: 1px solid #d9e2ef;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

.fancy-x-table tbody td {
    border-top: 1px solid #edf1f7;
    color: #233245;
    vertical-align: middle;
}

.fancy-x-table tbody tr:hover {
    background: #f8fbff;
}

/* Badge ID de renglon */
.fancy-x-row-id {
    display: inline-block;
    min-width: 44px;
    text-align: center;
    padding: 4px 8px;
    border-radius: 6px;
    background: #edf3ff;
    color: #305696;
    font-weight: 700;
    font-size: 12px;
}

/* Close button: blanco automatico cuando el fancy usa fancy-x-root */
.fancybox-container:has(.fancy-x-root) .fancybox-close-small {
    color: #ffffff !important;
    opacity: 1 !important;
}
```

### 10.2 Ejemplo de uso (fancy informativo)

```php
<div id="fancyDetalleX" class="fancy-x-root">
    <div class="fancy-x-header">
        <div class="row align-items-center">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
                <h4 class="fancy-x-title">Detalle</h4>
                <div class="fancy-x-subtitle">Resumen de informacion.</div>
            </div>
            <div class="col-12 col-md-4 text-md-right">
                <span class="badge badge-light fancy-x-header-badge">ABIERTA</span>
            </div>
        </div>
    </div>

    <div class="fancy-x-body">
        <div class="row mb-2">
            <div class="col-12 col-md-6 col-lg-3 mb-2">
                <div class="fancy-x-summary-card">
                    <div class="fancy-x-summary-label">Folio</div>
                    <div class="fancy-x-summary-value">123</div>
                </div>
            </div>
        </div>

        <!-- Nota: cada dato de informacion debe ir en su tarjeta.
             Evitar una tarjeta grande que agrupe todos los campos. -->

        <div class="fancy-x-table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-sm small fancy-x-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Producto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="fancy-x-row-id">5</span></td>
                            <td>Refaccion</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

### 10.3 Ejemplo de uso (fancy formulario)

```php
<div id="fancyFormularioX" class="fancy-x-root">
    <div class="fancy-x-header">
        <h4 class="fancy-x-title">Agregar registro</h4>
        <div class="fancy-x-subtitle">Captura los campos requeridos.</div>
    </div>

    <div class="fancy-x-body">
        <form id="formX" name="formX">
            <input type="hidden" name="proceso" value="agregarX">

            <div class="form-row">
                <div class="form-group col-12 col-xl-6">
                    <label for="nombre">Nombre <strong class="danger">*</strong></label>
                    <input type="text" id="nombre" name="nombre" class="form-control requerido">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group d-xl-flex col-md-12 mb-0 justify-content-end">
                    <button
                        type="button"
                        class="btn btn-danger btn-sm shadow-sm col-12 col-xl-auto mr-xl-1 mb-1 mb-xl-0"
                        data-fancybox-close
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm shadow-sm col-12 col-xl-auto mt-xl-0"
                        id="btnAccion"
                        onclick="guardar('formX','modulo/modulos/submodulo')"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
```

### 10.4 JS reusable (contenedor Fancybox + tema scoped)

Usar la funcion global `inicializarFancyX()` de `/js/funciones.js`:

```js
$(document).ready(function() {
    inicializarFancyX("#fancyDetalleX");
});
```

Esta funcion ajusta `.fancybox-content` (padding 0, background transparente,
overflow hidden, border-radius 14px) e inicializa Select2 con `dropdownParent`.

El boton de cierre ya se maneja automaticamente via CSS (ver seccion 3.4).

---

## 11) Mapa rapido: clase -> uso

1. `.fancy-x-root`
- Contenedor raiz del modal.

2. `.fancy-x-header`
- Encabezado con gradiente y bloque de titulo.

3. `.fancy-x-body`
- Contenedor principal de contenido.

4. `.fancy-x-summary-*`
- Tarjetas de resumen en fancys informativos.

5. `.fancy-x-table*`
- Tabla y envolvente visual de detalle/listado.

6. `inicializarFancyX(selector)`
- Funcion global en `/js/funciones.js`. Ajusta contenedor Fancybox e inicializa Select2.
- El close button blanco se aplica automaticamente via CSS (`.fancybox-container:has(.fancy-x-root) .fancybox-close-small`).
