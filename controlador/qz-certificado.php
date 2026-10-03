<?php
/**
 * Entrega el certificado publico con el que QZ Tray verifica las firmas que genera
 * qz-firma.php. Es el mismo par de llaves del punto de venta: el instalador de QZ
 * Tray de las sucursales ya trae este certificado como de confianza, asi que el panel
 * imprime sin pedir permiso en cada ticket igual que las cajas.
 *
 * El certificado NO es secreto, pero se pide sesion para no exponerlo sin necesidad.
 */
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");

define("QZ_ACCESO_INTERNO", true);
include($_SERVER["DOCUMENT_ROOT"] . "/config/qz-llaves.php");

header("Content-Type: text/plain");
header("Cache-Control: no-store, no-cache, must-revalidate");

echo $QZ_CERTIFICADO;
?>
