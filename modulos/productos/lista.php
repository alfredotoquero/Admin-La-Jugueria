<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/productos/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$productos = new Productos($con);

if (!$productos->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para administrar productos.</div>';
	exit;
}

$idsucursalFiltro = (int) ($_POST["idsucursal"] ?? 0);

$lista = $productos->getProductos($idadministrador, $idsucursalFiltro);
$conteoSucursales = $productos->getConteoSucursalesPorProducto();
?>

<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
	<?php if (empty($lista)) { ?>
		<div class="alert alert-warning m-2 text-center">No hay productos registrados.</div>
	<?php } else { ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 nowrap dataTable no-footer table-sm small" id="tablaProductos">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Precio</th>
						<th>Tipo</th>
						<th class="text-center">Sucursales</th>
						<th class="text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $p) { ?>
						<tr>
							<td>
								<?= formatearLabel($p["nombre"]) ?>
								<?php if (trim($p["descripcion"] ?? "") !== "") { ?>
									<br><small class="text-muted"><?= formatearLabel($p["descripcion"]) ?></small>
								<?php } ?>
							</td>
							<td>
								<?php if ((int) $p["precio_variable"] === 1) { ?>
									<span class="text-muted">Varia por sucursal</span>
								<?php } else { ?>
									$<?= formatearLabel(smart_number_format($p["precio"])) ?>
								<?php } ?>
							</td>
							<td>
								<?php if ((int) $p["servicio"] === 1) { ?>
									<span class="badge badge-info">Servicio</span>
								<?php } else { ?>
									<span class="badge badge-secondary">Producto</span>
								<?php } ?>
							</td>
							<td class="text-center"><?= (int) ($conteoSucursales[$p["idproducto"]] ?? 0) ?></td>
							<td class="text-center">
								<a
									href="javascript:;"
									data-fancybox
									data-options='{"src":"/modulos/productos/agregar.php?id=<?= (int) $p["idproducto"] ?>","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
									class="btn btn-primary btn-sm"
									data-toggle="tooltip"
									title="Editar"
								>
									<i class="fas fa-edit"></i>
								</a>
								<a
									href="javascript:;"
									onclick="eliminar('eliminarProducto','productos','<?= (int) $p["idproducto"] ?>')"
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
		if ($("#tablaProductos").length) {
			$("#tablaProductos").DataTable({
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
