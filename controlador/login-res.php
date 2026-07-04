<?php
/**
 * NOTA DE MIGRACIÓN:
 * En el proyecto original (intranet-xensei), controlador/login-res.php NO
 * contenía el flujo de login multi-tenant (ese vivía, un poco al revés de
 * lo que sugiere el nombre, en controlador/login.php). El contenido real
 * de login-res.php era lógica muerta y 100% específica de un negocio de
 * grabado/impresión de equipos ("engravers") que no se usa en ninguna
 * parte del front-end actual (no hay ninguna referencia a este archivo en
 * el JS del proyecto). Por eso no se copió tal cual.
 *
 * En su lugar, este archivo se dejó como un endpoint genérico de utilidad:
 * responde si la sesión actual sigue siendo válida. Es útil para que el
 * front-end (polling, o antes de intentar una acción sensible) confirme
 * el estado de sesión sin depender de que la petición real regrese 401.
 *
 * Si no lo necesitas, elimínalo sin problema: no es parte del flujo de
 * login (ese es controlador/login.php) ni del dispatcher AJAX
 * (controlador/procesos.php).
 */
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");

$autenticado = isset($_SESSION["infoUsuario"]["idusuario"]);

http_response_code($autenticado ? 200 : 401);
header("Content-Type: application/json");
echo json_encode([
	"result" => $autenticado ? "success" : "error",
	"autenticado" => $autenticado,
	"idusuario" => $_SESSION["infoUsuario"]["idusuario"] ?? null,
	"codigo" => $_SESSION["infoUsuario"]["codigo"] ?? null,
], 128);
