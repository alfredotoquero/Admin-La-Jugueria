# 🏴‍☠️ Bienvenido a la Isla del Código Reutilizable ⚓  

Has navegado por mares de código repetitivo y por fin encontraste **tierra firme**.  
Aquí guardamos los **Traits y Helpers** que evitarán que vuelvas a escribir la misma función una y otra vez.  

---

## 🛠️ **Traits: El Código de la Hermandad Pirata**  
Los **Traits** permiten compartir lógica entre clases sin duplicaciones innecesarias.  

📜 **Disponible:**  
- **TraitDocumentosVencer.php** 🏆  
  - Calcula la vigencia de documentos sin importar si son empleados, proveedores, clientes o vehículos.  

🏴 **Uso:**  
```php
require_once __DIR__ . '/../traits/TraitDocumentosVencer.php';
class Clase { use TraitDocumentosVencer; }
```





🛠️ **Helpers: Herramientas del Buen Navegante**
Si los Traits son el código de los corsarios, los Helpers son las herramientas esenciales para no naufragar en código repetitivo.

🛠 **Lista de Helpers Disponibles**
📜 documentosHelper.php → Genera badges dinámicos que muestran cuántos documentos han sido subidos y su porcentaje de completado.

🏴 ¿Cómo usarlo en cubierta?
```php
require_once __DIR__ . '/../helpers/documentosHelper.php';
echo renderDocumentosBadge($cantidadDocumentos, $documentosTotales, 'table');
```

🏗️ **Reglas de la Tripulación**
Si copias y pegas el mismo código más veces que un mensaje en botella... detente.

🏴 **¿Dónde debe ir tu código?**
Si se comparte entre varias clases → Hazlo un Trait en /traits/.
Si es una función independiente → Agrégala a un Helper en /helpers/.


**Navega con inteligencia, usa estos recursos, y que el código siempre esté de tu lado. 🏴‍☠️**