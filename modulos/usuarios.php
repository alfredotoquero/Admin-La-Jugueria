<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/modulos/usuarios/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$usuariosClase = new Usuarios($con);
$tieneAcceso = $usuariosClase->tieneAccesoModulo($idadministrador);
?>

<?php if (!$tieneAcceso) { ?>
	<div class="alert alert-warning">No tienes permiso para administrar usuarios.</div>
<?php } else { ?>
	<div class="card shadow-sm border-0 mb-4">
		<div class="card-body">
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
				<div class="mb-3 mb-md-0">
					<h4 class="mb-1 font-weight-bold text-primary">
						<i class="fas fa-users mr-2"></i>
						Usuarios
					</h4>
				</div>
				<div class="d-flex align-items-center">
					<a
						href="javascript:;"
						data-fancybox
						data-options='{"src":"/modulos/usuarios/agregar.php","type":"ajax","closeExisting":true,"clickSlide":false,"touch":false}'
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
			cargarLista("/modulos/usuarios/lista.php", "", "divLista");
		}
	</script>
<?php } ?>
