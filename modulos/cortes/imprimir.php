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
	echo '<div class="alert alert-warning m-2 text-center">No se especifico el corte a imprimir.</div>';
	exit;
}

try {
	$corte = $cortes->getCorteParaImprimir($idcorte, $idadministrador);
} catch (Exception $e) {
	echo '<div class="alert alert-warning m-2 text-center">' . formatearLabel($e->getMessage() ? explode("|", $e->getMessage())[0] : "No se pudo cargar el corte.") . '</div>';
	exit;
}

$montoEnLetras = strtoupper(num2letras(number_format((float) $corte["fondofinal"], 2, ".", "")));
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Corte de Caja #<?= (int) $corte["folio"] ?></title>
	<style>
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 13px;
			color: #1c2a3a;
			margin: 0;
			padding: 20px;
		}

		.ticket {
			max-width: 420px;
			margin: 0 auto;
		}

		.centrado {
			text-align: center;
		}

		.separador {
			border: none;
			border-top: 1px dashed #888;
			margin: 10px 0;
		}

		table.datos {
			width: 100%;
			border-collapse: collapse;
		}

		table.datos td {
			padding: 3px 0;
		}

		table.datos td.valor {
			text-align: right;
		}

		.btn-imprimir {
			display: block;
			margin: 20px auto 0;
			padding: 10px 24px;
			background: #103B74;
			color: #fff;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-size: 14px;
		}

		@media print {
			.btn-imprimir {
				display: none;
			}
		}
	</style>
</head>
<body>
	<div class="ticket">
		<div class="centrado">
			<strong><?= formatearLabel($corte["ticket_negocio"]) ?></strong><br>
			<?= formatearLabel($corte["sucursal"]) ?><br>
			<?= formatearLabel($corte["ticket_calle"] . " No. " . $corte["ticket_numero"]) ?><br>
			<?= formatearLabel($corte["ticket_colonia"] . " C.P. " . $corte["ticket_codigopostal"]) ?><br>
			<?= formatearLabel($corte["ticket_ciudad"]) ?><br>
			<?= formatearLabel($corte["ticket_nombre"]) ?><br>
			<?= formatearLabel($corte["ticket_rfc"]) ?><br>
			<?= formatearLabel($corte["ticket_regimen"]) ?>
		</div>

		<hr class="separador">

		<div class="centrado"><strong>CORTE DE CAJA #<?= (int) $corte["folio"] ?></strong></div>
		<div class="centrado"><?= formatearLabel(fecha_display($corte["fechafinal"]) . " " . hora_formateada($corte["horafinal"])) ?></div>

		<hr class="separador">

		<table class="datos">
			<tr>
				<td>FONDO FINAL</td>
				<td class="valor">$<?= number_format((float) $corte["fondofinal"], 2) ?></td>
			</tr>
			<tr>
				<td colspan="2"><strong>DESGLOSE:</strong></td>
			</tr>
			<tr>
				<td>FONDO INICIAL (MXN)</td>
				<td class="valor">$<?= number_format((float) $corte["fondoinicial"], 2) ?></td>
			</tr>
			<?php if ((float) $corte["ventas"] > 0) { ?>
				<tr>
					<td>EFECTIVO (MXN)</td>
					<td class="valor">$<?= number_format((float) $corte["ventas"], 2) ?></td>
				</tr>
			<?php } ?>
			<tr>
				<td>TOTAL DE GASTOS</td>
				<td class="valor">$<?= number_format((float) $corte["gastos"], 2) ?></td>
			</tr>
			<tr>
				<td>FOLIO INICIAL DEL CORTE</td>
				<td class="valor"><?= (int) $corte["folioinicial"] ?></td>
			</tr>
			<tr>
				<td>FOLIO FINAL DEL CORTE</td>
				<td class="valor"><?= (int) $corte["foliofinal"] ?></td>
			</tr>
		</table>

		<div class="centrado" style="margin-top: 10px;"><?= formatearLabel($montoEnLetras) ?></div>

		<hr class="separador">

		<div class="centrado"><strong>FIRMAS</strong></div>
		<div style="height: 80px;"></div>

		<hr class="separador">

		<div class="centrado"><strong>CORTE DE CAJA</strong></div>

		<button type="button" class="btn-imprimir" onclick="window.print()">Imprimir</button>
	</div>
</body>
</html>
