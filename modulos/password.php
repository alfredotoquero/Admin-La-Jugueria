<?php
/**
 * Cambio de contrasena del usuario en sesion. Se abre desde el menu de usuario
 * (esquina superior derecha de home.php). Siempre cambia la contrasena de quien
 * tiene la sesion iniciada: no recibe ningun id.
 */
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
?>

<style>
	#fancyPassword {
		width: 35%;
	}

	@media only screen and (max-width: 740px) {
		#fancyPassword {
			width: 90%;
		}
	}
</style>

<div id="fancyPassword" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title">Cambiar Contrasena</h4>
		<div class="fancy-x-subtitle">Confirma tu contrasena actual y captura la nueva.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmPassword">
			<input type="hidden" name="proceso" value="cambiarPassword">

			<div class="form-row">
				<div class="form-group col-12">
					<label>Contrasena actual <strong class="text-danger">*</strong></label>
					<input type="password" name="password_actual" class="form-control requerido" autocomplete="current-password">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Nueva contrasena <strong class="text-danger">*</strong></label>
					<input type="password" id="txtPasswordNueva" name="password_nueva" class="form-control requerido" autocomplete="new-password">
					<small class="form-text text-muted">Minimo 6 caracteres.</small>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Confirmar nueva contrasena <strong class="text-danger">*</strong></label>
					<input type="password" id="txtPasswordConfirmacion" name="password_confirmacion" class="form-control requerido" autocomplete="new-password">
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
						onclick="intentarCambiarPassword()"
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
		inicializarFancyX("#fancyPassword");
	});

	function intentarCambiarPassword() {
		var nueva = $("#txtPasswordNueva").val();

		if (nueva.length > 0 && nueva.length < 6) {
			parent.Swal.fire("Atencion", "La nueva contrasena debe tener al menos 6 caracteres.", "warning");
			return;
		}

		if (nueva !== $("#txtPasswordConfirmacion").val()) {
			parent.Swal.fire("Atencion", "La nueva contrasena y su confirmacion no coinciden.", "warning");
			return;
		}

		// Con callback, guardar() no intenta recargar una lista: este fancy se
		// abre desde cualquier pantalla.
		guardar("frmPassword", "password", 0, function () {
			parent.jQuery.fancybox.getInstance().close();
		});
	}
</script>
