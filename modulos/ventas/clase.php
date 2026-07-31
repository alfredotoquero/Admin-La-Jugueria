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
	 * Filtros opcionales de sucursal, usuario y rango de fecha (sobre la
	 * fecha de la cuenta, no la del corte).
	 */
	public function getVentasPorProducto($idadministrador, $filtros = array()) {
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (empty($sucursalesUsuario))
			return array();

		$idsucursalFiltro = (int) ($filtros["idsucursal"] ?? 0);
		$idusuarioFiltro = (int) ($filtros["idusuario"] ?? 0);
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
		if ($fechaDesde !== "") {
			$condiciones .= " and cu.fecha >= ?";
			$params[] = $fechaDesde;
		}
		if ($fechaHasta !== "") {
			$condiciones .= " and cu.fecha <= ?";
			$params[] = $fechaHasta;
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
}
?>
