<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
if (isset($_SESSION["infoUsuario"])) {
	header("location: /home.php");
	exit;
} else {
	unset($_SESSION['infoUsuario']);
	unset($_SESSION["authToken"]);
	$_SESSION["authToken"] = sha1(uniqid(microtime(), true));
}
?>
<!doctype html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<title>Admin La Jugueria</title>

	<!-- Bootstrap core CSS -->
	<link href="/vendor/bootstrap/bootstrap.css" rel="stylesheet">
	<style>
		html,
		body {
			height: 100%;
		}

		body {
			display: -ms-flexbox;
			display: -webkit-box;
			display: flex;
			-ms-flex-align: center;
			-ms-flex-pack: center;
			-webkit-box-align: center;
			align-items: center;
			-webkit-box-pack: center;
			justify-content: center;
			padding-top: 40px;
			padding-bottom: 40px;
			background-color: #F0F1F1;
			color: rgba(255, 255, 255, 1.00);
		}

		.modal {
			color: rgba(0, 0, 0, 1.00) !important;
		}

		.form-signin {
			width: 100%;
			max-width: 330px;
			padding: 15px;
			margin: 0 auto;
		}

		.form-signin .checkbox {
			font-weight: 400;
		}

		.form-signin .form-control {
			position: relative;
			box-sizing: border-box;
			height: auto;
			padding: 10px;
			font-size: 16px;
		}

		.form-signin .form-control:focus {
			z-index: 2;
		}

		.form-signin input[type="email"] {
			margin-bottom: -1px;
			border-bottom-right-radius: 0;
			border-bottom-left-radius: 0;
		}

		.form-signin input[type="password"] {
			margin-bottom: 10px;
			border-top-left-radius: 0;
			border-top-right-radius: 0;
		}

		.error {
			color: orangered;
		}
	</style>
</head>

<body class="text-center">
	<form class="form-signin" id="formLogin" action="" method="post" data-toggle="validator">
		<input type="hidden" name="authToken" id="authToken" value="<?= $_SESSION["authToken"] ?>" />
		<img class="mb-2" src="/img/logo.png" alt="" style="width:150px;">

		<label for="txtUsuario" class="sr-only">Correo:</label>
		<input type="email" id="txtUsuario" name="txtUsuario" class="form-control" placeholder="Correo Electronico" required autofocus>
		<label for="txtPassword" class="sr-only">Contraseña</label>
		<input type="password" id="txtPassword" name="txtPassword" class="form-control" placeholder="Contraseña" required>
		<div class="checkbox mb-3"></div>
		<button class="btn btn-lg btn-primary btn-block" type="submit" id="btnIniciar">Iniciar Sesi&oacute;n</button>
		<p class="mt-5 mb-3 text-muted">LA JUGUERIA &copy; <?= date('Y') ?></p>
	</form>

	<div class="modal fade" id="modalLogin" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Login failed!</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body text-left" id="txtMensajeModal">
					...
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-warning" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>

	<script src="/vendor/jquery/jquery.min.js"></script>
	<script src="/vendor/bootstrap/bootstrap.js"></script>
	<script src="/vendor/jquery-easing/jquery.easing.min.js"></script>
	<script src="/vendor/validate/jquery.validate.min.js"></script>
	<script src="/vendor/validate/localization/messages_es.min.js"></script>
	<script src="/vendor/sweetalert/sweetalert.min.js"></script>
	<script id="funciones" data-idusuario="0" src="/js/funciones.js"></script>

	<script>
		$(document).ready(function() {
			$("#formLogin").validate({
				submitHandler: function(form) {
					$("#btnIniciar").prop("disabled", true);
					login();
				}
			});
		});
		$('#modalLogin').on('hidden.bs.modal', function() {
			$('#txtLogin').focus();
			$("#btnIniciar").prop("disabled", false);
		});
	</script>
</body>

</html>
