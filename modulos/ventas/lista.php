<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/ventas/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$ventas = new Ventas($con);

if (!$ventas->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para consultar ventas.</div>';
	exit;
}

$filtros = array(
	"idsucursal" => $_POST["idsucursal"] ?? 0,
	"idusuario" => $_POST["idusuario"] ?? 0,
	"fechadesde" => $_POST["fechadesde"] ?? "",
	"fechahasta" => $_POST["fechahasta"] ?? "",
);

$lista = $ventas->getVentasPorProducto($idadministrador, $filtros);

$totalCantidad = 0;
$totalImporte = 0;
foreach ($lista as $v) {
	$totalCantidad += (float) $v["cantidad"];
	$totalImporte += (float) $v["total"];
}
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay ventas registradas con los filtros seleccionados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaVentas">
				<thead>
					<tr>
						<th>Producto</th>
						<th class="text-right">Cantidad Vendida</th>
						<th class="text-right">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $v) { ?>
						<tr>
							<td>
								<b><?= formatearLabel($v["nombre"]) ?></b>
								<?php if (trim((string) ($v["descripcion"] ?? "")) !== "") { ?>
									<br><span class="text-muted small"><?= formatearLabel($v["descripcion"]) ?></span>
								<?php } ?>
							</td>
							<td class="text-right"><?= formatearLabel(smart_number_format($v["cantidad"], 0)) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($v["total"])) ?></td>
						</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
						<th class="text-right">Total</th>
						<th class="text-right"><?= formatearLabel(smart_number_format($totalCantidad, 0)) ?></th>
						<th class="text-right">$<?= formatearLabel(smart_number_format($totalImporte)) ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	<?php } ?>
	</div>
</div>

<script>
	$(function () {
		if ($("#tablaVentas").length) {
			$("#tablaVentas").DataTable({
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
