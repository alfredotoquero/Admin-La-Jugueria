<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/sucursales/clase.php");

header("Content-Type: application/json");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$sucursales = new Sucursales($con);

if (!$sucursales->tieneAccesoModulo($idadministrador)) {
	echo json_encode(array(
		"result" => "error",
		"titulo" => "Sin permiso",
		"mensaje" => "No tienes permiso para administrar sucursales.",
		"texto" => "No tienes permiso para administrar sucursales."
	));
	exit;
}

$proceso = $_POST["proceso"] ?? "";

switch ($proceso) {
	case "agregarSucursal":
		$respuesta = $sucursales->agregarSucursal($_POST);
		break;
	case "editarSucursal":
		$respuesta = $sucursales->editarSucursal($_POST);
		break;
	case "eliminarSucursal":
		$respuesta = $sucursales->eliminarSucursal($_POST);
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
