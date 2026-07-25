<?php
include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/generales.php");
include($_SERVER["DOCUMENT_ROOT"] . "/modulos/productos/clase.php");

$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
$productos = new Productos($con);

if (!$productos->tieneAccesoModulo($idadministrador)) {
	echo '<div class="alert alert-warning m-2 text-center">No tienes permiso para administrar productos.</div>';
	exit;
}

$id = (int) ($_GET["id"] ?? 0);
$editando = ($id > 0);

$producto = ($editando) ? $productos->getProducto($id, $idadministrador) : array(
	"idproducto" => 0,
	"nombre" => "",
	"descripcion" => "",
	"precio" => "",
	"precio_variable" => 0,
	"servicio" => 0,
	"sucursales" => array(),
);

$sucursalesCatalogo = $productos->getSucursalesUsuario($idadministrador);

$proceso = ($editando) ? "editarProducto" : "agregarProducto";
?>

<style>
	#fancyProducto {
		width: 45%;
	}

	@media only screen and (max-width: 740px) {
		#fancyProducto {
			width: 90%;
		}
	}

	#fancyProducto .segmentado-tipo {
		display: inline-flex;
		border: 1px solid #ced4da;
		border-radius: .25rem;
		overflow: hidden;
	}

	#fancyProducto .segmentado-tipo .btn {
		border: none;
		border-radius: 0;
		background: #fff;
		color: #495057;
	}

	#fancyProducto .segmentado-tipo .btn.active {
		background: var(--fancy-color-1);
		color: #fff;
		box-shadow: none;
	}

	#fancyProducto .txtPrecioGeneral:disabled {
		background-color: #e9ecef;
	}
</style>

<div id="fancyProducto" class="fancy-x-root">
	<div class="fancy-x-header">
		<h4 class="fancy-x-title"><?= $editando ? "Editar Producto" : "Agregar Producto" ?></h4>
		<div class="fancy-x-subtitle">Captura los datos del producto y las sucursales donde estará disponible.</div>
	</div>

	<div class="fancy-x-body">
		<form id="frmProducto">
			<input type="hidden" name="proceso" value="<?= $proceso ?>">
			<?php if ($editando) { ?>
				<input type="hidden" name="id" value="<?= (int) $producto["idproducto"] ?>">
			<?php } ?>
			<input type="hidden" name="servicio" id="servicio" value="<?= (int) $producto["servicio"] ?>">
			<input type="hidden" name="precio_variable" id="precio_variable" value="<?= (int) $producto["precio_variable"] ?>">

			<div class="form-row">
				<div class="form-group col-12">
					<label>Nombre <strong class="text-danger">*</strong></label>
					<input type="text" name="nombre" class="form-control requerido mayusculas" value="<?= formatearLabel($producto["nombre"]) ?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Descripcion</label>
					<textarea name="descripcion" class="form-control" rows="2"><?= formatearLabel($producto["descripcion"]) ?></textarea>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label class="d-block">Tipo de producto</label>
					<div class="btn-group segmentado-tipo" role="group">
						<button type="button" class="btn btn-sm btnTipoProducto <?= ((int) $producto["servicio"] === 0) ? "active" : "" ?>" data-servicio="0">Producto</button>
						<button type="button" class="btn btn-sm btnTipoProducto <?= ((int) $producto["servicio"] === 1) ? "active" : "" ?>" data-servicio="1">Servicio</button>
					</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12 col-md-4">
					<label>Precio general <strong class="text-danger" id="reqPrecioGeneral">*</strong></label>
					<input type="number" step="0.01" min="0.01" name="precio" class="form-control requerido txtPrecioGeneral" value="<?= formatearLabel($producto["precio"]) ?>">
				</div>
				<div class="form-group col-12 col-md-8">
					<label>&nbsp;</label>
					<div class="custom-control custom-checkbox mt-2">
						<input type="checkbox" class="custom-control-input" id="chkPrecioVariable" <?= ((int) $producto["precio_variable"] === 1) ? "checked" : "" ?>>
						<label class="custom-control-label" for="chkPrecioVariable">Configurar precio distinto por sucursal</label>
					</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-12">
					<label>Sucursales donde estara disponible <strong class="text-danger">*</strong></label>
					<?php if (empty($sucursalesCatalogo)) { ?>
						<div class="alert alert-warning m-0">No tienes sucursales asignadas; no puedes registrar productos.</div>
					<?php } else { ?>
						<div class="table-responsive border rounded">
							<table class="table table-sm mb-0" id="tablaSucursalesProducto">
								<thead>
									<tr>
										<th style="width: 40px;"></th>
										<th>Sucursal</th>
										<th style="width: 140px;">Unidades</th>
										<th style="width: 160px;">Precio</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($sucursalesCatalogo as $sucursal) {
										$idsucursal = (int) $sucursal["idsucursal"];
										$asignacion = $producto["sucursales"][$idsucursal] ?? null;
										$marcado = ($asignacion !== null);
										$unidades = $marcado ? $asignacion["unidades"] : 0;
										$precioSucursal = $marcado ? $asignacion["precio"] : "";
									?>
										<tr>
											<td>
												<div class="custom-control custom-checkbox">
													<input
														type="checkbox"
														class="custom-control-input chkSucursalProducto"
														id="sucursal<?= $idsucursal ?>"
														name="sucursales[]"
														value="<?= $idsucursal ?>"
														<?= $marcado ? "checked" : "" ?>
													>
													<label class="custom-control-label" for="sucursal<?= $idsucursal ?>"></label>
												</div>
											</td>
											<td><?= formatearLabel($sucursal["nombre"]) ?></td>
											<td>
												<input
													type="number"
													min="0"
													step="1"
													class="form-control form-control-sm txtUnidadesSucursal"
													name="unidades[<?= $idsucursal ?>]"
													value="<?= formatearLabel($unidades) ?>"
												>
											</td>
											<td>
												<input
													type="number"
													min="0.01"
													step="0.01"
													class="form-control form-control-sm txtPrecioSucursal"
													name="precio_sucursal[<?= $idsucursal ?>]"
													value="<?= formatearLabel($precioSucursal) ?>"
												>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					<?php } ?>
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
						onclick="intentarGuardarProducto()"
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
		inicializarFancyX("#fancyProducto");

		actualizarColumnaUnidades();
		actualizarColumnaPrecio();

		$(".btnTipoProducto").on("click", function () {
			$(".btnTipoProducto").removeClass("active");
			$(this).addClass("active");
			$("#servicio").val($(this).data("servicio"));
			actualizarColumnaUnidades();
		});

		$("#chkPrecioVariable").on("change", function () {
			$("#precio_variable").val($(this).is(":checked") ? "1" : "0");
			actualizarColumnaPrecio();
		});
	});

	function actualizarColumnaUnidades() {
		var esServicio = $("#servicio").val() == "1";
		$(".txtUnidadesSucursal").prop("disabled", esServicio);
		if (esServicio)
			$(".txtUnidadesSucursal").val(0);
	}

	function actualizarColumnaPrecio() {
		var precioVariable = $("#precio_variable").val() == "1";
		$(".txtPrecioSucursal").prop("disabled", !precioVariable);

		var $precioGeneral = $(".txtPrecioGeneral");
		$precioGeneral.prop("disabled", precioVariable);
		$precioGeneral.toggleClass("requerido", !precioVariable);
		$("#reqPrecioGeneral").toggle(!precioVariable);
		if (precioVariable)
			$precioGeneral.val("");
	}

	function intentarGuardarProducto() {
		if ($(".chkSucursalProducto:checked").length == 0) {
			parent.Swal.fire("Atencion", "Selecciona al menos una sucursal.", "warning");
			return;
		}

		var precioVariable = $("#precio_variable").val() == "1";
		var esServicio = $("#servicio").val() == "1";
		var valido = true;

		if (!precioVariable) {
			var precioGeneral = parseFloat($(".txtPrecioGeneral").val());
			if (isNaN(precioGeneral) || precioGeneral <= 0) {
				parent.Swal.fire("Atencion", "Captura un precio general valido.", "warning");
				return;
			}
		}

		$(".chkSucursalProducto:checked").each(function () {
			var fila = $(this).closest("tr");

			if (precioVariable) {
				var precio = parseFloat(fila.find(".txtPrecioSucursal").val());
				if (isNaN(precio) || precio <= 0) {
					valido = false;
					return false;
				}
			}

			if (!esServicio) {
				var unidades = parseInt(fila.find(".txtUnidadesSucursal").val());
				if (isNaN(unidades) || unidades < 0) {
					valido = false;
					return false;
				}
			}
		});

		if (!valido) {
			parent.Swal.fire("Atencion", "Revisa el precio y las unidades capturadas en las sucursales seleccionadas.", "warning");
			return;
		}

		guardar("frmProducto", "productos");
	}
</script>
