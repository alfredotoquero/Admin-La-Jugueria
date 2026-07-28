<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/cajeros/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$cajerosClase = new Cajeros($con);
$tieneAcceso = $cajerosClase->tieneAccesoModulo($idadministrador);
$sucursalesUsuario = ($tieneAcceso) ? $cajerosClase->getSucursalesUsuario($idadministrador) : array();
$mostrarSucursal = ($tieneAcceso) ? $cajerosClase->mostrarSelectorSucursal($idadministrador) : false;
?>

<?php if (!$tieneAcceso) { ?>
	<div class="alert alert-warning">No tienes permiso para administrar cajeros.</div>
<?php } else { ?>
	<div class="card shadow-sm border-0 mb-4">
		<div class="card-body">
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
				<div class="mb-3 mb-md-0">
					<h4 class="mb-1 font-weight-bold text-primary">
						<i class="fas fa-user-tie mr-2"></i>
						Cajeros
					</h4>
				</div>
				<div class="d-flex align-items-center">
					<?php if ($mostrarSucursal) { ?>
						<div class="mr-2" style="width: 220px;">
							<select id="filtroSucursal" class="form-control select2" onchange="recargarLista()">
								<option value="0">-- Todas las sucursales --</option>
								<?php foreach ($sucursalesUsuario as $sucursal) { ?>
									<option value="<?= (int) $sucursal["idsucursal"] ?>"><?= formatearLabel($sucursal["nombre"]) ?></option>
								<?php } ?>
							</select>
						</div>
					<?php } ?>
					<a
						href="javascript:;"
						data-fancybox
						data-options='{"src":"/modulos/cajeros/agregar.php","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
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
			cargarLista("/modulos/cajeros/lista.php", { idsucursal: idsucursal }, "divLista");
		}
	</script>
<?php } ?>
