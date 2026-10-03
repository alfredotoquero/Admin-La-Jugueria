<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Ventas extends BaseClass {

	private $con, $isDebugger;
	protected $claseQueries;

	public function __construct($con = null, $pdo = null) {
		if ($con === null)
			include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
		include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/queries.php");
		include_once($_SERVER["DOCUMENT_ROOT"] . "/config/environment.php");
		$this->con = $con;
		$this->isDebugger = $_SESSION["infoUsuario"]["debugger"] ?? 0;
		$this->claseQueries = new Queries($con, $pdo, $this->isDebugger);
		register_shutdown_function(array($this, "handleFatalError"));
	}

	public function isDebugger() {
		return $this->isDebugger;
	}

	public function tieneAccesoModulo($idadministrador) {
		if (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1)
			return true;

		$query = "
		select
			1
		from
			tradministradoropciones ao
		inner join
			topciones o on o.idopcion = ao.idopcion
		where
			ao.idadministrador = ? and
			o.url = 'ventas'
		limit 1
		";
		$params = array($idadministrador);
		$fila = $this->claseQueries->fetchResults($query, $params, false);

		return !empty($fila);
	}

	/**
	 * Sucursales que el usuario actual puede consultar: todas las activas si
	 * es admin, o solo las que tiene explicitamente en tradminsucursales.
	 */
	public function getSucursalesUsuario($idadministrador) {
		if (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1) {
			$query = "select idsucursal, nombre from tsucursales where status = 1 order by nombre";
			return $this->claseQueries->fetchResults($query);
		}

		$query = "
		select
			s.idsucursal,
			s.nombre
		from
			tsucursales s
		inner join
			tradminsucursales a on a.idsucursal = s.idsucursal
		where
			a.idadministrador = ? and
			s.status = 1
		order by
			s.nombre
		";
		return $this->claseQueries->fetchResults($query, array($idadministrador));
	}

	/**
	 * Usuarios del POS (tusuarios) que tienen al menos un corte registrado en
	 * la sucursal indicada. No existe un catalogo directo usuario-sucursal;
	 * se deriva de tcortes. Solo devuelve datos si la sucursal esta dentro
	 * del alcance del administrador actual.
	 */
	public function getUsuariosPorSucursal($idsucursal, $idadministrador) {
		$idsucursal = (int) $idsucursal;
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if ($idsucursal <= 0 || !in_array($idsucursal, $sucursalesUsuario))
			return array();

		$query = "
		select distinct
			u.idusuario,
			u.nombre,
			u.apaterno,
			u.amaterno
		from
			tcortes c
		inner join
			tusuarios u on u.idusuario = c.idusuario
		where
			c.idsucursal = ?
		order by
			u.nombre, u.apaterno
		";
		return $this->claseQueries->fetchResults($query, array($idsucursal));
	}

	/**
	 * Resumen de ventas por producto: cantidad vendida e importe total,
	 * agrupado a partir de las lineas de venta (trcuentaproductos) de las
	 * cuentas (tcuentas) de los cortes visibles para el usuario actual.
	 * Filtros opcionales de sucursal, usuario y fecha. El filtro de fecha
	 * depende de "tipofiltro":
	 *   - "rango" (default): rango sobre la fecha de la cuenta (cu.fecha).
	 *   - "corte": solo las ventas de cortes que iniciaron en "fechadesde"
	 *     (c.fechainicio), ignorando "fechahasta".
	 */
	public function getVentasPorProducto($idadministrador, $filtros = array()) {
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (empty($sucursalesUsuario))
			return array();

		$idsucursalFiltro = (int) ($filtros["idsucursal"] ?? 0);
		$idusuarioFiltro = (int) ($filtros["idusuario"] ?? 0);
		$tipoFiltro = (trim($filtros["tipofiltro"] ?? "") === "corte") ? "corte" : "rango";
		$fechaDesde = trim($filtros["fechadesde"] ?? "");
		$fechaHasta = trim($filtros["fechahasta"] ?? "");

		$placeholders = implode(",", array_fill(0, count($sucursalesUsuario), "?"));
		$params = $sucursalesUsuario;

		$condiciones = "";
		if ($idsucursalFiltro > 0) {
			$condiciones .= " and c.idsucursal = ?";
			$params[] = $idsucursalFiltro;
		}
		if ($idusuarioFiltro > 0) {
			$condiciones .= " and c.idusuario = ?";
			$params[] = $idusuarioFiltro;
		}
		if ($tipoFiltro == "corte") {
			if ($fechaDesde !== "") {
				$condiciones .= " and c.fechainicio = ?";
				$params[] = $fechaDesde;
			}
		} else {
			if ($fechaDesde !== "") {
				$condiciones .= " and cu.fecha >= ?";
				$params[] = $fechaDesde;
			}
			if ($fechaHasta !== "") {
				$condiciones .= " and cu.fecha <= ?";
				$params[] = $fechaHasta;
			}
		}

		$query = "
		select
			p.idproducto,
			p.nombre,
			p.descripcion,
			sum(rp.cantidad) as cantidad,
			sum(rp.cantidad * rp.precio) as total
		from
			trcuentaproductos rp
		inner join
			tcuentas cu on cu.idcuenta = rp.idcuenta
		inner join
			tcortes c on c.idcorte = cu.idcorte
		inner join
			tproductos p on p.idproducto = rp.idproducto
		where
			c.idsucursal in ($placeholders)
			$condiciones
		group by
			p.idproducto, p.nombre, p.descripcion
		order by
			p.nombre
		";

		return $this->claseQueries->fetchResults($query, $params);
	}

	/**
	 * Sucursales del alcance del usuario con los datos que se necesitan para
	 * imprimir: impresora y tamano de impresion (72 o 58 mm).
	 */
	private function getSucursalesImpresion($idadministrador) {
		$ids = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (empty($ids))
			return array();

		$placeholders = implode(",", array_fill(0, count($ids), "?"));
		$query = "
		select
			idsucursal,
			nombre,
			ticket_negocio as negocio,
			ticket_nombreimpresora as nombreimpresora,
			ticket_tamanoimpresion as tamanoimpresion
		from
			tsucursales
		where
			idsucursal in ($placeholders)
		order by
			nombre
		";
		return $this->claseQueries->fetchResults($query, $ids);
	}

	/**
	 * Arma el reporte de ventas por producto como ticket ESC/POS para mandarlo
	 * directo a la impresora termica via QZ Tray (js/impresion.js), igual que
	 * los tickets del punto de venta. El contenido respeta los mismos filtros
	 * (y el mismo alcance) que la lista en pantalla.
	 *
	 * Se imprime con la impresora y el tamano de impresion (72 o 58 mm) de una
	 * sola sucursal, que debe estar en el alcance del usuario:
	 * - Filtro de una sucursal: esa sucursal.
	 * - "TODAS": la sucursal desde la que se imprime ($idsucursalImpresora, la
	 *   elige el usuario en pantalla); si solo tiene una sucursal, esa.
	 *
	 * @return array impresora => nombre de la impresora, datos => ESC/POS en base64
	 */
	public function getTicketReporteVentas($idadministrador, $filtros, $idsucursalImpresora = 0) {
		include_once($_SERVER["DOCUMENT_ROOT"] . "/includes/escpos.php");

		$sucursalesImpresion = array_column($this->getSucursalesImpresion($idadministrador), null, "idsucursal");
		$nombresSucursales = array_column($sucursalesImpresion, "nombre", "idsucursal");

		$idsucursalFiltro = (int) ($filtros["idsucursal"] ?? 0);
		if ($idsucursalFiltro > 0 && !isset($sucursalesImpresion[$idsucursalFiltro]))
			throw new Exception("No tienes acceso a la sucursal seleccionada.|Atencion|mensaje|warning", 1);

		if ($idsucursalFiltro > 0)
			$idsucursalImpresora = $idsucursalFiltro;
		else if (count($sucursalesImpresion) === 1)
			$idsucursalImpresora = (int) array_key_first($sucursalesImpresion);
		else
			$idsucursalImpresora = (int) $idsucursalImpresora;

		if (!isset($sucursalesImpresion[$idsucursalImpresora]))
			throw new Exception("Selecciona la sucursal desde la que vas a imprimir.|Atencion|mensaje|warning", 1);

		$infoticket = $sucursalesImpresion[$idsucursalImpresora];

		if (trim($infoticket["nombreimpresora"] ?? "") === "")
			throw new Exception("La sucursal no tiene configurado el nombre de la impresora.|Atencion|mensaje|warning", 1);

		// El nombre del cajero se resuelve en servidor (no se confia en el texto
		// del combo) y solo entre los usuarios de la sucursal filtrada.
		$usuarioNombre = "TODOS";
		$idusuarioFiltro = (int) ($filtros["idusuario"] ?? 0);
		if ($idusuarioFiltro > 0) {
			foreach ($this->getUsuariosPorSucursal($idsucursalFiltro, $idadministrador) as $usuario) {
				if ((int) $usuario["idusuario"] === $idusuarioFiltro)
					$usuarioNombre = trim($usuario["nombre"] . " " . $usuario["apaterno"] . " " . ($usuario["amaterno"] ?? ""));
			}
		}

		$lista = $this->getVentasPorProducto($idadministrador, $filtros);

		$ancho = anchoTicket($infoticket["tamanoimpresion"]);
		$tipoFiltro = (trim($filtros["tipofiltro"] ?? "") === "corte") ? "corte" : "rango";
		$fechaDesde = trim($filtros["fechadesde"] ?? "");
		$fechaHasta = trim($filtros["fechahasta"] ?? "");

		$escpos = escposInit();
		$escpos .= escposAlign("center");
		$escpos .= escposBold(true).escposTamano(true);
		$escpos .= escposParrafo($infoticket["negocio"], (int) floor($ancho / 2));
		$escpos .= escposTamano(false);
		$escpos .= escposLinea("REPORTE DE VENTAS");
		$escpos .= escposBold(false);
		$escpos .= escposLinea(date("d/m/Y h:i:s a"));
		$escpos .= escposAlign("left");
		$escpos .= escposSeparador($ancho);

		$escpos .= escposParrafo("SUCURSAL: " . (($idsucursalFiltro > 0) ? $nombresSucursales[$idsucursalFiltro] : "TODAS"), $ancho);
		$escpos .= escposParrafo("USUARIO: " . $usuarioNombre, $ancho);
		if ($tipoFiltro == "corte")
			$escpos .= escposParrafo("FECHA DE CORTE: " . fecha_display($fechaDesde), $ancho);
		else
			$escpos .= escposParrafo("PERIODO: " . fecha_display($fechaDesde) . " AL " . fecha_display($fechaHasta), $ancho);
		$escpos .= escposSeparador($ancho);

		$totalCantidad = 0;
		$totalImporte = 0;

		if (empty($lista)) {
			$escpos .= escposParrafo("NO HAY VENTAS REGISTRADAS CON LOS FILTROS SELECCIONADOS.", $ancho);
		} else {
			$escpos .= $this->escposFilaReporte("PRODUCTO", "CANT", "TOTAL", $ancho);
			$escpos .= escposSeparador($ancho);
			foreach ($lista as $venta) {
				$totalCantidad += (float) $venta["cantidad"];
				$totalImporte += (float) $venta["total"];
				$escpos .= $this->escposFilaReporte($venta["nombre"], smart_number_format($venta["cantidad"], 0), "$" . number_format((float) $venta["total"], 2), $ancho);
			}
			$escpos .= escposSeparador($ancho);
			$escpos .= escposFilaMonto("ARTICULOS", smart_number_format($totalCantidad, 0), $ancho);
			$escpos .= escposBold(true);
			$escpos .= escposFilaMonto("TOTAL", "$" . number_format($totalImporte, 2), $ancho);
			$escpos .= escposBold(false);
		}

		$escpos .= escposCorte();

		return array(
			"impresora" => $infoticket["nombreimpresora"],
			"datos" => base64_encode($escpos),
		);
	}

	/**
	 * Fila PRODUCTO | CANT | TOTAL del reporte, con la misma distribucion en
	 * ambos tamanos: la cantidad (6) y el total (11) a la derecha y el resto
	 * para el nombre (25 en 72 mm, 15 en 58 mm). Un nombre largo se parte en
	 * varios renglones dentro de su columna, como en el ticket de venta.
	 */
	private function escposFilaReporte($nombre, $cantidad, $total, $ancho) {
		$escpos = "";
		$numLinea = 1;
		foreach (dividirTexto($nombre, $ancho - 17) as $linea) {
			$escpos .= escposFila(array(
				array($linea, $ancho - 17, "left"),
				array(($numLinea == 1) ? $cantidad : "", 6, "right"),
				array(($numLinea == 1) ? $total : "", 11, "right"),
			));
			$numLinea++;
		}
		return $escpos;
	}
}
?>
