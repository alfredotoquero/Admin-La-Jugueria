/**
 * Impresion directa de tickets en la impresora termica via QZ Tray, igual que
 * el punto de venta (ver admin.php en La-Jugueria). El servidor arma el ticket
 * como bytes ESC/POS (includes/escpos.php) y aqui solo se reenvia a QZ Tray.
 *
 * Requiere /vendor/qz-tray/qz-tray.js cargado antes que este archivo.
 */

/**
 * Firma de las peticiones a QZ Tray. Debe registrarse antes de conectar.
 * rejectOnFailure es indispensable: sin el, si no se obtiene el certificado
 * QZ Tray sigue sin firma y solo deja un aviso en la consola (vuelve a salir
 * el dialogo de permiso en cada impresion, sin ningun error visible).
 */
qz.security.setCertificatePromise(function (resolve, reject) {
	$.ajax({ url: "/controlador/qz-certificado.php", cache: false, dataType: "text" })
		.done(function (certificado) {
			// Con la sesion expirada llega otra respuesta en vez del certificado.
			if (certificado.indexOf("BEGIN CERTIFICATE") === -1) {
				console.error("QZ Tray: la respuesta del certificado no es un certificado");
				reject("No se obtuvo el certificado de firma (la sesion pudo haber expirado).");
				return;
			}
			resolve(certificado);
		})
		.fail(function (xhr) {
			console.error("QZ Tray: fallo al pedir el certificado (HTTP " + xhr.status + ")");
			reject(xhr);
		});
}, { rejectOnFailure: true });

qz.security.setSignatureAlgorithm("SHA512");

qz.security.setSignaturePromise(function (porFirmar) {
	return function (resolve, reject) {
		$.ajax({ url: "/controlador/qz-firma.php", data: { request: porFirmar }, cache: false, dataType: "text" })
			.done(resolve)
			.fail(function (xhr) {
				console.error("QZ Tray: fallo al firmar (HTTP " + xhr.status + ")");
				reject(xhr);
			});
	};
});

/**
 * Manda un ticket ESC/POS (en base64) a la impresora indicada.
 *
 * @param {string} nombreImpresora nombre exacto con el que esta instalada en el equipo
 * @param {string} datosBase64 bytes ESC/POS codificados en base64
 * @returns {Promise<boolean>} true si se envio; los errores ya se notifican aqui
 */
function imprimirTicket(nombreImpresora, datosBase64) {
	var yaAvisado = false;
	var conexion = qz.websocket.isActive() ? Promise.resolve() : qz.websocket.connect();

	return conexion
		.catch(function (error) {
			yaAvisado = true;
			console.error("QZ Tray no disponible:", error);
			Swal.fire({
				icon: "warning",
				title: "QZ Tray no detectado",
				html: "No se detecto QZ Tray en esta computadora (o no esta abierto).<br><br>" +
					"Si ya esta instalado, abrelo e intenta de nuevo. Si no, instala el mismo " +
					"instalador de QZ Tray que usan las cajas de las sucursales (no el de qz.io: " +
					"ese no trae el certificado de La Jugueria y pediria permiso en cada impresion)."
			});
			return Promise.reject(error);
		})
		.then(function () {
			return qz.printers.find(nombreImpresora);
		})
		.then(function (impresora) {
			var config = qz.configs.create(impresora);
			var datos = [{ type: "raw", format: "command", flavor: "base64", data: datosBase64 }];
			return qz.print(config, datos);
		})
		.then(function () {
			return true;
		})
		.catch(function (error) {
			if (!yaAvisado) {
				console.error("Error de impresion QZ Tray:", error);
				Swal.fire({
					icon: "error",
					title: "No se pudo imprimir",
					text: "Verifica que la impresora \"" + nombreImpresora + "\" este instalada en esta computadora con ese mismo nombre."
				});
			}
			return false;
		});
}
