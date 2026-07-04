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

$id = (int) ($_GET["id"] ?? 0);
$editando = ($id > 0);

$usuario = ($editando) ? $usuarios->getUsuario($id) : array(
	"idadministrador" => 0,
	"nombre" => "",
	"paterno" => "",
	"materno" => "",
	"correo" => "",
	"admin" => 0,
	"opciones" => array(),
	"sucursales" => array(),
);

$opcionesCatalogo = $usuarios->getOpciones();
$sucursalesCatalogo = $usuarios->getSucursales();

$proceso = ($editando) ? "editarUsuario" : "agregarUsuario";
$claseRequeridaPassword = ($editando) ? "form-control" : "form-control requerido";
?>

<style>
	#fancyUsuario {
		width: 50%;
	}

	@media only screen and (max-width: 740px) {
		#fancyUsuario {
			width: 90%;
		}
	}
</style>

<div id="fancyUsuario" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title"><?= $editando ? "Editar Usuario" : "Agregar Usuario" ?></h4>
		<div class="fancy-x-subtitle">Captura los datos de acceso y los permisos del usuario.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmUsuario">
			<input type="hidden" name="proceso" value="<?= $proceso ?>">
			<?php if ($editando) { ?>
				<input type="hidden" name="id" value="<?= (int) $usuario["idadministrador"] ?>">
			<?php } ?>
			<input type="hidden" name="admin" id="admin" value="<?= (int) $usuario["admin"] ?>">

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Nombre <strong class="text-danger">*</strong></label>
					<input type="text" name="nombre" class="form-control requerido mayusculas" value="<?= formatearLabel($usuario["nombre"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Apellido Paterno <strong class="text-danger">*</strong></label>
					<input type="text" name="paterno" class="form-control requerido mayusculas" value="<?= formatearLabel($usuario["paterno"]) ?>">
				</div>
				<div class="form-group col-12 col-md-3">
					<label>Apellido Materno</label>
					<input type="text" name="materno" class="form-control mayusculas" value="<?= formatearLabel($usuario["materno"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Correo <strong class="text-danger">*</strong></label>
					<input type="email" name="correo" class="form-control requerido" value="<?= formatearLabel($usuario["correo"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-6">
					<label>Contrasena <?= $editando ? "" : '<strong class="text-danger">*</strong>' ?></label>
					<input type="password" id="txtPassword" name="password" class="<?= $claseRequeridaPassword ?>" autocomplete="new-password">
					<?php if ($editando) { ?>
						<small class="form-text text-muted">Dejar en blanco para no cambiar la contrasena.</small>
					<?php } ?>
				</div>
				<div class="form-group col-12 col-md-6">
					<label>Confirmar Contrasena <?= $editando ? "" : '<strong class="text-danger">*</strong>' ?></label>
					<input type="password" id="txtPassword2" class="form-control" autocomplete="new-password">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<div class="custom-control custom-checkbox">
						<input type="checkbox" class="custom-control-input" id="chkAdmin" <?= ((int) $usuario["admin"] === 1) ? "checked" : "" ?>>
						<label class="custom-control-label" for="chkAdmin">Es Administrador (acceso total al sistema)</label>
					</div>
				</div>
			</div>

			<div id="bloqueAccesos" <?= ((int) $usuario["admin"] === 1) ? 'style="display:none;"' : "" ?>>
				<div class="form-row">
					<div class="form-group col-12 col-md-6">
						<label>Opciones con acceso <strong class="text-danger">*</strong></label>
						<div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
							<?php if (empty($opcionesCatalogo)) { ?>
								<div class="small text-muted">No hay opciones registradas.</div>
							<?php } ?>
							<?php foreach ($opcionesCatalogo as $opcion) { ?>
								<div class="custom-control custom-checkbox">
									<input
										type="checkbox"
										class="custom-control-input chkOpcion"
										id="opcion<?= (int) $opcion["idopcion"] ?>"
										name="opciones[]"
										value="<?= (int) $opcion["idopcion"] ?>"
										<?= in_array($opcion["idopcion"], $usuario["opciones"]) ? "checked" : "" ?>
									>
									<label class="custom-control-label" for="opcion<?= (int) $opcion["idopcion"] ?>"><?= formatearLabel($opcion["nombre"]) ?></label>
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="form-group col-12 col-md-6">
						<label>Sucursales con acceso <strong class="text-danger">*</strong></label>
						<div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
							<?php if (empty($sucursalesCatalogo)) { ?>
								<div class="small text-muted">No hay sucursales registradas.</div>
							<?php } ?>
							<?php foreach ($sucursalesCatalogo as $sucursal) { ?>
								<div class="custom-control custom-checkbox">
									<input
										type="checkbox"
										class="custom-control-input chkSucursal"
										id="sucursal<?= (int) $sucursal["idsucursal"] ?>"
										name="sucursales[]"
										value="<?= (int) $sucursal["idsucursal"] ?>"
										<?= in_array($sucursal["idsucursal"], $usuario["sucursales"]) ? "checked" : "" ?>
									>
									<label class="custom-control-label" for="sucursal<?= (int) $sucursal["idsucursal"] ?>"><?= formatearLabel($sucursal["nombre"]) ?></label>
								</div>
							<?php } ?>
						</div>
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
						onclick="intentarGuardarUsuario()"
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
		inicializarFancyX("#fancyUsuario");

		$("#chkAdmin").on("change", function () {
			var esAdmin = $(this).is(":checked");
			$("#admin").val(esAdmin ? "1" : "0");
			$("#bloqueAccesos").toggle(!esAdmin);
		});
	});

	function intentarGuardarUsuario() {
		var pass1 = $("#txtPassword").val();
		var pass2 = $("#txtPassword2").val();

		if (pass1 !== pass2) {
			parent.Swal.fire("Atencion", "Las contrasenas no coinciden.", "warning");
			return;
		}

		if ($("#admin").val() == "0") {
			if ($(".chkOpcion:checked").length == 0) {
				parent.Swal.fire("Atencion", "Selecciona al menos una opcion.", "warning");
				return;
			}
			if ($(".chkSucursal:checked").length == 0) {
				parent.Swal.fire("Atencion", "Selecciona al menos una sucursal.", "warning");
				return;
			}
		}

		guardar("frmUsuario", "usuarios");
	}
</script>
