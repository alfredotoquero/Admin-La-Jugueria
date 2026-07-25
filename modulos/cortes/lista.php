<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/cortes/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$cortes = new Cortes($con);

if (!$cortes->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para consultar cortes.</div>';
	exit;
}

$filtros = array(
	"idsucursal" => $_POST["idsucursal"] ?? 0,
	"idusuario" => $_POST["idusuario"] ?? 0,
	"fechadesde" => $_POST["fechadesde"] ?? "",
	"fechahasta" => $_POST["fechahasta"] ?? "",
);

$lista = $cortes->getCortes($idadministrador, $filtros);
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay cortes registrados con los filtros seleccionados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaCortes">
				<thead>
					<tr>
						<th>Sucursal</th>
						<th>Usuario</th>
						<th>Inicio</th>
						<th>Final</th>
						<th>Folios</th>
						<th class="text-right">Fondo Inicial</th>
						<th class="text-right">Gastos</th>
						<th class="text-right">Ventas</th>
						<th class="text-right">Fondo Final</th>
						<th class="text-center">Z</th>
						<th class="text-center">Estado</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $c) {
						$usuario = trim(($c["usuario_nombre"] ?? "") . " " . ($c["usuario_apaterno"] ?? "") . " " . ($c["usuario_amaterno"] ?? ""));
						$usuario = ($usuario !== "") ? $usuario : ("Usuario #" . (int) $c["idusuario"]);
						$abierto = ((int) $c["status"] === 0);
					?>
						<tr>
							<td><?= formatearLabel($c["sucursal"]) ?></td>
							<td><?= formatearLabel($usuario) ?></td>
							<td><?= formatearLabel(fecha_display($c["fechainicio"]) . " " . hora_formateada($c["horainicio"])) ?></td>
							<td><?= $abierto ? "-" : formatearLabel(fecha_display($c["fechafinal"]) . " " . hora_formateada($c["horafinal"])) ?></td>
							<td><?= $abierto ? "-" : ((int) $c["folioinicial"] . " - " . (int) $c["foliofinal"]) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["fondoinicial"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["gastos"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["ventas"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["fondofinal"])) ?></td>
							<td class="text-center"><?= (int) $c["z"] ?></td>
							<td class="text-center">
								<?php if ((int) $c["status"] === 1) { ?>
									<span class="badge badge-success">Cerrado</span>
								<?php } else { ?>
									<span class="badge badge-warning">Abierto</span>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } ?>
	</div>
</div>

<script>
	$(function () {
		if ($("#tablaCortes").length) {
			$("#tablaCortes").DataTable({
				order: [],
				language: {
					decimal: "",
					emptyTable: "No hay datos disponibles",
					info: "Mostrando _START_ de _END_ de _TOTAL_ resultados",
					infoEmpty: "No hay resultados",
					infoFiltered: "(filtrado de _MAX_ resultados)",
					infoPostFix: "",
					thousands: ",",
					lengthMenu: "Mostrar _MENU_ resultados",
					loadingRecords: "Cargando...",
					processing: "Procesando...",
					search: "Buscar:",
					zeroRecords: "No se encontraron resultados",
					paginate: {
						first: "Primero",
						last: "Ultimo",
						next: "Siguiente",
						previous: "Anterior",
					},
					aria: {
						sortAscending: ": activar de forma ascendente",
						sortDescending: ": activar de forma descendente",
					},
				},
			});
		}
	});
</script>
