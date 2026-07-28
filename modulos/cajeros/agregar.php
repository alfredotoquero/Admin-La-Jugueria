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

$id = (int) ($_GET["id"] ?? 0);
$editando = ($id > 0);

$cajero = ($editando) ? $cajeros->getCajero($id, $idadministrador) : array(
	"idusuario" => 0,
	"idsucursal" => 0,
	"nombre" => "",
	"apaterno" => "",
	"amaterno" => "",
	"usuario" => "",
);

$sucursalesCatalogo = $cajeros->getSucursalesUsuario($idadministrador);
if (empty($sucursalesCatalogo)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes sucursales asignadas; no puedes registrar cajeros.</div>';
	exit;
}

$mostrarSucursal = $cajeros->mostrarSelectorSucursal($idadministrador);

$proceso = ($editando) ? "editarCajero" : "agregarCajero";
$claseRequeridaPassword = ($editando) ? "form-control" : "form-control requerido";
?>

<style>
	#fancyCajero {
		width: 50%;
	}

	@media only screen and (max-width: 740px) {
		#fancyCajero {
			width: 90%;
		}
	}
</style>

<div id="fancyCajero" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title"><?= $editando ? "Editar Cajero" : "Agregar Cajero" ?></h4>
		<div class="fancy-x-subtitle">Captura los datos de acceso del cajero y la sucursal donde opera.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmCajero" autocomplete="off">
			<input type="hidden" name="proceso" value="<?= $proceso ?>">
			<?php if ($editando) { ?>
				<input type="hidden" name="id" value="<?= (int) $cajero["idusuario"] ?>">
			<?php } ?>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Nombre <strong class="text-danger">*</strong></label>
					<input type="text" name="nombre" class="form-control requerido mayusculas" value="<?= formatearLabel($cajero["nombre"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Apellido Paterno <strong class="text-danger">*</strong></label>
					<input type="text" name="apaterno" class="form-control requerido mayusculas" value="<?= formatearLabel($cajero["apaterno"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Apellido Materno</label>
					<input type="text" name="amaterno" class="form-control mayusculas" value="<?= formatearLabel($cajero["amaterno"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 <?= $mostrarSucursal ? "col-md-6" : "" ?>">
					<label>Usuario <strong class="text-danger">*</strong></label>
					<input type="text" name="usuario" class="form-control requerido" value="<?= formatearLabel($cajero["usuario"]) ?>" autocomplete="off">
				</div>
				<?php if ($mostrarSucursal) { ?>
					<div class="form-group col-12 col-md-6">
						<label>Sucursal <strong class="text-danger">*</strong></label>
						<select name="idsucursal" class="form-control select2 requerido">
							<option value="">-- Selecciona --</option>
							<?php foreach ($sucursalesCatalogo as $sucursal) { ?>
								<option value="<?= (int) $sucursal["idsucursal"] ?>" <?= ((int) $cajero["idsucursal"] === (int) $sucursal["idsucursal"]) ? "selected" : "" ?>>
									<?= formatearLabel($sucursal["nombre"]) ?>
								</option>
							<?php } ?>
						</select>
					</div>
				<?php } else { ?>
					<input type="hidden" name="idsucursal" value="<?= (int) $sucursalesCatalogo[0]["idsucursal"] ?>">
				<?php } ?>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Contrasena <?= $editando ? "" : '<strong class="text-danger">*</strong>' ?></label>
					<input type="password" id="txtPassword" name="password" class="<?= $claseRequeridaPassword ?>" autocomplete="off">
					<?php if ($editando) { ?>
						<small class="form-text text-muted">Dejar en blanco para no cambiar la contrasena.</small>
					<?php } ?>
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Confirmar Contrasena <?= $editando ? "" : '<strong class="text-danger">*</strong>' ?></label>
					<input type="password" id="txtPassword2" class="form-control" autocomplete="off">
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
						onclick="intentarGuardarCajero()"
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
		inicializarFancyX("#fancyCajero");
	});

	function intentarGuardarCajero() {
		var pass1 = $("#txtPassword").val();
		var pass2 = $("#txtPassword2").val();

		if (pass1 !== pass2) {
			parent.Swal.fire("Atencion", "Las contrasenas no coinciden.", "warning");
			return;
		}

		guardar("frmCajero", "cajeros");
	}
</script>
