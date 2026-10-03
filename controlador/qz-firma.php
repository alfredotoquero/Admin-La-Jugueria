<?php
/**
 * Firma digital de las peticiones que el navegador manda a QZ Tray.
 *
 * QZ Tray solo deja recordar el permiso ("Remember this decision") cuando la
 * peticion viene firmada con un certificado de confianza; sin firma pide
 * autorizacion en CADA impresion. Usa la misma llave que el punto de venta
 * (config/qz-llaves.php, ignorado en git: se copia a mano al servidor).
 *
 * La llave privada nunca sale del servidor: solo viaja la firma.
 */

// Exige sesion iniciada; sin esto cualquiera en internet podria pedir firmas y
// mandar impresiones a las computadoras que tienen QZ Tray.
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");

define("QZ_ACCESO_INTERNO", true);
include($_SERVER["DOCUMENT_ROOT"] . "/config/qz-llaves.php");

header("Content-Type: text/plain");
// La firma depende del texto (que lleva marca de tiempo), no debe cachearse nunca.
header("Cache-Control: no-store, no-cache, must-revalidate");

$porFirmar = $_GET["request"] ?? "";

$llave = openssl_pkey_get_private($QZ_LLAVE_PRIVADA);
if ($llave === false) {
	header("HTTP/1.0 500 Internal Server Error");
	exit;
}

$firma = "";
// SHA512 es lo que espera QZ Tray desde la version 2.1.
if (openssl_sign($porFirmar, $firma, $llave, "sha512")) {
	echo base64_encode($firma);
} else {
	header("HTTP/1.0 500 Internal Server Error");
}
?>
