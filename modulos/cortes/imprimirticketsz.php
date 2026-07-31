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
	echo '<div class="alert alert-warning m-2 text-center">No se especifico la verificacion a imprimir.</div>';
	exit;
}

try {
	$corte = $cortes->getVerificacionParaImprimir($idcorte, $idadministrador);
	$cuentas = $cortes->getCuentasVerificacion($idcorte, $idadministrador);
} catch (Exception $e) {
	echo '<div class="alert alert-warning m-2 text-center">' . formatearLabel($e->getMessage() ? explode("|", $e->getMessage())[0] : "No se pudo cargar la verificacion.") . '</div>';
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Tickets de Verificacion #<?= (int) $corte["idcorte"] ?></title>
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
			margin: 0 auto 30px;
		}

		.centrado {
			text-align: center;
		}

		.separador {
			border: none;
			border-top: 1px dashed #888;
			margin: 10px 0;
		}

		table.productos {
			width: 100%;
			border-collapse: collapse;
			margin: 6px 0;
		}

		table.productos th,
		table.productos td {
			padding: 2px 4px;
			font-size: 12px;
		}

		table.productos th {
			text-align: left;
			border-bottom: 1px solid #888;
		}

		table.productos td.numero {
			text-align: right;
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

			.ticket {
				page-break-after: always;
			}
		}
	</style>
</head>
<body>
	<?php if (empty($cuentas)) { ?>
		<div class="alert alert-warning m-2 text-center">Esta verificacion no tiene cuentas registradas.</div>
	<?php } else { ?>
		<?php foreach ($cuentas as $cuenta) {
			$total = (float) $cuenta["total"];
			$efectivo = $total + (float) $cuenta["cambio"];
			$articulos = 0;
			foreach ($cuenta["productos"] as $producto) {
				$articulos += (int) $producto["cantidad"];
			}
			$montoEnLetras = strtoupper(num2letras(number_format($total, 2, ".", "")));
		?>
			<div class="ticket">
				<div class="centrado">
					<strong><?= formatearLabel($corte["ticket_negocio"]) ?></strong><br>
					<?= formatearLabel($corte["ticket_calle"] . " No. " . $corte["ticket_numero"]) ?><br>
					<?= formatearLabel($corte["ticket_colonia"] . " C.P. " . $corte["ticket_codigopostal"]) ?><br>
					<?= formatearLabel($corte["ticket_ciudad"]) ?><br>
					<?= formatearLabel($corte["ticket_nombre"]) ?><br>
					<?= formatearLabel($corte["ticket_rfc"]) ?><br>
					<?= formatearLabel($corte["ticket_regimen"]) ?><br>
					<?= formatearLabel(fecha_display($cuenta["fecha"]) . " " . hora_formateada($cuenta["hora"]) . " " . str_pad($cuenta["idcuenta"], 7, "0", STR_PAD_LEFT)) ?>
				</div>

				<hr class="separador">

				<table class="productos">
					<thead>
						<tr>
							<th>Cant.</th>
							<th>Producto</th>
							<th class="numero">Precio</th>
							<th class="numero">Importe</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($cuenta["productos"] as $producto) { ?>
							<tr>
								<td><?= (int) $producto["cantidad"] ?></td>
								<td><?= formatearLabel($producto["producto"]) ?></td>
								<td class="numero">$<?= number_format((float) $producto["precio"], 2) ?></td>
								<td class="numero">$<?= number_format((float) $producto["precio"] * (int) $producto["cantidad"], 2) ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

				<hr class="separador">

				<table class="datos">
					<tr>
						<td>TOTAL</td>
						<td class="valor">$<?= number_format($total, 2) ?></td>
					</tr>
					<tr>
						<td>EFECTIVO</td>
						<td class="valor">$<?= number_format($efectivo, 2) ?></td>
					</tr>
					<tr>
						<td>CAMBIO</td>
						<td class="valor">$<?= number_format((float) $cuenta["cambio"], 2) ?></td>
					</tr>
				</table>

				<div class="centrado" style="margin-top: 10px;"><?= formatearLabel($montoEnLetras) ?></div>

				<hr class="separador">

				<div class="centrado">ARTICULOS <?= (int) $articulos ?></div>

				<hr class="separador">

				<div class="centrado"><strong>GRACIAS POR SU COMPRA</strong></div>
			</div>
		<?php } ?>

		<button type="button" class="btn-imprimir" onclick="window.print()">Imprimir</button>
	<?php } ?>
</body>
</html>
