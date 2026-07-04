<?php
/**
 * Guardián de expiración de sesión por inactividad.
 *
 * Arquitectura genérica (multi-tenant): se incluye después de
 * includes/session.php en cualquier punto de entrada protegido
 * (home.php, endpoints AJAX, etc.). Si el usuario tiene sesión iniciada
 * pero lleva más de $TIEMPO_MAXIMO_INACTIVIDAD segundos sin actividad,
 * la sesión se destruye y:
 *   - Si la petición es AJAX/JSON, responde 401 con un authToken nuevo
 *     (para regenerar el token anti-CSRF antes del siguiente login).
 *   - Si es una carga normal de página, redirige a "/{codigo_empresa}".
 *
 * Si no hay sesión iniciada, simplemente valida cuánto tiempo pasó desde
 * el último "ultimo_acceso" reportado por el cliente (usado por el
 * front-end para decidir si debe mostrar el modal de "sesión expirada").
 */

// Tiempo máximo de inactividad permitido, en segundos (7200 = 2 horas).
$TIEMPO_MAXIMO_INACTIVIDAD = 7200;

if (isset($_SESSION["infoUsuario"])) {
	// Calculamos el tiempo transcurrido desde el último acceso.
	$fechaGuardada = $_SESSION["ultimo_acceso"];
	$ahora = date("Y-n-j H:i:s");
	$tiempo_transcurrido = (strtotime($ahora) - strtotime($fechaGuardada));

	if ($tiempo_transcurrido >= $TIEMPO_MAXIMO_INACTIVIDAD) {
		$idusuario = $_SESSION["infoUsuario"]["idusuario"];
		unset($_SESSION["infoUsuario"]);
		unset($_SESSION["ultimo_acceso"]);
		session_destroy();

		// ¿Es una petición AJAX/JSON o una carga normal de página?
		if (
			(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") ||
			(isset($_SERVER["HTTP_ACCEPT"]) && (strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false || strpos($_SERVER["HTTP_ACCEPT"], "application/x-www-form-urlencoded") !== false || strpos($_SERVER["HTTP_ACCEPT"], "multipart/form-data") !== false || strpos($_SERVER["HTTP_ACCEPT"], "text/javascript") !== false)) ||
			(isset($_SERVER["CONTENT_TYPE"]) && (in_array($_SERVER["CONTENT_TYPE"], ["application/json", "application/x-www-form-urlencoded"]) || strpos($_SERVER["CONTENT_TYPE"], "multipart/form-data") !== false))
		) {
			header("Content-Type: application/json", true, 401);
			session_start();
			$_SESSION["authToken"] = sha1(uniqid(microtime(), true));
			echo json_encode(["name" => "SessionExpiredError", "mensaje" => "Sesión expirada. Haz Clic aquí para reiniciar tu sesión o ignora para regresar a la pantalla de inicio.", "authToken" => $_SESSION["authToken"]], JSON_FORCE_OBJECT);
			exit;
		} else {
			header("location: /");
			exit;
		}
	} else {
		// Sigue activo: si la carga es de página normal (no AJAX), refrescamos
		// la marca de "último acceso".
		if (
			!((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") ||
			(isset($_SERVER["HTTP_ACCEPT"]) && (strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false || strpos($_SERVER["HTTP_ACCEPT"], "application/x-www-form-urlencoded") !== false || strpos($_SERVER["HTTP_ACCEPT"], "multipart/form-data") !== false || strpos($_SERVER["HTTP_ACCEPT"], "text/javascript") !== false)) ||
			(isset($_SERVER["CONTENT_TYPE"]) && (in_array($_SERVER["CONTENT_TYPE"], ["application/json", "application/x-www-form-urlencoded"]) || strpos($_SERVER["CONTENT_TYPE"], "multipart/form-data") !== false)))
		) {
			$_SESSION["ultimo_acceso"] = $ahora;
		}
	}
} else {
	// No hay sesión iniciada: esto normalmente lo llama el front-end vía AJAX
	// para verificar si su "ultimo_acceso" local ya venció, antes de intentar
	// una acción que requiera sesión.
	session_destroy();

	$ultimo_acceso = $_POST["ultimo_acceso"] ?? null;
	$ahora = date("Y-n-j H:i:s");
	$tiempo_transcurrido = (!empty($ultimo_acceso)) ? (strtotime($ahora) - strtotime($ultimo_acceso)) : null;

	if (
		(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") ||
		(isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false) ||
		(isset($_SERVER["CONTENT_TYPE"]) && (in_array($_SERVER["CONTENT_TYPE"], ["application/json", "application/x-www-form-urlencoded"]) || strpos($_SERVER["CONTENT_TYPE"], "multipart/form-data") !== false))
	) {
		header("Content-Type: application/json", true, 401);
		session_start();
		$direccion = explode("/", $_SERVER["HTTP_REFERER"] ?? "", 5)[4] ?? "";
		$direccion = ((empty($direccion)) ? (explode("/", $_SERVER["REQUEST_URI"], 3)[2] ?? "") : $direccion);
		if (!empty($direccion)) {
			$_SESSION["url_redirigir"] = $direccion;
		}
		$_SESSION["authToken"] = sha1(uniqid(microtime(), true));
		echo json_encode(["name" => "SessionExpiredError", "mensaje" => "Sesión expirada. Haz Clic aquí para reiniciar tu sesión o ignora para regresar a la pantalla de inicio.", "authToken" => $_SESSION["authToken"]], JSON_FORCE_OBJECT);
		exit;
	} else {
		session_start();
		$direccion = explode("/", $_SERVER["REQUEST_URI"], 3)[2] ?? "";
		if (!empty($direccion)) {
			$_SESSION["url_redirigir"] = $direccion;
		}
		header("location: /");
		exit;
	}
}
