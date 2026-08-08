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

$idcorte = (int) ($_GET["idcorte"] ?? 0);
if ($idcorte <= 0) {
	echo '<div class="alert alert-warning m-2 text-center">No se especifico el corte a verificar.</div>';
	exit;
}

$corte = $cortes->getCorte($idcorte, $idadministrador);

if ((int) $corte["status"] !== 1 || (int) $corte["z"] !== 0 || !$cortes->esProximoAVerificar($idcorte, $corte["idsucursal"])) {
	echo '<div class="alert alert-warning m-2 text-center">Este corte ya no esta disponible para verificar.</div>';
	exit;
}

$cuentas = $cortes->getCuentasCorte($idcorte, $idadministrador);
?>

<style>
	#fancyVerificarCorte {
		width: 70%;
	}

	@media only screen and (max-width: 740px) {
		#fancyVerificarCorte {
			width: 90%;
		}
	}
</style>

<div id="fancyVerificarCorte" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title">Verificacion de Corte #<?= (int) $corte["folio"] ?></h4>
		<div class="fancy-x-subtitle">Confirma cuales cuentas formaron parte de este corte.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmVerificarCorte">
			<input type="hidden" name="proceso" value="verificarCorte">
			<input type="hidden" name="idcorte" value="<?= (int) $idcorte ?>">

			<?php if (empty($cuentas)) { ?>
				<div class="alert alert-warning m-2 text-center">Este corte no tiene cuentas registradas.</div>
			<?php } else { ?>
				<div class="fancy-x-table-wrap">
					<div class="table-responsive">
						<table class="table table-hover table-sm small fancy-x-table" id="tablaVerificarCuentas">
							<thead>
								<tr>
									<th width="30"></th>
									<th class="text-center" width="70">Folio</th>
									<th>Descripcion</th>
									<th class="text-center" width="120">Fecha</th>
									<th class="text-right" width="100">Total</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($cuentas as $cuenta) { ?>
									<tr>
										<td class="text-center">
											<input type="checkbox" name="idcuenta[]" class="chkCuenta" value="<?= (int) $cuenta["idcuenta"] ?>" data-total="<?= number_format((float) $cuenta["total"], 2, ".", "") ?>" checked onchange="calcularTotalVerificacion()">
										</td>
										<td class="text-center"><span class="fancy-x-row-id"><?= str_pad((int) $cuenta["folio"], 7, "0", STR_PAD_LEFT) ?></span></td>
										<td>
											<?php foreach ($cuenta["productos"] as $producto) { ?>
												<div><strong><?= (int) $producto["cantidad"] ?></strong> <?= formatearLabel($producto["producto"]) ?></div>
											<?php } ?>
										</td>
										<td class="text-center"><?= formatearLabel(fecha_display($cuenta["fecha"])) ?> <?= formatearLabel(hora_formateada($cuenta["hora"])) ?></td>
										<td class="text-right">$<?= formatearLabel(number_format((float) $cuenta["total"], 2)) ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="row mt-2 mb-2">
					<div class="col-12">
						<div class="fancy-x-summary-card">
							<div class="fancy-x-summary-label">Total seleccionado</div>
							<div class="fancy-x-summary-value" id="txtTotalVerificacion"></div>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group d-xl-flex col-md-12 mb-0 justify-content-end">
						<button
							type="button"
							class="btn btn-danger btn-sm shadow-sm col-12 col-xl-auto mr-xl-1 mb-1 mb-xl-0"
							data-fancybox-close
						>
							Cancelar
						</button>
						<button
							type="button"
							class="btn btn-primary btn-sm shadow-sm col-12 col-xl-auto mt-xl-0"
							id="btnAccion"
							onclick="intentarVerificarCorte()"
						>
							Terminar
						</button>
					</div>
				</div>
			<?php } ?>
		</form>
	</div>
</div>

<script>
	$(function () {
		inicializarFancyX("#fancyVerificarCorte");
		calcularTotalVerificacion();
	});

	function calcularTotalVerificacion() {
		var total = 0;
		$(".chkCuenta:checked").each(function () {
			total += Number($(this).data("total"));
		});
		$("#txtTotalVerificacion").text("$" + total.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
	}

	function intentarVerificarCorte() {
		if ($(".chkCuenta:checked").length === 0) {
			parent.Swal.fire("Atencion", "Debes seleccionar al menos una cuenta.", "warning");
			return;
		}

		Swal.fire({
			title: "Confirmar verificacion",
			text: "Deseas realizar la verificacion con la informacion seleccionada?",
			icon: "warning",
			showCancelButton: true,
			confirmButtonText: "Si, verificar",
			cancelButtonText: "Cancelar",
			reverseButtons: true,
		}).then(function (result) {
			if (result.isConfirmed) {
				guardar("frmVerificarCorte", "cortes");
			}
		});
	}
</script>
