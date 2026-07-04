<?php
// Incluir la función renderTable
include_once 'componentes/renderTabla.php';

// Obtener y decodificar el JSON recibido
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$idusuario = $data['idusuario'] ?? null;

// Verificar que tengamos todos los parámetros
if (isset($data['tableId'], $data['data']['headers'], $data['data']['rows'])) {
    echo renderTable($data['tableId'], $data['data'], $idusuario);
} else {
    echo "Error: Datos incompletos.";
}
?>
