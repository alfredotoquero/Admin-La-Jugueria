<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Cajeros extends BaseClass {

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
			o.url = 'cajeros'
		limit 1
		";
		$params = array($idadministrador);
		$fila = $this->claseQueries->fetchResults($query, $params, false);

		return !empty($fila);
	}

	/**
	 * Sucursales que el usuario actual puede ver/asignar: todas las activas si
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
	 * El selector/columna/filtro de sucursal solo se muestra si el usuario es
	 * admin (sin importar cuantas sucursales haya) o si tiene mas de una
	 * sucursal asignada; con una sola sucursal no tiene sentido mostrarlo.
	 */
	public function mostrarSelectorSucursal($idadministrador) {
		if (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1)
			return true;

		return count($this->getSucursalesUsuario($idadministrador)) > 1;
	}

	/**
	 * Listado de cajeros visible para el usuario actual: solo los de sus
	 * sucursales permitidas (todas si es admin), con filtro opcional a una
	 * sola sucursal.
	 */
	public function getCajeros($idadministrador, $idsucursalFiltro = 0) {
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (empty($sucursalesUsuario))
			return array();

		if ($idsucursalFiltro > 0 && !in_array($idsucursalFiltro, $sucursalesUsuario))
			$idsucursalFiltro = 0;

		$placeholders = implode(",", array_fill(0, count($sucursalesUsuario), "?"));
		$query = "
		select
			u.idusuario,
			u.nombre,
			u.apaterno,
			u.amaterno,
			u.usuario,
			u.idsucursal,
			s.nombre as sucursal
		from
			tusuarios u
		inner join
			tsucursales s on s.idsucursal = u.idsucursal
		where
			u.status = 'A' and
			u.idsucursal in ($placeholders)
			" . (($idsucursalFiltro > 0) ? " and u.idsucursal = ?" : "") . "
		order by
			u.nombre, u.apaterno
		";
		$params = $sucursalesUsuario;
		if ($idsucursalFiltro > 0)
			$params[] = $idsucursalFiltro;

		return $this->claseQueries->fetchResults($query, $params);
	}

	/**
	 * Obtiene un cajero por id, validando que su sucursal este dentro de las
	 * permitidas para el usuario actual (si no, se trata como no encontrado).
	 */
	public function getCajero($idusuario, $idadministrador) {
		$query = "select idusuario, idsucursal, nombre, apaterno, amaterno, usuario from tusuarios where idusuario = ?";
		$params = array($idusuario);
		$cajero = $this->claseQueries->fetchResults($query, $params, false, "No se encontro el cajero", false, false, "", false, false);

		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (!in_array((int) $cajero["idsucursal"], $sucursalesUsuario))
			throw new Exception("No se encontro el cajero.|Atencion|mensaje|warning", 1);

		return $cajero;
	}

	private function existeUsuarioDuplicado($usuario, $idsucursal, $idusuarioExcluir = null) {
		$query = "select idusuario from tusuarios where usuario = ? and idsucursal = ?";
		$params = array($usuario, $idsucursal);
		if (!empty($idusuarioExcluir)) {
			$query .= " and idusuario != ?";
			$params[] = $idusuarioExcluir;
		}
		$fila = $this->claseQueries->fetchResults($query, $params, false);
		return !empty($fila);
	}

	private function validarDatosBase($post) {
		$nombre = trim($post["nombre"] ?? "");
		$apaterno = trim($post["apaterno"] ?? "");
		$usuario = trim($post["usuario"] ?? "");

		if ($nombre === "" || $apaterno === "" || $usuario === "")
			throw new Exception("Nombre, apellido paterno y usuario son obligatorios.|Atencion|mensaje|warning", 1);
	}

	/**
	 * El idsucursal recibido debe estar entre las sucursales permitidas del
	 * usuario actual. Nunca se confia en el valor del POST.
	 */
	private function resolverSucursal($post, $sucursalesPermitidas) {
		$idsucursal = (int) ($post["idsucursal"] ?? 0);
		if ($idsucursal <= 0 || !in_array($idsucursal, $sucursalesPermitidas))
			throw new Exception("Selecciona una sucursal a la que tengas acceso.|Atencion|mensaje|warning", 1);
		return $idsucursal;
	}

	public function agregarCajero($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
		$sucursalesPermitidas = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");

		$this->validarDatosBase($post);

		$nombre = trim($post["nombre"]);
		$apaterno = trim($post["apaterno"]);
		$amaterno = trim($post["amaterno"] ?? "");
		$usuario = trim($post["usuario"]);
		$password = $post["password"] ?? "";

		if ($password === "" || strlen($password) < 6)
			throw new Exception("La contrasena es obligatoria y debe tener al menos 6 caracteres.|Atencion|mensaje|warning", 1);

		$idsucursal = $this->resolverSucursal($post, $sucursalesPermitidas);

		if ($this->existeUsuarioDuplicado($usuario, $idsucursal))
			throw new Exception("Ya existe un cajero registrado con ese usuario en esta sucursal.|Atencion|mensaje|warning", 1);

		$query = "
		insert into tusuarios
			(idsucursal, nombre, apaterno, amaterno, usuario, password, status)
		values
			(?, ?, ?, ?, ?, AES_ENCRYPT(?, '" . SEED_CAJEROS . "'), 'A')
		";
		$params = array($idsucursal, $nombre, $apaterno, $amaterno, $usuario, $password);
		$this->claseQueries->executeQuery($query, $params, true, "No se pudo guardar el cajero");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Cajero guardado correctamente.",
			"texto" => "Cajero guardado correctamente."
		);
	}

	public function editarCajero($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
		$sucursalesPermitidas = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");

		$idusuario = (int) ($post["id"] ?? 0);
		if ($idusuario <= 0)
			throw new Exception("No se especifico el cajero a editar.|Atencion|mensaje|warning", 1);

		$this->getCajero($idusuario, $idadministrador);

		$this->validarDatosBase($post);

		$nombre = trim($post["nombre"]);
		$apaterno = trim($post["apaterno"]);
		$amaterno = trim($post["amaterno"] ?? "");
		$usuario = trim($post["usuario"]);
		$password = trim($post["password"] ?? "");

		if ($password !== "" && strlen($password) < 6)
			throw new Exception("La contrasena debe tener al menos 6 caracteres.|Atencion|mensaje|warning", 1);

		$idsucursal = $this->resolverSucursal($post, $sucursalesPermitidas);

		if ($this->existeUsuarioDuplicado($usuario, $idsucursal, $idusuario))
			throw new Exception("Ya existe otro cajero registrado con ese usuario en esta sucursal.|Atencion|mensaje|warning", 1);

		$query = "
		update tusuarios set
			idsucursal = ?,
			nombre = ?,
			apaterno = ?,
			amaterno = ?,
			usuario = ?" . (($password !== "") ? ", password = AES_ENCRYPT(?, '" . SEED_CAJEROS . "')" : "") . "
		where
			idusuario = ?
		";
		$params = array($idsucursal, $nombre, $apaterno, $amaterno, $usuario);
		if ($password !== "")
			$params[] = $password;
		$params[] = $idusuario;

		$this->claseQueries->executeQuery($query, $params, false, "No se pudo actualizar el cajero");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Cajero actualizado correctamente.",
			"texto" => "Cajero actualizado correctamente."
		);
	}

	public function eliminarCajero($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];

		$idusuario = (int) ($post["id"] ?? 0);
		if ($idusuario <= 0)
			throw new Exception("No se especifico el cajero a eliminar.|Atencion|mensaje|warning", 1);

		$this->getCajero($idusuario, $idadministrador);

		$this->claseQueries->executeQuery("update tusuarios set status = 'I' where idusuario = ?", array($idusuario), false, "No se pudo eliminar el cajero");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"texto" => "Cajero eliminado correctamente."
		);
	}
}
?>
