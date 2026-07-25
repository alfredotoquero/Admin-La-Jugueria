<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/productos/clase.php");

header("Content-Type: application/json");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$productos = new Productos($con);

if (!$productos->tieneAccesoModulo($idadministrador)) {
	echo json_encode(array(
		"result" => "error",
		"titulo" => "Sin permiso",
		"mensaje" => "No tienes permiso para administrar productos.",
		"texto" => "No tienes permiso para administrar productos."
	));
	exit;
}

$proceso = $_POST["proceso"] ?? "";

switch ($proceso) {
	case "agregarProducto":
		$respuesta = $productos->agregarProducto($_POST);
		break;
	case "editarProducto":
		$respuesta = $productos->editarProducto($_POST);
		break;
	case "eliminarProducto":
		$respuesta = $productos->eliminarProducto($_POST);
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
