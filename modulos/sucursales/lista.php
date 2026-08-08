<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/sucursales/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$sucursales = new Sucursales($con);

if (!$sucursales->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para administrar sucursales.</div>';
	exit;
}

$lista = $sucursales->getSucursales();
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay sucursales registradas.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaSucursales">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Razon Social</th>
						<th>RFC</th>
						<th class="text-right">Siguiente Folio</th>
						<th>Registro</th>
						<th class="text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $s) { ?>
						<tr>
							<td><?= formatearLabel($s["nombre"]) ?></td>
							<td><?= formatearLabel($s["ticket_nombre"]) ?></td>
							<td><?= formatearLabel($s["ticket_rfc"]) ?></td>
							<td class="text-right"><?= number_format((int) $s["siguiente_folio"]) ?></td>
							<td><?= formatearLabel(fecha_display($s["registro"])) ?></td>
							<td class="text-center">
								<a
									href="javascript:;"
									data-fancybox
									data-options='{"src":"/modulos/sucursales/agregar.php?id=<?= (int) $s["idsucursal"] ?>","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
									class="btn btn-primary btn-sm"
									data-toggle="tooltip"
									title="Editar"
								>
									<i class="fas fa-edit"></i>
								</a>
								<a
									href="javascript:;"
									onclick="eliminar('eliminarSucursal','sucursales','<?= (int) $s["idsucursal"] ?>')"
									class="btn btn-danger btn-sm"
									data-toggle="tooltip"
									title="Eliminar"
								>
									<i class="fas fa-trash"></i>
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
		if ($("#tablaSucursales").length) {
			$("#tablaSucursales").DataTable({
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
