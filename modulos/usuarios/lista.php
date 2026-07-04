<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/usuarios/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$usuarios = new Usuarios($con);

if (!$usuarios->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para administrar usuarios.</div>';
	exit;
}

$lista = $usuarios->getUsuarios();
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay usuarios registrados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaUsuarios">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Correo</th>
						<th>Tipo</th>
						<th>Registro</th>
						<th class="text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $u) {
						$nombreCompleto = trim($u["nombre"] . " " . $u["paterno"] . " " . ($u["materno"] ?? ""));
						$esPropio = ((int) $u["idadministrador"] === (int) $idadministrador);
					?>
						<tr>
							<td><?= formatearLabel($nombreCompleto) ?></td>
							<td><?= formatearLabel($u["correo"]) ?></td>
							<td>
								<?php if ((int) $u["admin"] === 1) { ?>
									<span class="badge badge-primary">Administrador</span>
								<?php } else { ?>
									<span class="badge badge-secondary">Usuario limitado</span>
								<?php } ?>
							</td>
							<td><?= formatearLabel(fecha_display($u["registro"])) ?></td>
							<td class="text-center">
								<a
									href="javascript:;"
									data-fancybox
									data-options='{"src":"/modulos/usuarios/agregar.php?id=<?= (int) $u["idadministrador"] ?>","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
									class="btn btn-primary btn-sm"
									data-toggle="tooltip"
									title="Editar"
								>
									<i class="fas fa-edit"></i>
								</a>
								<?php if (!$esPropio) { ?>
									<a
										href="javascript:;"
										onclick="eliminar('eliminarUsuario','usuarios','<?= (int) $u["idadministrador"] ?>')"
										class="btn btn-danger btn-sm"
										data-toggle="tooltip"
										title="Eliminar"
									>
										<i class="fas fa-trash"></i>
									</a>
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
		if ($("#tablaUsuarios").length) {
			$("#tablaUsuarios").DataTable({
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
