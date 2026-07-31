<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/ventas/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$ventas = new Ventas($con);

if (!$ventas->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para consultar ventas.</div>';
	exit;
}

$filtros = array(
	"idsucursal" => $_GET["idsucursal"] ?? 0,
	"idusuario" => $_GET["idusuario"] ?? 0,
	"fechadesde" => $_GET["fechadesde"] ?? "",
	"fechahasta" => $_GET["fechahasta"] ?? "",
);

$sucursalNombre = (((int) $filtros["idsucursal"]) > 0) ? trim((string) ($_GET["sucursalnombre"] ?? "")) : "Todas";
$usuarioNombre = (((int) $filtros["idusuario"]) > 0) ? trim((string) ($_GET["usuarionombre"] ?? "")) : "Todos";

$lista = $ventas->getVentasPorProducto($idadministrador, $filtros);

$totalCantidad = 0;
$totalImporte = 0;
foreach ($lista as $v) {
	$totalCantidad += (float) $v["cantidad"];
	$totalImporte += (float) $v["total"];
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Reporte de Ventas</title>
	<style>
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 13px;
			color: #1c2a3a;
			margin: 0;
			padding: 20px;
		}

		.reporte {
			max-width: 900px;
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

		table.filtros {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 10px;
		}

		table.filtros td {
			padding: 2px 0;
		}

		table.productos {
			width: 100%;
			border-collapse: collapse;
		}

		table.productos th, table.productos td {
			padding: 6px 8px;
			border-bottom: 1px solid #ddd;
		}

		table.productos th {
			text-align: left;
			border-bottom: 2px solid #1c2a3a;
		}

		table.productos td.valor, table.productos th.valor {
			text-align: right;
		}

		table.productos tfoot td {
			border-top: 2px solid #1c2a3a;
			border-bottom: none;
			font-weight: bold;
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
	<div class="reporte">
		<div class="centrado">
			<strong>REPORTE DE VENTAS</strong>
		</div>

		<hr class="separador">

		<table class="filtros">
			<tr>
				<td><strong>Sucursal:</strong> <?= formatearLabel($sucursalNombre) ?></td>
				<td><strong>Usuario:</strong> <?= formatearLabel($usuarioNombre) ?></td>
			</tr>
			<tr>
				<td colspan="2">
					<strong>Periodo:</strong>
					<?= formatearLabel(($filtros["fechadesde"] !== "") ? fecha_display($filtros["fechadesde"]) : "-") ?>
					al
					<?= formatearLabel(($filtros["fechahasta"] !== "") ? fecha_display($filtros["fechahasta"]) : "-") ?>
				</td>
			</tr>
		</table>

		<hr class="separador">

		<?php if (empty($lista)) { ?>
			<div class="centrado">No hay ventas registradas con los filtros seleccionados.</div>
		<?php } else { ?>
			<table class="productos">
				<thead>
					<tr>
						<th>Producto</th>
						<th class="valor">Cantidad Vendida</th>
						<th class="valor">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($lista as $v) { ?>
						<tr>
							<td><?= formatearLabel($v["nombre"]) ?></td>
							<td class="valor"><?= formatearLabel(smart_number_format($v["cantidad"], 0)) ?></td>
							<td class="valor">$<?= formatearLabel(smart_number_format($v["total"])) ?></td>
						</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
						<td>Total</td>
						<td class="valor"><?= formatearLabel(smart_number_format($totalCantidad, 0)) ?></td>
						<td class="valor">$<?= formatearLabel(smart_number_format($totalImporte)) ?></td>
					</tr>
				</tfoot>
			</table>
		<?php } ?>

		<button type="button" class="btn-imprimir" onclick="window.print()">Imprimir</button>
	</div>
</body>
</html>
