<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/cajeros/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$cajeros = new Cajeros($con);

if (!$cajeros->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para administrar cajeros.</div>';
	exit;
}

$sucursalesUsuario = $cajeros->getSucursalesUsuario($idadministrador);
if (empty($sucursalesUsuario)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes sucursales asignadas.</div>';
	exit;
}

$mostrarSucursal = $cajeros->mostrarSelectorSucursal($idadministrador);
$idsucursalFiltro = (int) ($_POST["idsucursal"] ?? 0);

$lista = $cajeros->getCajeros($idadministrador, $idsucursalFiltro);
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay cajeros registrados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaCajeros">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Usuario</th>
						<?php if ($mostrarSucursal) { ?>
							<th>Sucursal</th>
						<?php } ?>
						<th class="text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $c) {
						$nombreCompleto = trim($c["nombre"] . " " . $c["apaterno"] . " " . ($c["amaterno"] ?? ""));
					?>
						<tr>
							<td><?= formatearLabel($nombreCompleto) ?></td>
							<td><?= formatearLabel($c["usuario"]) ?></td>
							<?php if ($mostrarSucursal) { ?>
								<td><?= formatearLabel($c["sucursal"]) ?></td>
							<?php } ?>
							<td class="text-center">
								<a
									href="javascript:;"
									data-fancybox
									data-options='{"src":"/modulos/cajeros/agregar.php?id=<?= (int) $c["idusuario"] ?>","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
									class="btn btn-primary btn-sm"
									data-toggle="tooltip"
									title="Editar"
								>
									<i class="fas fa-edit"></i>
								</a>
								<a
									href="javascript:;"
									onclick="eliminar('eliminarCajero','cajeros','<?= (int) $c["idusuario"] ?>')"
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
		if ($("#tablaCajeros").length) {
			$("#tablaCajeros").DataTable({
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
