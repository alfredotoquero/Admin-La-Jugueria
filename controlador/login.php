<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include($_SERVER["DOCUMENT_ROOT"] . "/config/environment.php");

$correo = mysqli_real_escape_string($con, $_POST['usuario']);
$password = mysqli_real_escape_string($con, $_POST['password']);

$query = "
select
	idadministrador,
	nombre,
	paterno,
	materno,
	correo,
	registro,
	admin,
	status
from
	tadministradores
where
	correo = '".$correo."' and
	password = aes_encrypt('".$password."','".SEED_ADMINISTRADORES."')";
$result = mysqli_query($con,$query);

if (mysqli_num_rows($result) == 1) {
	$_SESSION['infoUsuario'] = mysqli_fetch_assoc($result);

	if ($_SESSION['infoUsuario']['status'] == 1) {
		if ($_SESSION["authToken"] == $_POST["authToken"]) {
			// Login correcto.
			$_SESSION["ultimo_acceso"] = date("Y-n-j H:i:s");
			unset($_SESSION["authToken"]);
			$result = "success";
			$tit = "";
			$msg = "";
		} else {
			// authToken no coincide: posible replay/CSRF. Regenera el token
			// para el siguiente intento (ver includes/seguridad2.php).
			unset($_SESSION['infoUsuario']);
			$result = "error";
			$tit = "";
			$msg = "token";
		}
	} else {
		// Cuenta suspendida/deshabilitada.
		unset($_SESSION['infoUsuario']);
		$result = "error";
		$tit = "No tiene permisos";
		$msg = "Error de status";
	}
} else {
	// Credenciales incorrectas.
	unset($_SESSION['infoUsuario']);
	$result = "error";
	$tit = "Datos de Inicio Incorrectos";
	$msg = "Datos de inicio incorrectos";
}

$array = array("result" => $result, "tit" => $tit, "msg" => $msg);

echo json_encode($array, 128);
?>