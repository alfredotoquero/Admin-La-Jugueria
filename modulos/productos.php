<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/productos/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$productosClase = new Productos($con);
$tieneAcceso = $productosClase->tieneAccesoModulo($idadministrador);
$sucursalesUsuario = ($tieneAcceso) ? $productosClase->getSucursalesUsuario($idadministrador) : array();
?>

<?php if (!$tieneAcceso) { ?>
	<div class="alert alert-warning">No tienes permiso para administrar productos.</div>
<?php } else { ?>
	<div class="card shadow-sm border-0 mb-4">
		<div class="card-body">
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
				<div class="mb-3 mb-md-0">
					<h4 class="mb-1 font-weight-bold text-primary">
						<i class="fas fa-box mr-2"></i>
						Productos
					</h4>
				</div>
				<div class="d-flex align-items-center">
					<?php if (count($sucursalesUsuario) > 1) { ?>
						<select id="filtroSucursal" class="form-control select2 mr-2" style="width: 220px;" onchange="recargarLista()">
							<option value="0">-- Todas las sucursales --</option>
							<?php foreach ($sucursalesUsuario as $sucursal) { ?>
								<option value="<?= (int) $sucursal["idsucursal"] ?>"><?= formatearLabel($sucursal["nombre"]) ?></option>
							<?php } ?>
						</select>
					<?php } ?>
					<a
						href="javascript:;"
						data-fancybox
						data-options='{"src":"/modulos/productos/agregar.php","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
						class="btn btn-primary shadow-sm"
					>
						<i class="fas fa-plus"></i>
						<span class="d-none d-md-inline">Agregar</span>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div id="divLista"></div>

	<script>
		$(document).ready(function () {
			recargarLista();
		});

		function recargarLista() {
			var idsucursal = $("#filtroSucursal").length ? $("#filtroSucursal").val() : 0;
			cargarLista("/modulos/productos/lista.php", { idsucursal: idsucursal }, "divLista");
		}
	</script>
<?php } ?>
