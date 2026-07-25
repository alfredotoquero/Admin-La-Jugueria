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
	default:
		$respuesta = array(
			"result" => "error",
			"titulo" => "Error",
			"mensaje" => "No se encontro el proceso solicitado.",
			"texto" => "No se encontro el proceso solicitado.",
		);
		break;
}

echo json_encode($respuesta);
?>
