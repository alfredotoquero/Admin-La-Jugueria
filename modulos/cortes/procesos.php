<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/cortes/clase.php");

header("Content-Type: application/json");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$cortes = new Cortes($con);

if (!$cortes->tieneAccesoModulo($idadministrador)) {
	echo json_encode(array(
		"result" => "error",
		"titulo" => "Sin permiso",
		"mensaje" => "No tienes permiso para consultar cortes.",
		"texto" => "No tienes permiso para consultar cortes."
	));
	exit;
}

$proceso = $_POST["proceso"] ?? "";

try {
	switch ($proceso) {
		case "getUsuariosSucursal":
			$idsucursal = (int) ($_POST["idsucursal"] ?? 0);
			$filas = $cortes->getUsuariosPorSucursal($idsucursal, $idadministrador);
			$datos = array();
			foreach ($filas as $fila) {
				$nombre = trim($fila["nombre"] . " " . $fila["apaterno"] . " " . ($fila["amaterno"] ?? ""));
				$datos[] = array("idusuario" => (int) $fila["idusuario"], "nombre" => $nombre);
			}
			$respuesta = array("result" => "success", "data" => $datos);
			break;
		case "verificarCorte":
			$respuesta = $cortes->verificarCorte($_POST);
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
	// con el formato "mensaje|titulo|tipo|icono" y codigo 1; ver clase.php.
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
		file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/txts/excepciones.txt", "cortes/procesos.php ($proceso): " . $e->getMessage() . " -- " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
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
