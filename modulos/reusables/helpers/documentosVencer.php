<?php
function renderDocumentosBadge($cantidadDocumentos,  $cantidadVencidos, $documentosTotales, $tipoVista = 'table', $forzarGris = false)
{
    // Asegurar que los valores sean numéricos para evitar errores con NULL
    $cantidadDocumentos = is_numeric($cantidadDocumentos) ? (int)$cantidadDocumentos : 0;
    $cantidadVencidos = is_numeric($cantidadVencidos) ? (int)$cantidadVencidos : 0;
    $documentosTotales = is_numeric($documentosTotales) ? (int)$documentosTotales : 0;

    $totalDocumentosMostrados = $cantidadDocumentos + $cantidadVencidos;
    // Determinar el estilo basado en la cantidad de documentos
    if ($forzarGris && $documentosTotales == 0) {
        $style = "secondary"; // Caso especial opcional en gris
        $texto = "N/A";
    } elseif ($documentosTotales == 0) {
        $style = "info"; // Azul si no hay documentos requeridos
        $texto = "N/A";
    } elseif ($cantidadDocumentos == 0 && $documentosTotales > 0) {
        $style = "danger"; // Rojo si faltan todos los documentos
        $texto = "$cantidadVencidos / $documentosTotales";
    } elseif ($cantidadDocumentos < $documentosTotales) {
        $style = "warning"; // Amarillo si no se han subido todos
        $texto = "$totalDocumentosMostrados / $documentosTotales";
    } else {
        $style = "success"; // Verde si están todos los documentos
        $texto = "$totalDocumentosMostrados / $documentosTotales";
    }

    // Generar HTML según el tipo de vista
    if ($tipoVista === 'table') {
        return "<td class='align-middle text-center'><span class='badge badge-$style w-125'>$texto</span></td>";
    } elseif ($tipoVista === 'div') {
        return "<div id='totalDolares'><span class='h5 mb-0 font-weight-bold text-white-800 badge badge-$style w-125'>$texto</span></div>";
    }

    return ""; // En caso de vista inválida
}

/**
 * Renderiza una tabla HTML de forma genérica.
 *
 * @param string $tableId El atributo id de la tabla.
 * @param array $columns Arreglo de columnas. Cada elemento es un arreglo con:
 *                       - 'header': texto de la cabecera.
 *                       - 'attributes' (opcional): atributos HTML (p.ej. clase, style).
 * @param array $rows Arreglo de filas. Cada fila es un arreglo de celdas (ya pueden tener contenido HTML).
 * @param array $options Opciones adicionales (p.ej. tableClass, tableStyle).
 */
function renderDocumentosVencerTable($tableId, $columns, $rows, $options = []) {
    $tableClass = isset($options['tableClass']) ? $options['tableClass'] : 'table table-hover mb-0 nowrap';
    $tableStyle = isset($options['tableStyle']) ? $options['tableStyle'] : 'width:100%';

    echo "<table class='$tableClass small' style='$tableStyle' id='$tableId'>";
    
    // Renderizar encabezado
    echo "<thead><tr>";
    foreach ($columns as $col) {
        $colHeader = isset($col['header']) ? $col['header'] : '';
        $colAttr   = isset($col['attributes']) ? $col['attributes'] : '';
        echo "<th $colAttr>$colHeader</th>";
    }
    echo "</tr></thead>";

    // Renderizar cuerpo
    echo "<tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>$cell</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}

function renderDocumentosVencerTable2($tableId, $columns, $rows, $options = []) {
    $tableClass = isset($options['tableClass']) ? $options['tableClass'] : 'table table-hover mb-0 nowrap';
    $tableStyle = isset($options['tableStyle']) ? $options['tableStyle'] : 'width:100%';

    echo "<table class='$tableClass small' style='$tableStyle' id='$tableId'>";
    
    // Encabezado
    echo "<thead><tr>";
    foreach ($columns as $col) {
        $colHeader = isset($col['header']) ? $col['header'] : '';
        $colAttr   = isset($col['attributes']) ? $col['attributes'] : '';
        echo "<th $colAttr>$colHeader</th>";
    }
    echo "</tr></thead>";
    
    // Cuerpo
    echo "<tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($columns as $col) {
            $key = isset($col['key']) ? $col['key'] : '';
            $cell = isset($row[$key]) ? $row[$key] : '';
            
            // Si hay callback personalizado se utiliza, sino se aplica la lógica por defecto
            if (isset($col['render']) && is_callable($col['render'])) {
                $cell = call_user_func($col['render'], $cell, $row);
            } else {
                // Lógica por defecto para la columna "vencido"
                if ($key === 'vencido') {
                    if ($cell == 'VENCIDO') {
                        $cell = '<span class="badge badge-danger">' . $cell . '</span>';
                    } elseif ($cell == 'PRÓXIMO A VENCER') {
                        $cell = '<span class="badge badge-warning">' . $cell . '</span>';
                    } elseif ($cell == 'SIN ASIGNAR') {
                        $cell = '<span class="badge badge-secondary">' . $cell . '</span>';
                    } else {
                        $cell = '<span class="badge badge-success">' . $cell . '</span>';
                    }
                }
                // Lógica por defecto para la columna "vigencia"
                if ($key === 'vigencia') {
                    $timestamp = strtotime($cell);
                    $data_order = "";
                    if (!$timestamp || $cell == '0000-00-00' || empty($cell)) {
                        $cell = '';
                    } else {
                        $cell = date("d/m/Y", $timestamp);
                        $data_order = date("Y-m-d", $timestamp);
                    }
                }
            }
            echo "<td " . ((!empty($data_order)) ? "data-order='" . $data_order . "'" : "") . ">$cell</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    
    echo "</table>";
}

/**
 * Imprime el bloque de JavaScript para inicializar DataTables.
 *
 * @param string $tableId El ID de la tabla a inicializar.
 * @param array $options Opciones personalizadas para DataTables (se combinarán con las opciones por defecto).
 */
function initDataDocumentosVencerTable($tableId, $options = []) {
    // Opciones por defecto para DataTables
    $defaultOptions = [
        "responsive" => false,
        "language" => [
            "sProcessing"   => "Procesando...",
            "sLengthMenu"   => "Mostrar _MENU_ registros",
            "sZeroRecords"  => "No se encontraron resultados",
            "sEmptyTable"   => "Ningún dato disponible en esta tabla",
            "sInfo"         => "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty"    => "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered" => "(filtrado de un total de _MAX_ registros)",
            "sSearch"       => "Buscar:",
            "sLoadingRecords" => "Cargando...",
            "oPaginate"     => [
                "sFirst"    => "Primero",
                "sLast"     => "Último",
                "sNext"     => "Siguiente",
                "sPrevious" => "Anterior"
            ],
            "oAria"         => [
                "sSortAscending"  => ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending" => ": Activar para ordenar la columna de manera descendente"
            ],
            "buttons"       => [
                "copy"      => "Copiar",
                "colvis"    => "Visibilidad"
            ]
        ]
    ];

    // Combinar las opciones por defecto con las opciones personalizadas
    $mergedOptions = array_replace_recursive($defaultOptions, $options);
    $jsonOptions = json_encode($mergedOptions);
    
    // Imprimir el bloque de inicialización de DataTables
    echo "<script>
    $(document).ready(function() {
        $('#" . $tableId . "').DataTable(" . $jsonOptions . ");
    });
    </script>";
}
?>