<?php
/**
 * Renderiza una tabla HTML.
 *
 * @param string $tableId   ID para la tabla (atributo id).
 * @param array  $headers   Array de cabeceras. Cada elemento es un array asociativo con:
 *                          'field' => nombre del campo en los datos,
 *                          'label' => etiqueta que se mostrará en el encabezado.
 * @param array  $rows      Array de registros, donde cada registro es un array asociativo.
 * @param string $buttons   HTML que se mostrará en la columna de acciones (puede contener múltiples botones).
 *
 * @return string HTML generado de la tabla.
 */

function renderTable($tableId, $data, $idusuario = null) {
    $headers = $data['headers'];
    $rows = $data['rows'];
    $html = '<table class="table table-sm small" id="' . htmlspecialchars($tableId) . '">';
    
    // Construir el encabezado de la tabla
    // Campos [field, label, classes]
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th class="' . (isset($header['classes']) ? $header['classes'] : '') . '">' . htmlspecialchars($header['label']) . '</th>';
    }
    $html .= '</tr></thead>';
    
    $html .= '<tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        // Recorrer cada cabecera definida
        foreach ($headers as $header) {
            switch ($header['field']) {
                case 'actions':
                // Si el header es "actions", construimos la celda de botones
                $actionHtml = '';
                if (!empty($row['buttons']) && is_array($row['buttons'])) {
                    foreach ($row['buttons'] as $button) {
                        switch ($button['type']) {
                            case 'map':
                                // Se asume que $button['fields'][0] contiene la clave del campo con la URL
                                $field = $button['fields'][0];
                                if ( $row[$field] != null ) {
                                    $actionHtml .= '<a href="' . htmlspecialchars($row[$field]) . '" target="_blank" class="btn btn-sm btn-info shadow-sm" data-toggle="tooltip" data-placement="top" title="Map">
                                                        <i class="fas fa-map"></i>
                                                    </a> ';
                                }
                                break;
                            case 'details':
                                // Se usa la URL base en action y se le concatena el parámetro obtenido de $row[$field]
                                $field = $button['fields'][0];
                                $actionHtml .= '<a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/detalleBuzon.php?viaje=' . urlencode($row[$field]) 
                                    . '", "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-primary shadow-sm" data-toggle="tooltip" data-placement="top" title="Details">
                                                    <i class="fas fa-info"></i>
                                                </a> ';
                                break;
                            case 'edit':
                                // Similar a details, pero la URL base es para editar
                                $field = $button['fields'][0];
                                $actionHtml .= '<a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/agregarViaje.php?viaje=' . urlencode($row[$field]) 
                                    . '", "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-success shadow-sm" data-toggle="tooltip" data-placement="top" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a> ';
                                break;
                            case 'editStatus':
                                // Similar a details, pero la URL base es para editar
                                $field = $button['fields'][0];

                                if ($row['idstatus'] == '20') {
                                    $actionHtml .= '<a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/aceptarViaje.php?viaje=' . urlencode($row[$field]) . '&idstatus=' . urlencode($row['idstatus'])
                                    . '", "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-info shadow-sm" data-toggle="tooltip" data-placement="top" title="Accept">
                                                    <i class="fas fa-check"></i>
                                                </a> ';

                                } else {
                                }
                                break;
                            case 'documents':
                                // Se usa la URL base en action y se le concatena el parámetro obtenido de $row[$field]
                                $field = $button['fields'][0];

                                if ($row['idstatus'] !== '22') {   
                                    $actionHtml .= '<a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/documentos.php?viaje=' . urlencode($row[$field]) 
                                        . '", "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-warning shadow-sm" data-toggle="tooltip" data-placement="top" title="Documents">
                                                        <i class="fas fa-file"></i>
                                                    </a> ';
                                } else {
                                }
                                break;
                            case 'excel':
                                // Se usa la URL base en action y se le concatena el parámetro obtenido de $row[$field]
                                $field = $button['fields'][0];
                                $actionHtml .= '<a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/excel.php?viaje=' . urlencode($row[$field]) 
                                    . '", "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-success shadow-sm" data-toggle="tooltip" data-placement="top" title="Add Merchandise">
                                                    <i class="far fa-file-excel text-white"></i>
                                                </a> ';
                                break;
                            case 'restore':
                                // Para delete, se espera que action sea el nombre de la función a ejecutar, a la que se le pasa el identificador.
                                $fieldId = $button['fields'][0];
                                
                                if ($row['idstatus'] !== '22') {
                                    $actionHtml .= '<a href="javascript:;" class="btn btn-sm btn-success shadow-sm" onclick="restore('. htmlspecialchars($row[$fieldId]) . ', ' . $idusuario . ')" data-toggle="tooltip" data-placement="top" title="Restore">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a> '; 
                                } else {
                                }
                                break;
                            case 'cancel':
                                $fieldId = $button['fields'][0];

                                if ($row['idstatus'] !== '22') {   
                                    $actionHtml .= '<a href="javascript:;" class="btn btn-sm btn-danger shadow-sm" onclick="handleDelete('. htmlspecialchars($row[$fieldId]) . ', ' . $idusuario . ')" data-toggle="tooltip" data-placement="top" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </a> ';
                                } else {
                                }
                                break;
                            default:
                                $actionHtml .= 'N/A';
                                break;
                        }
                    }
                        
                }
                // Se imprime la celda de acciones completa
                $html .= '<td class="text-right">' . $actionHtml . '</td>';
                    break;
                case 'status':
                    $field = $header['field'];
                    $value = isset($row[$field]) ? $row[$field] : '-';

                    $html .= '<td><span class="h5 mb-0 font-weight-bold text-white badge w-125" style="background-color: #'. htmlspecialchars($row['color']).';">'. htmlspecialchars($value) .'</span></td>';
                    
                    break;
                case 'unidad_info':
                    $field = $header['field'];
                    $value = isset($row[$field]) ? $row[$field] : '-';

                    if($row['nombreunidad'] == '') {
                        $html .= '<td><span title="Awaiting assignment" style="text-decoration: none; cursor: default;">REQUESTED</span></td>';
                    } else {
                        $html .= '<td>'. $row['nombreunidad'] .'</td>';
                    }
                    break;
                case 'documents':
                    $field = $header['field'];
                    $value = isset($row[$field]) ? $row[$field] : '-';

                    if (empty($row['totalDocuments'])) {
                        $html .= '<td class="text-center">N/A</span></td>';
                    }
                    else if ($row['countDocuments'] == $row['totalDocuments']) {
                        $html .= '<td class="text-center"><span class="h5 mb-0 font-weight-bold text-white badge badge-success w-125">'. htmlspecialchars($row['quantityDocuments']) .'</span></td>';
                    } else {
                        $html .= '<td class="text-center"><span class="h5 mb-0 font-weight-bold text-white badge badge-danger w-125">'. htmlspecialchars($row['quantityDocuments']) .'</span></td>';
                    }
                    break;
                case 'merchandise':
                    $field = $header['field'];
                    $value = isset($row[$field]) ? $row[$field] : '-';

                    if (!empty($row['totalMerch'])) {
                        $html .= '<td class="text-center">
                                            <a href="javascript:;" data-fancybox data-options=\'{"src": "/modulos/viajes/viajes/viewExcel.php?viaje=' . urlencode($row['idviaje']) . '", 
                                                "type": "ajax", "closeExisting": true, "clickSlide": false}\' class="btn btn-sm btn-outline-primary shadow-sm" data-toggle="tooltip" data-placement="top" title="Editar">
                                                <i class="fas fa-eye"></i> '. urlencode($row['totalMerch']) . '
                                            </a>
                                    </td>';

                    } else {
                        $html .= '<td class="text-center">N/A</td>';
                    }
                    break;
                default:
                    $field = $header['field'];
                    $value = isset($row[$field]) ? $row[$field] : '-';
                    if ($field === 'fecha' && !empty($value)) {
                        $dateObj = new DateTime($value);
                        $value = $dateObj->format('Y/m/d');
                    }

                    if($field === 'update_status' && !empty($value)) {
                        $dateObj = new DateTime($value);
                        $value = $dateObj->format('Y/m/d H:i:s');
                    }
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                    break;
            }
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    
    
    return $html;
}
?>
