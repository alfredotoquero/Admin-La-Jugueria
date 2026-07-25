<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Cortes extends BaseClass {

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
			o.url = 'cortes'
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
	 * Listado de cortes visible para el usuario actual: solo los de sus
	 * sucursales (todas si es admin), con filtros opcionales de sucursal,
	 * usuario y rango de fecha (sobre fechainicio).
	 */
	public function getCortes($idadministrador, $filtros = array()) {
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
			$condiciones .= " and c.fechainicio >= ?";
			$params[] = $fechaDesde;
		}
		if ($fechaHasta !== "") {
			$condiciones .= " and c.fechainicio <= ?";
			$params[] = $fechaHasta;
		}

		$query = "
		select
			c.idcorte,
			c.idsucursal,
			s.nombre as sucursal,
			c.idusuario,
			u.nombre as usuario_nombre,
			u.apaterno as usuario_apaterno,
			u.amaterno as usuario_amaterno,
			c.fechainicio,
			c.horainicio,
			c.fechafinal,
			c.horafinal,
			c.folioinicial,
			c.foliofinal,
			c.fondoinicial,
			c.gastos,
			c.ventas,
			c.fondofinal,
			c.z,
			c.status
		from
			tcortes c
		inner join
			tsucursales s on s.idsucursal = c.idsucursal
		left join
			tusuarios u on u.idusuario = c.idusuario
		where
			c.idsucursal in ($placeholders)
			$condiciones
		order by
			c.fechainicio desc, c.horainicio desc
		";

		return $this->claseQueries->fetchResults($query, $params);
	}
}
?>
