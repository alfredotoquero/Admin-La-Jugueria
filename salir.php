<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
$codigo = $_SESSION['infoUsuario']['codigo'] ?? '';
unset($_SESSION['infoUsuario']);
unset($_SESSION["ultimo_acceso"]);
session_destroy();
header("location:/" . $codigo);
