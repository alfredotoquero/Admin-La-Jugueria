<?php
namespace Modulos\Reusables\ExelUtils;

class DocumentosVencerLogic
{
    /**
     * Procesa la información de un documento y devuelve el contenido formateado y el color asignado.
     *
     * @param array $documentData Datos del documento con claves 'vigencia' y 'vencido'
     * @return array ['contenido' => string, 'color' => string]
     */
    public static function procesarDocumento(array $documentData)
    {
        if (isset($documentData['vencido'])) {
            $estado = $documentData['vencido'];
            $vigencia = $documentData['vigencia'];
            $fecha = ($vigencia && $vigencia != '0000-00-00')
                ? date("d/m/Y", strtotime($vigencia))
                : 'SIN FECHA';
            $contenido = empty($fecha) ? $estado : "$estado ($fecha)";
            
            $color = 'FFFFFF'; // Valor por defecto
            if (strcasecmp($estado, 'VENCIDO') === 0) {
                $color = 'e74a3b';
            } elseif (strcasecmp($estado, 'PRÓXIMO A VENCER') === 0) {
                $color = 'f6c23e';
            } elseif (strcasecmp($estado, 'SIN ASIGNAR') === 0) {
                $color = '858796';
            } elseif (strcasecmp($estado, 'VIGENTE') === 0) {
                $color = '1cc88a';
            }
            
            return [
                'contenido' => $contenido,
                'color'     => $color
            ];
        }
        return [
            'contenido' => 'N/A',
            'color'     => 'FFFFFF'
        ];
    }
}
