<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Productos extends BaseClass {

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
			o.url = 'productos'
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
	 * Listado de productos visibles para el usuario actual: solo los
	 * disponibles en alguna de sus sucursales (todas si es admin), con
	 * filtro opcional a una sola sucursal.
	 */
	public function getProductos($idadministrador, $idsucursalFiltro = 0) {
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (empty($sucursalesUsuario))
			return array();

		if ($idsucursalFiltro > 0 && !in_array($idsucursalFiltro, $sucursalesUsuario))
			$idsucursalFiltro = 0;

		$placeholders = implode(",", array_fill(0, count($sucursalesUsuario), "?"));
		$query = "
		select distinct
			p.idproducto,
			p.nombre,
			p.descripcion,
			p.precio,
			p.servicio,
			p.precio_variable
		from
			tproductos p
		inner join
			tproductosucursales ps on ps.idproducto = p.idproducto and ps.status = 1
		where
			p.status = 1 and
			ps.idsucursal in ($placeholders)
			" . (($idsucursalFiltro > 0) ? " and ps.idsucursal = ?" : "") . "
		order by
			p.nombre
		";
		$params = $sucursalesUsuario;
		if ($idsucursalFiltro > 0)
			$params[] = $idsucursalFiltro;

		return $this->claseQueries->fetchResults($query, $params);
	}

	/**
	 * Cantidad de sucursales donde esta disponible cada producto (para la
	 * columna "Sucursales" del listado).
	 */
	public function getConteoSucursalesPorProducto() {
		$query = "select idproducto, count(*) as total from tproductosucursales where status = 1 group by idproducto";
		$filas = $this->claseQueries->fetchResults($query);
		$conteo = array();
		foreach ($filas as $fila)
			$conteo[$fila["idproducto"]] = (int) $fila["total"];
		return $conteo;
	}

	public function getProducto($idproducto, $idadministrador) {
		$query = "
		select
			idproducto,
			nombre,
			descripcion,
			precio,
			precio_variable,
			servicio
		from
			tproductos
		where
			idproducto = ?
		";
		$params = array($idproducto);
		$producto = $this->claseQueries->fetchResults($query, $params, false, "No se encontro el producto", false, false, "", false, false);

		$query = "select idsucursal, precio, unidades from tproductosucursales where idproducto = ? and status = 1";
		$asignaciones = $this->claseQueries->fetchResults($query, $params);
		$producto["sucursales"] = array();
		foreach ($asignaciones as $asignacion) {
			$producto["sucursales"][(int) $asignacion["idsucursal"]] = array(
				"precio" => $asignacion["precio"],
				"unidades" => (int) $asignacion["unidades"],
			);
		}

		return $producto;
	}

	private function getSucursalesDeProducto($idproducto) {
		$query = "select idsucursal from tproductosucursales where idproducto = ? and status = 1";
		return $this->claseQueries->fetchResults($query, array($idproducto));
	}

	private function existeNombreDuplicado($nombre, $idproducto = null) {
		$query = "select idproducto from tproductos where nombre = ? and status = 1";
		$params = array($nombre);
		if (!empty($idproducto)) {
			$query .= " and idproducto != ?";
			$params[] = $idproducto;
		}
		$fila = $this->claseQueries->fetchResults($query, $params, false);
		return !empty($fila);
	}

	private function validarDatosBase($post, $precioVariable) {
		$nombre = trim($post["nombre"] ?? "");

		if ($nombre === "")
			throw new Exception("El nombre del producto es obligatorio.|Atencion|mensaje|warning", 1);

		if (!$precioVariable) {
			$precio = $post["precio"] ?? "";
			if ($precio === "" || !is_numeric($precio) || (float) $precio <= 0)
				throw new Exception("El precio general debe ser un numero mayor a cero.|Atencion|mensaje|warning", 1);
		}
	}

	/**
	 * Whitelistea las sucursales recibidas contra las del usuario actual y
	 * valida/normaliza precio y unidades de cada una segun precio_variable y
	 * servicio. Nunca confia en sucursales fuera del acceso del usuario.
	 */
	private function resolverSucursales($post, $idadministrador, $precioVariable, $esServicio) {
		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		$sucursalesRecibidas = array_map("intval", (array) ($post["sucursales"] ?? array()));
		$sucursalesRecibidas = array_values(array_intersect($sucursalesRecibidas, $sucursalesUsuario));

		if (empty($sucursalesRecibidas))
			throw new Exception("Selecciona al menos una sucursal para el producto.|Atencion|mensaje|warning", 1);

		$preciosPost = $post["precio_sucursal"] ?? array();
		$unidadesPost = $post["unidades"] ?? array();

		$seleccion = array();
		foreach ($sucursalesRecibidas as $idsucursal) {
			$precio = null;
			if ($precioVariable) {
				$precio = $preciosPost[$idsucursal] ?? "";
				if ($precio === "" || !is_numeric($precio) || (float) $precio <= 0)
					throw new Exception("Captura un precio valido para cada sucursal seleccionada.|Atencion|mensaje|warning", 1);
				$precio = round((float) $precio, 2);
			}

			$unidades = 0;
			if (!$esServicio) {
				$unidades = $unidadesPost[$idsucursal] ?? "";
				if ($unidades === "" || !is_numeric($unidades) || (int) $unidades < 0)
					throw new Exception("Captura unidades validas para cada sucursal seleccionada.|Atencion|mensaje|warning", 1);
				$unidades = (int) $unidades;
			}

			$seleccion[$idsucursal] = array("precio" => $precio, "unidades" => $unidades);
		}

		return array($sucursalesUsuario, $seleccion);
	}

	/**
	 * Reemplaza las filas de tproductosucursales del producto, pero solo para
	 * las sucursales que el usuario actual tiene permitidas. Las sucursales
	 * del producto fuera de ese conjunto (asignadas por otro usuario con
	 * acceso a otras sucursales) no se tocan.
	 */
	private function guardarSucursalesProducto($idproducto, $sucursalesPermitidas, $seleccion) {
		if (empty($sucursalesPermitidas))
			return;

		$placeholders = implode(",", array_fill(0, count($sucursalesPermitidas), "?"));
		$this->claseQueries->executeQuery(
			"delete from tproductosucursales where idproducto = ? and idsucursal in ($placeholders)",
			array_merge(array($idproducto), $sucursalesPermitidas)
		);

		foreach ($seleccion as $idsucursal => $datos) {
			if (!in_array($idsucursal, $sucursalesPermitidas))
				continue;
			$this->claseQueries->executeQuery(
				"insert into tproductosucursales (idproducto, idsucursal, precio, unidades, status) values (?, ?, ?, ?, 1)",
				array($idproducto, $idsucursal, $datos["precio"], $datos["unidades"])
			);
		}
	}

	public function agregarProducto($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];

		$servicio = (($post["servicio"] ?? "0") == "1") ? 1 : 0;
		$precioVariable = (($post["precio_variable"] ?? "0") == "1") ? 1 : 0;

		$this->validarDatosBase($post, $precioVariable);

		$nombre = trim($post["nombre"]);
		$descripcion = trim($post["descripcion"] ?? "");
		$precio = $precioVariable ? null : round((float) $post["precio"], 2);

		if ($this->existeNombreDuplicado($nombre))
			throw new Exception("Ya existe un producto registrado con ese nombre.|Atencion|mensaje|warning", 1);

		list($sucursalesPermitidas, $seleccion) = $this->resolverSucursales($post, $idadministrador, $precioVariable, $servicio === 1);

		mysqli_begin_transaction($this->con);
		try {
			$query = "
			insert into tproductos
				(nombre, descripcion, precio, precio_variable, servicio, status)
			values
				(?, ?, ?, ?, ?, 1)
			";
			$params = array($nombre, $descripcion, $precio, $precioVariable, $servicio);
			$idproducto = $this->claseQueries->executeQuery($query, $params, true, "No se pudo guardar el producto");

			$this->guardarSucursalesProducto($idproducto, $sucursalesPermitidas, $seleccion);

			mysqli_commit($this->con);
		} catch (Exception $e) {
			mysqli_rollback($this->con);
			throw $e;
		}

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Producto guardado correctamente.",
			"texto" => "Producto guardado correctamente."
		);
	}

	public function editarProducto($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];

		$idproducto = (int) ($post["id"] ?? 0);
		if ($idproducto <= 0)
			throw new Exception("No se especifico el producto a editar.|Atencion|mensaje|warning", 1);

		$servicio = (($post["servicio"] ?? "0") == "1") ? 1 : 0;
		$precioVariable = (($post["precio_variable"] ?? "0") == "1") ? 1 : 0;

		$this->validarDatosBase($post, $precioVariable);

		$nombre = trim($post["nombre"]);
		$descripcion = trim($post["descripcion"] ?? "");
		$precio = $precioVariable ? null : round((float) $post["precio"], 2);

		if ($this->existeNombreDuplicado($nombre, $idproducto))
			throw new Exception("Ya existe otro producto registrado con ese nombre.|Atencion|mensaje|warning", 1);

		list($sucursalesPermitidas, $seleccion) = $this->resolverSucursales($post, $idadministrador, $precioVariable, $servicio === 1);

		mysqli_begin_transaction($this->con);
		try {
			$query = "
			update tproductos set
				nombre = ?,
				descripcion = ?,
				precio = ?,
				precio_variable = ?,
				servicio = ?
			where
				idproducto = ?
			";
			$params = array($nombre, $descripcion, $precio, $precioVariable, $servicio, $idproducto);
			$this->claseQueries->executeQuery($query, $params, false, "No se pudo actualizar el producto");

			$this->guardarSucursalesProducto($idproducto, $sucursalesPermitidas, $seleccion);

			mysqli_commit($this->con);
		} catch (Exception $e) {
			mysqli_rollback($this->con);
			throw $e;
		}

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Producto actualizado correctamente.",
			"texto" => "Producto actualizado correctamente."
		);
	}

	public function eliminarProducto($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];
		$esAdmin = (($_SESSION["infoUsuario"]["admin"] ?? 0) == 1);

		$idproducto = (int) ($post["id"] ?? 0);
		if ($idproducto <= 0)
			throw new Exception("No se especifico el producto a eliminar.|Atencion|mensaje|warning", 1);

		$query = "select idproducto from tproductos where idproducto = ? and status = 1";
		$params = array($idproducto);
		$this->claseQueries->fetchResults($query, $params, false, "No se encontro el producto", false, false, "", false, false);

		if (!$esAdmin) {
			$sucursalesProducto = array_column($this->getSucursalesDeProducto($idproducto), "idsucursal");
			$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
			if (!empty(array_diff($sucursalesProducto, $sucursalesUsuario)))
				throw new Exception("Este producto tambien esta asignado a sucursales a las que no tienes acceso; no puedes eliminarlo.|Atencion|mensaje|warning", 1);
		}

		$this->claseQueries->executeQuery("update tproductos set status = 0 where idproducto = ?", $params, false, "No se pudo eliminar el producto");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"texto" => "Producto eliminado correctamente."
		);
	}
}
?>
