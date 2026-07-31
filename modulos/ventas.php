<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/ventas/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$ventasClase = new Ventas($con);
$tieneAcceso = $ventasClase->tieneAccesoModulo($idadministrador);
$sucursalesUsuario = ($tieneAcceso) ? $ventasClase->getSucursalesUsuario($idadministrador) : array();

$fechaHastaDefault = date("Y-m-d");
$fechaDesdeDefault = date("Y-m-d", strtotime("-6 days"));
?>

<?php if (!$tieneAcceso) { ?>
	<div class="alert alert-warning">No tienes permiso para consultar ventas.</div>
<?php } else { ?>
	<div class="card shadow-sm border-0 mb-4">

		<div class="card-body border-bottom">
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
				<div class="mb-3 mb-md-0">
					<h4 class="mb-1 font-weight-bold text-primary">
						<i class="fas fa-chart-line mr-2"></i>
						Ventas
					</h4>
				</div>
				<div class="d-flex align-items-center">
					<button type="button" class="btn btn-secondary shadow-sm" onclick="imprimirReporteVentas()">
						<i class="fas fa-print"></i>
						<span class="d-none d-md-inline ml-1">Imprimir</span>
					</button>
				</div>
			</div>
		</div>

		<div class="card-body bg-light">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label class="small text-muted font-weight-bold mb-1">Sucursal</label>
					<select class="form-control select2" style="width: 100%;" id="filtroSucursal">
						<option value="0">TODAS</option>
						<?php foreach ($sucursalesUsuario as $sucursal) { ?>
							<option value="<?= (int) $sucursal["idsucursal"] ?>"><?= formatearLabel($sucursal["nombre"]) ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="small text-muted font-weight-bold mb-1">Usuario</label>
					<select class="form-control select2" style="width: 100%;" id="filtroUsuario">
						<option value="0">TODOS</option>
					</select>
				</div>
				<div class="col-md-2 mb-3">
					<label class="small text-muted font-weight-bold mb-1">Fecha desde</label>
					<input type="date" class="form-control" id="filtroFechaDesde" value="<?= $fechaDesdeDefault ?>">
				</div>
				<div class="col-md-2 mb-3">
					<label class="small text-muted font-weight-bold mb-1">Fecha hasta</label>
					<input type="date" class="form-control" id="filtroFechaHasta" value="<?= $fechaHastaDefault ?>">
				</div>
				<div class="col-md-2 mb-3 d-flex align-items-end">
					<button type="button" class="btn btn-primary btn-block" onclick="recargarLista()">
						<i class="fas fa-search"></i>
						<span class="d-none d-md-inline ml-1">Buscar</span>
					</button>
				</div>
			</div>
		</div>

	</div>

	<div id="divLista"></div>

	<script>
		var opcionesUsuarioVentas = [];

		$(document).ready(function () {
			recargarLista();
			$('.select2').select2();

			$("#filtroSucursal").on("change", function () {
				cargarUsuariosSucursal();
			});
		});

		function cargarUsuariosSucursal() {
			var idsucursal = $("#filtroSucursal").val();

			if (idsucursal == 0) {
				opcionesUsuarioVentas = [];
				reinicializarSelectUsuarioVentas();
				return;
			}

			$.ajax({
				type: "POST",
				url: "/modulos/ventas/procesos.php",
				dataType: "json",
				data: {
					proceso: "getUsuariosSucursal",
					idsucursal: idsucursal
				},
				success: function (resp) {
					opcionesUsuarioVentas = (resp.result === "success") ? resp.data : [];
					reinicializarSelectUsuarioVentas();
				}
			});
		}

		function reinicializarSelectUsuarioVentas() {
			var $select = $("#filtroUsuario");
			if ($select.hasClass("select2-hidden-accessible")) {
				$select.select2("destroy");
			}
			$select.empty();
			$select.append($("<option>", { value: "0", text: "TODOS" }));
			opcionesUsuarioVentas.forEach(function (u) {
				$select.append($("<option>", { value: u.idusuario, text: u.nombre }));
			});
			$select.select2({ width: "100%" });
		}

		function recargarLista() {
			var idsucursal = $("#filtroSucursal").val();
			var idusuario = $("#filtroUsuario").val();
			var fechadesde = $("#filtroFechaDesde").val();
			var fechahasta = $("#filtroFechaHasta").val();
			cargarLista("/modulos/ventas/lista.php", {
				idsucursal: idsucursal,
				idusuario: idusuario,
				fechadesde: fechadesde,
				fechahasta: fechahasta
			}, "divLista");
		}

		function imprimirReporteVentas() {
			var params = $.param({
				idsucursal: $("#filtroSucursal").val(),
				idusuario: $("#filtroUsuario").val(),
				fechadesde: $("#filtroFechaDesde").val(),
				fechahasta: $("#filtroFechaHasta").val(),
				sucursalnombre: $("#filtroSucursal option:selected").text(),
				usuarionombre: $("#filtroUsuario option:selected").text()
			});
			window.open("/modulos/ventas/imprimir.php?" + params, "_blank");
		}
	</script>
<?php } ?>
