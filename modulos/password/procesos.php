<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/usuarios/clase.php");

header("Content-Type: application/json");

// Sin validacion de modulo: cualquier usuario con sesion puede cambiar su
// propia contrasena (la clase siempre usa el id de la sesion).
$usuarios = new Usuarios($con);

$proceso = $_POST["proceso"] ?? "";

try {
	switch ($proceso) {
		case "cambiarPassword":
			$respuesta = $usuarios->cambiarPasswordPropio($_POST);
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
	// Excepciones de negocio (validaciones) se lanzan con el formato
	// "mensaje|titulo|tipo|icono" y codigo 1; ver modulos/usuarios/clase.php.
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
		file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/txts/excepciones.txt", "password/procesos.php ($proceso): " . $e->getMessage() . " -- " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
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
?>
