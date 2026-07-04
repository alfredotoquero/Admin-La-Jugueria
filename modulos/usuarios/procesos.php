<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/usuarios/clase.php");

header("Content-Type: application/json");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$usuarios = new Usuarios($con);

if (!$usuarios->tieneAccesoModulo($idadministrador)) {
	echo json_encode(array(
		"result" => "error",
		"titulo" => "Sin permiso",
		"mensaje" => "No tienes permiso para administrar usuarios.",
		"texto" => "No tienes permiso para administrar usuarios."
	));
	exit;
}

$proceso = $_POST["proceso"] ?? "";

switch ($proceso) {
	case "agregarUsuario":
		$respuesta = $usuarios->agregarUsuario($_POST);
		break;
	case "editarUsuario":
		$respuesta = $usuarios->editarUsuario($_POST);
		break;
	case "eliminarUsuario":
		$respuesta = $usuarios->eliminarUsuario($_POST);
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
