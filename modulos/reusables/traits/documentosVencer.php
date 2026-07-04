<?php
trait documentosVencer {
  public function getInfoEntidadDocumentos($idEntidad, $idempresa) {
      $tablaEntidad = "templeados";
      $campoID = "idempleado";
      $tablaDocumentos = "empleados_documentos";
      $tablaRequisitos = "tr_requisitos_puesto";
      $campoFiltroRequisitos = "rp.idpuesto = e.idpuesto";

      $query = "
          SELECT e.*,
          (
              CASE
                  WHEN EXISTS(
                      SELECT 1 FROM trprospectos_documentacion pd
                      WHERE pd.idprospecto IN (
                          SELECT idprospecto FROM tprospectos WHERE idempleado = e.$campoID
                      )
                  ) THEN (
                      SELECT COUNT(0) FROM trprospectos_documentacion pd
                      WHERE pd.idprospecto IN (
                          SELECT idprospecto FROM tprospectos
                          WHERE idempleado = e.$campoID AND status = 3
                      )
                      AND pd.idrequisicion IN (SELECT idrequisicion FROM trequisiciones)
                  )
                  ELSE (
                      SELECT COUNT(0) FROM $tablaRequisitos rp
                      WHERE $campoFiltroRequisitos
                  )
              END
          ) AS documentos_totales,
          (
              SELECT COUNT(0) FROM $tablaDocumentos ed
              JOIN $tablaRequisitos rp ON ed.iddocumento = rp.idrequisito
              WHERE ed.$campoID = e.$campoID
              AND $campoFiltroRequisitos
              AND ed.vigencia <= CURDATE()
              AND ed.vigencia != '0000-00-00'
          ) AS cantidad_vencidos,
          (
              (
                  SELECT COUNT(0) FROM $tablaDocumentos ed
                  JOIN $tablaRequisitos rp ON ed.iddocumento = rp.idrequisito
                  WHERE ed.$campoID = e.$campoID
                  AND $campoFiltroRequisitos
                  AND (ed.vigencia > CURDATE() OR ed.vigencia = '0000-00-00' OR ed.vigencia IS NULL)
              )
              + (
                  SELECT COUNT(0) FROM $tablaDocumentos ed
                  WHERE ed.$campoID = e.$campoID
                  AND (ed.vigencia > CURDATE() OR ed.vigencia = '0000-00-00' OR ed.vigencia IS NULL)
                  AND ed.iddocumento NOT IN (
                      SELECT idrequisito FROM $tablaRequisitos WHERE idpuesto = e.idpuesto
                  )
                  AND ed.iddocumento NOT IN (
                      SELECT pd.iddocumento FROM trprospectos_documentacion pd
                      JOIN tprospectos pr ON pd.idprospecto = pr.idprospecto
                      WHERE pr.idempleado = e.$campoID AND pr.status = 3
                      AND pd.idrequisicion IN (SELECT idrequisicion FROM trequisiciones)
                  )
              )
          ) AS cantidad_documentos,
          (
              (
                  SELECT COUNT(0)
                  FROM $tablaDocumentos ed
                  JOIN tprospectos pr ON pr.idempleado = ed.idempleado AND pr.status = 3
                  JOIN trprospectos_documentacion rp ON rp.idprospecto = pr.idprospecto
                      AND rp.iddocumento = ed.iddocumento
                      AND rp.idrequisicion IN (SELECT idrequisicion FROM trequisiciones)
                  WHERE ed.$campoID = e.$campoID
                  AND (ed.vigencia > CURDATE() OR ed.vigencia = '0000-00-00' OR ed.vigencia IS NULL)
              )
              + (
                  SELECT COUNT(0) FROM $tablaDocumentos ed
                  WHERE ed.$campoID = e.$campoID
                  AND (ed.vigencia > CURDATE() OR ed.vigencia = '0000-00-00' OR ed.vigencia IS NULL)
                  AND ed.iddocumento NOT IN (
                      SELECT idrequisito FROM $tablaRequisitos WHERE idpuesto = e.idpuesto
                  )
                  AND ed.iddocumento NOT IN (
                      SELECT pd.iddocumento FROM trprospectos_documentacion pd
                      JOIN tprospectos pr ON pd.idprospecto = pr.idprospecto
                      WHERE pr.idempleado = e.$campoID AND pr.status = 3
                      AND pd.idrequisicion IN (SELECT idrequisicion FROM trequisiciones)
                  )
              )
          ) AS cantidad_documentos_prospecto,
          (
              SELECT COUNT(0) FROM $tablaDocumentos ed
              JOIN $tablaRequisitos rp ON ed.iddocumento = rp.idrequisito
              WHERE ed.$campoID = e.$campoID
              AND $campoFiltroRequisitos
              AND (ed.vigencia > CURDATE() OR ed.vigencia = '0000-00-00' OR ed.vigencia IS NULL)
          ) AS docs_puesto_entregados,
          (
              SELECT COUNT(0) FROM $tablaRequisitos rp
              WHERE $campoFiltroRequisitos
          ) AS docs_puesto_total
          FROM $tablaEntidad e
          WHERE e.$campoID = '$idEntidad'
          AND e.idempresa = '$idempresa'
      ";

      $numrows = mysqli_num_rows(mysqli_query($this->con, $query));
      $result = [];

      if ($numrows > 0) {
          $result = mysqli_fetch_array(mysqli_query($this->con, $query));
      }

      return $result;
  }
}

?>