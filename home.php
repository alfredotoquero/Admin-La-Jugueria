<?php
/**
 * Home / shell de la aplicación autenticada.
 *
 * Arquitectura genérica que se conserva de intranet-xensei (proyecto
 * de un solo tenant, sin selector de empresa):
 *   - Sidebar dinámico armado desde BD (topciones, filtrado para
 *     administradores no-admin vía tradministradoropciones).
 *   - Navbar superior con dropdown de usuario.
 *   - Despacho de la vista de cada opción vía include('modulos/{modulo1}.php'),
 *     donde {modulo1} es el primer segmento de topciones.url.
 *
 * Lo que se podó respecto al original (ver detalles en el resumen de la
 * migración): banners de vigencia/timbres de facturación, avisos de RH,
 * felicitaciones de cumpleaños/aniversario (hardcodeadas a un idusuario),
 * botón de soporte/tickets, integración de OneSignal, "ver como usuario",
 * llaves de Google Maps hardcodeadas, y los overrides de etiquetas de menú
 * específicos de módulos de negocio de Xensei (Manager de Ruta, POS, Corp).
 */
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
header("Cache-Control: no-cache, must-revalidate");

$correo = $_SESSION['infoUsuario']['correo'];
$idadministrador = $_SESSION['infoUsuario']['idadministrador'];
$nombre = $_SESSION['infoUsuario']['nombre'];

// Sidebar: administradores con admin=1 ven todas las opciones.
// El resto solo ve las opciones que tiene asignadas explícitamente.
if ($_SESSION['infoUsuario']['admin'] == 1) {
	$sql = "SELECT * FROM topciones ORDER BY nombre";
} else {
	$sql = "SELECT o.*
		FROM topciones o
		INNER JOIN tradministradoropciones ao ON ao.idopcion = o.idopcion
		WHERE ao.idadministrador = " . (int) $idadministrador . "
		ORDER BY o.nombre";
}
$result = mysqli_query($con, $sql);
$opciones = array();
while ($row = mysqli_fetch_assoc($result)) {
	$opciones[] = $row;
}

function primerSegmentoUrl($url) {
	$segmentos = explode('/', trim($url, '/'));
	return $segmentos[0];
}

$v = "?v=1.0.0";
?>
<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="shortcut icon" href="/img/logo.png" sizes="16x16 24x24 32x32 48x48 64x64">
	<title>Admin La Jugueria</title>

	<!--
		Set mínimo de vendors para arrancar (jQuery, Bootstrap, DataTables,
		Select2, SweetAlert2, Toastr, Font Awesome). Ajusta/agrega los que
		tu proyecto necesite (editor WYSIWYG, date pickers avanzados,
		gráficas, mapas, drag&drop de archivos, etc.) — el original tenía
		muchos más, casi todos reemplazables por versiones más nuevas.
	-->
	<script src="/vendor/jquery/jquery.min.js"></script>
	<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<link href="/vendor/fontawesome-pro-5/css/all.min.css" rel="stylesheet" type="text/css">

	<!--
		style.php: fork temático de sb-admin-2.css (Start Bootstrap, MIT) que
		expone --primary/--secondary y las clases fancy-x-* como variables CSS
		por tenant (ver css/style.php y /agent_manuals/agents_FANCYS.md).
		Sustituye a css/sb-admin-2.css como hoja de estilo base cargada en
		runtime; sb-admin-2.css/.min.css se conservan en el repo como
		referencia del vendor original sin theming.
	-->
	<link rel="stylesheet" type="text/css" href="/css/style.php">
	<link rel="stylesheet" type="text/css" href="/css/style.css">

	<link rel="stylesheet" href="/vendor/select2/css/select2.min.css" />
	<link rel="stylesheet" href="/vendor/select2/css/theme/select2-bootstrap4.css">
	<script type="text/javascript" src="/vendor/select2/js/select2.min.js"></script>
	<script type="text/javascript" src="/vendor/select2/js/i18n/es.js"></script>

	<link rel="stylesheet" href="/vendor/datatables/dataTables.bootstrap4.min.css" />
	<script type="text/javascript" src="/vendor/datatables/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="/vendor/datatables/dataTables.bootstrap4.min.js"></script>

	<link rel="stylesheet" href="/css/sweetalert2.min.css">
	<script src="/vendor/sweetalert/sweetalert2.min.js"></script>

	<!--
		loadingModal: requerido por showModal()/hideModal() en js/funciones.js,
		usadas por guardar()/eliminar()/solicitudServidor() para bloquear la
		pantalla mientras hay una petición AJAX en curso.
	-->
	<link rel="stylesheet" type="text/css" href="/vendor/loadingModal/css/jquery.loadingModal.min.css" />
	<script src="/vendor/loadingModal/js/jquery.loadingModal.min.js"></script>

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

	<!--
		FancyBox: requerido por el atributo data-fancybox (manejado en
		js/funciones.js) y por inicializarFancyX(), usados en los modales
		fancy-x-* documentados en /agent_manuals/agents_FANCYS.md.
	-->
	<link rel="stylesheet" type="text/css" href="/vendor/fancybox/jquery.fancybox.min.css">
	<script src="/vendor/fancybox/jquery.fancybox.min.js"></script>

	<!-- Custom scripts for all pages-->
	<script src="/vendor/jquery-easing/jquery.easing.min.js"></script>
	<script src="/js/sb-admin-2.min.js"></script>
	<!--
		funciones.js: subconjunto genérico podado de intranet-xensei (ver
		encabezado del archivo). Expone solicitudServidor/cargarLista/guardar/
		eliminar/validateForm, el manejo global de errores 401/
		SessionExpiredError, y los helpers fancy-x-* (inicializarFancyX).
		Se le pasan estos data-attributes como "configuración" que lee al
		cargar.
	-->
	<script id="funciones" data-idusuario="<?= $_SESSION["infoUsuario"]["idadministrador"] ?? "" ?>" data-ultimo_acceso="<?= $_SESSION["ultimo_acceso"] ?? "" ?>" data-debugger="<?= $_SESSION["infoUsuario"]["debugger"] ?? 0 ?>" data-modulo1="<?= $_GET["modulo1"] ?? "" ?>" data-modulo2="<?= $_GET["modulo2"] ?? "" ?>" src="/js/funciones.js<?= $v ?? "" ?>"></script>
</head>

<body id="page-top">

	<div class="se-pre-con"></div>

	<!-- EJEMPLO: aquí es donde el proyecto original mostraba banners de
	     aviso (vigencia de suscripción, avisos de RH, festivos próximos,
	     etc.). Se removieron por ser 100% de negocio; agrega los tuyos
	     propios si los necesitas. -->

	<div id="wrapper">
		<ul class="navbar-nav bg-gradient-sidebar sidebar sidebar-dark toggled accordion" id="accordionSidebar">
			<hr class="sidebar-divider my-0">
			<?php
			$i = 0;
			foreach ($opciones as $opcion) {
				$modulo1 = primerSegmentoUrl($opcion['url']);
				if (isset($_GET['modulo1']) && $_GET['modulo1'] == $modulo1) {
					$active = 'active';
				} else {
					$active = ((!isset($_GET["modulo1"]) || $_GET["modulo1"] == "") && $i == 0) ? "active" : "";
				}

				echo '<li class="nav-item">
						<a class="nav-link menuLink ' . $active . '" href="/' . $opcion['url'] . '">
							<i class="' . $opcion['icono'] . '"></i>
							<span>' . $opcion['nombre'] . '</span>
						</a>
					  </li>';
				$i++;
			}
			?>
		</ul>
		<div id="content-wrapper" class="d-flex flex-column">
			<div id="content">
				<nav class="navbar navbar-expand navbar-light bg-white topbar mb-1 static-top shadow">
					<img src="/img/logo.png" class="logoSideBar" height="auto">

					<!-- Sidebar Toggle (Topbar) -->
					<button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
						<i class="fa fa-bars"></i>
					</button>

					<!-- Topbar Navbar -->
					<ul class="navbar-nav ml-auto">

						<!-- Nav Item - User Information -->
						<li class="nav-item dropdown no-arrow">

							<a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= strtoupper($_SESSION['infoUsuario']['nombre']); ?></span>
								<img class="img-profile rounded-circle" src="/img/user.jpg">
							</a>
							<!-- Dropdown - User Information -->
							<div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
								<!-- EJEMPLO: liga a tu propio módulo de cambio de contraseña. -->
								<a class="dropdown-item" href="javascript:;" data-fancybox data-options='{"src" : "/modulos/password.php?id=<?= $_SESSION['infoUsuario']['idadministrador'] ?>", "type" : "ajax", "closeExisting": true, "clickSlide": false, "touch": false}'>
									<i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
									Cambiar Contraseña
								</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="/salir.php">
									<i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
									Cerrar Sesion
								</a>
							</div>
						</li>
					</ul>
				</nav>
				<!-- End of Topbar -->

				<!-- Begin Page Content -->
				<div class="container-fluid mt-4" id="contenedor">
					<?php
					// Cada opción del sidebar corresponde a un archivo bajo modulos/,
					// nombrado a partir del primer segmento de topciones.url.
					// Ej. url "recursosHumanos/empleados" -> modulos/recursosHumanos.php
					$modulo1Disponibles = array();
					foreach ($opciones as $opcion) {
						$modulo1Disponibles[] = primerSegmentoUrl($opcion['url']);
					}
					if (!empty($opciones) && in_array($_GET["modulo1"] ?? "", $modulo1Disponibles)) {
						include('modulos/' . $_GET['modulo1'] . '.php');
					} else if (!empty($opciones)) {
						include('modulos/' . $modulo1Disponibles[0] . '.php');
					} else {
						echo '<div class="alert alert-warning">No tienes ninguna opción habilitada. Da de alta registros en topciones / tradministradoropciones.</div>';
					}
					?>
				</div>
				<!-- /.container-fluid -->
			</div>
			<!-- End of Main Content -->

			<!-- Footer -->
			<footer class="sticky-footer bg-white">
				<div class="container my-auto">
					<div class="copyright text-center my-auto">
						<span>Copyright &copy; LA JUGUERIA</span>
					</div>
				</div>
			</footer>
			<!-- End of Footer -->

		</div>
		<!-- End of Content Wrapper -->

	</div>
	<!-- End of Page Wrapper -->

	<!-- Scroll to Top Button -->
	<a class="scroll-to-top rounded" href="#page-top">
		<i class="fas fa-angle-up"></i>
	</a>

	<script>
		$(document).ready(function() {
			$.fn.select2.defaults.set("theme", "bootstrap4");
			$('.select2').select2();
		});
	</script>

</body>

</html>
