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

$lista = $cortes->getVerificaciones($idadministrador, $filtros);
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay verificaciones registradas con los filtros seleccionados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaVerificaciones">
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
						<th class="text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $c) {
						$usuario = trim(($c["usuario_nombre"] ?? "") . " " . ($c["usuario_apaterno"] ?? "") . " " . ($c["usuario_amaterno"] ?? ""));
						$usuario = ($usuario !== "") ? $usuario : ("Usuario #" . (int) $c["idusuario"]);
					?>
						<tr>
							<td><?= formatearLabel($c["sucursal"]) ?></td>
							<td><?= formatearLabel($usuario) ?></td>
							<td><?= formatearLabel(fecha_display($c["fechainicio"]) . " " . hora_formateada($c["horainicio"])) ?></td>
							<td><?= formatearLabel(fecha_display($c["fechafinal"]) . " " . hora_formateada($c["horafinal"])) ?></td>
							<td><?= (int) $c["folioinicial"] . " - " . (int) $c["foliofinal"] ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["fondoinicial"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["gastos"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["ventas"])) ?></td>
							<td class="text-right">$<?= formatearLabel(smart_number_format($c["fondofinal"])) ?></td>
							<td class="text-center">
								<a
									href="/modulos/cortes/imprimirz.php?idcorte=<?= (int) $c["idcorte"] ?>"
									target="_blank"
									class="btn btn-secondary btn-sm"
									data-toggle="tooltip"
									title="Imprimir resumen"
								>
									<i class="fas fa-print"></i>
								</a>
								<a
									href="/modulos/cortes/imprimirticketsz.php?idcorte=<?= (int) $c["idcorte"] ?>"
									target="_blank"
									class="btn btn-info btn-sm"
									data-toggle="tooltip"
									title="Imprimir tickets"
								>
									<i class="fas fa-receipt"></i>
								</a>
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
		if ($("#tablaVerificaciones").length) {
			$("#tablaVerificaciones").DataTable({
				order: [],
				columnDefs: [{ orderable: false, targets: -1 }],
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
		$("[data-toggle='tooltip']").tooltip();
	});
</script>
