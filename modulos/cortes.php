<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/cortes/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$cortesClase = new Cortes($con);
$tieneAcceso = $cortesClase->tieneAccesoModulo($idadministrador);
$sucursalesUsuario = ($tieneAcceso) ? $cortesClase->getSucursalesUsuario($idadministrador) : array();

$fechaHastaDefault = date("Y-m-d");
$fechaDesdeDefault = date("Y-m-d", strtotime("-6 days"));
?>

<?php if (!$tieneAcceso) { ?>
	<div class="alert alert-warning">No tienes permiso para consultar cortes.</div>
<?php } else { ?>
	<div class="card shadow-sm border-0 mb-4">

		<div class="card-body border-bottom">
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
				<div class="mb-3 mb-md-0">
					<h4 class="mb-1 font-weight-bold text-primary">
						<i class="fas fa-cash-register mr-2"></i>
						Cortes
					</h4>
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
		var opcionesUsuarioCortes = [];

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
				opcionesUsuarioCortes = [];
				reinicializarSelectUsuarioCortes();
				return;
			}

			$.ajax({
				type: "POST",
				url: "/modulos/cortes/procesos.php",
				dataType: "json",
				data: {
					proceso: "getUsuariosSucursal",
					idsucursal: idsucursal
				},
				success: function (resp) {
					opcionesUsuarioCortes = (resp.result === "success") ? resp.data : [];
					reinicializarSelectUsuarioCortes();
				}
			});
		}

		function reinicializarSelectUsuarioCortes() {
			var $select = $("#filtroUsuario");
			if ($select.hasClass("select2-hidden-accessible")) {
				$select.select2("destroy");
			}
			$select.empty();
			$select.append($("<option>", { value: "0", text: "TODOS" }));
			opcionesUsuarioCortes.forEach(function (u) {
				$select.append($("<option>", { value: u.idusuario, text: u.nombre }));
			});
			$select.select2({ width: "100%" });
		}

		function recargarLista() {
			var idsucursal = $("#filtroSucursal").val();
			var idusuario = $("#filtroUsuario").val();
			var fechadesde = $("#filtroFechaDesde").val();
			var fechahasta = $("#filtroFechaHasta").val();
			cargarLista("/modulos/cortes/lista.php", {
				idsucursal: idsucursal,
				idusuario: idusuario,
				fechadesde: fechadesde,
				fechahasta: fechahasta
			}, "divLista");
		}
	</script>
<?php } ?>
