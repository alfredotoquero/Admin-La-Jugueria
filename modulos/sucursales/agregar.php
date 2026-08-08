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

$id = (int) ($_GET["id"] ?? 0);
$editando = ($id > 0);

$sucursal = ($editando) ? $sucursales->getSucursal($id) : array(
	"idsucursal" => 0,
	"nombre" => "",
	"ticket_negocio" => "",
	"ticket_calle" => "",
	"ticket_numero" => "",
	"ticket_colonia" => "",
	"ticket_codigopostal" => "",
	"ticket_ciudad" => "",
	"ticket_nombre" => "",
	"ticket_rfc" => "",
	"ticket_regimen" => "",
	"ticket_nombreimpresora" => "",
	"siguiente_folio" => 1,
	"siguiente_folio_corte" => 1,
	"siguiente_folio_cortez" => 1,
	"siguiente_folio_cuentaz" => 1,
);

$proceso = ($editando) ? "editarSucursal" : "agregarSucursal";
?>

<style>
	#fancySucursal {
		width: 55%;
	}

	@media only screen and (max-width: 740px) {
		#fancySucursal {
			width: 90%;
		}
	}
</style>

<div id="fancySucursal" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title"><?= $editando ? "Editar Sucursal" : "Agregar Sucursal" ?></h4>
		<div class="fancy-x-subtitle">Captura los datos generales de la sucursal y la informacion que se imprime en el ticket.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmSucursal">
			<input type="hidden" name="proceso" value="<?= $proceso ?>">
			<?php if ($editando) { ?>
				<input type="hidden" name="id" value="<?= (int) $sucursal["idsucursal"] ?>">
			<?php } ?>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Nombre de la sucursal <strong class="text-danger">*</strong></label>
					<input type="text" name="nombre" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["nombre"]) ?>">
				</div>
			</div>

			<h6 class="font-weight-bold text-primary mt-2 mb-3">
				<i class="fas fa-receipt mr-1"></i>
				Datos del ticket
			</h6>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Nombre del negocio <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_negocio" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_negocio"]) ?>">
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Razon social <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_nombre" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_nombre"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Calle <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_calle" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_calle"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Numero <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_numero" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_numero"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Codigo Postal <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_codigopostal" class="form-control requerido mayusculas" maxlength="5" value="<?= formatearLabel($sucursal["ticket_codigopostal"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Colonia <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_colonia" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_colonia"]) ?>">
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Ciudad <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_ciudad" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_ciudad"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>RFC <strong class="text-danger">*</strong></label>
					<input type="text" id="txtRfc" name="ticket_rfc" class="form-control requerido mayusculas" maxlength="13" value="<?= formatearLabel($sucursal["ticket_rfc"]) ?>">
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Regimen fiscal <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_regimen" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_regimen"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Nombre de la impresora <strong class="text-danger">*</strong></label>
					<input type="text" name="ticket_nombreimpresora" class="form-control requerido mayusculas" value="<?= formatearLabel($sucursal["ticket_nombreimpresora"]) ?>">
				</div>
			</div>

			<h6 class="font-weight-bold text-primary mt-2 mb-1">
				<i class="fas fa-hashtag mr-1"></i>
				Folios
			</h6>
			<p class="small text-muted mb-3">
				Numero con el que se emitira el proximo documento de cada tipo en esta sucursal.
				Cada sucursal lleva su consecutivo por separado y no se puede retroceder por
				debajo de un folio ya emitido.
			</p>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Venta (ticket) <strong class="text-danger">*</strong></label>
					<input type="number" name="siguiente_folio" class="form-control requerido" min="1" max="9999999" step="1" value="<?= (int) $sucursal["siguiente_folio"] ?>">
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Corte de caja <strong class="text-danger">*</strong></label>
					<input type="number" name="siguiente_folio_corte" class="form-control requerido" min="1" max="9999999" step="1" value="<?= (int) $sucursal["siguiente_folio_corte"] ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Verificacion de corte <strong class="text-danger">*</strong></label>
					<input type="number" name="siguiente_folio_cortez" class="form-control requerido" min="1" max="9999999" step="1" value="<?= (int) $sucursal["siguiente_folio_cortez"] ?>">
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Ticket verificado <strong class="text-danger">*</strong></label>
					<input type="number" name="siguiente_folio_cuentaz" class="form-control requerido" min="1" max="9999999" step="1" value="<?= (int) $sucursal["siguiente_folio_cuentaz"] ?>">
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
						onclick="guardar('frmSucursal', 'sucursales')"
					>
						Guardar
					</button>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	$(function () {
		inicializarFancyX("#fancySucursal");
	});
</script>
