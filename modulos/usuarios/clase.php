<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Usuarios extends BaseClass {

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

	/**
	 * Un administrador puede ver este modulo si es admin=1, o si tiene
	 * asignada explicitamente la opcion cuyo url es "usuarios". El sidebar
	 * de home.php ya filtra visualmente con la misma regla, pero el acceso
	 * real (procesos.php/lista.php/agregar.php) debe validarse aqui tambien
	 * para no depender solo de que el menu este oculto.
	 */
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
			o.url = 'usuarios'
		limit 1
		";
		$params = array($idadministrador);
		$fila = $this->claseQueries->fetchResults($query, $params, false);

		return !empty($fila);
	}

	public function getUsuarios() {
		$query = "
		select
			idadministrador,
			nombre,
			paterno,
			materno,
			correo,
			admin,
			registro
		from
			tadministradores
		where
			status = 1
		order by
			nombre, paterno
		";
		return $this->claseQueries->fetchResults($query);
	}

	public function getUsuario($idadministrador) {
		$query = "
		select
			idadministrador,
			nombre,
			paterno,
			materno,
			correo,
			admin
		from
			tadministradores
		where
			idadministrador = ?
		";
		$params = array($idadministrador);
		$usuario = $this->claseQueries->fetchResults($query, $params, false, "No se encontro el usuario", false, false, "", false, false);

		$query = "select idopcion from tradministradoropciones where idadministrador = ?";
		$opciones = $this->claseQueries->fetchResults($query, $params);
		$usuario["opciones"] = array_column($opciones, "idopcion");

		$query = "select idsucursal from tradminsucursales where idadministrador = ?";
		$sucursales = $this->claseQueries->fetchResults($query, $params);
		$usuario["sucursales"] = array_column($sucursales, "idsucursal");

		return $usuario;
	}

	public function getOpciones() {
		$query = "select idopcion, nombre from topciones order by nombre";
		return $this->claseQueries->fetchResults($query);
	}

	public function getSucursales() {
		$query = "select idsucursal, nombre from tsucursales where status = 1 order by nombre";
		return $this->claseQueries->fetchResults($query);
	}

	private function existeCorreoDuplicado($correo, $idadministrador = null) {
		$query = "select idadministrador from tadministradores where correo = ?";
		$params = array($correo);
		if (!empty($idadministrador)) {
			$query .= " and idadministrador != ?";
			$params[] = $idadministrador;
		}
		$fila = $this->claseQueries->fetchResults($query, $params, false);
		return !empty($fila);
	}

	/**
	 * True si $idadministrador es el unico administrador (admin=1, status=1)
	 * que queda activo en el sistema.
	 */
	private function esUnicoAdminActivo($idadministrador) {
		$query = "select count(*) as total from tadministradores where admin = 1 and status = 1 and idadministrador != ?";
		$params = array($idadministrador);
		$fila = $this->claseQueries->fetchResults($query, $params, false);
		return ((int) $fila["total"]) === 0;
	}

	private function validarDatosBase($post) {
		$nombre = trim($post["nombre"] ?? "");
		$paterno = trim($post["paterno"] ?? "");
		$correo = trim($post["correo"] ?? "");

		if ($nombre === "" || $paterno === "" || $correo === "")
			throw new Exception("Nombre, apellido paterno y correo son obligatorios.|Atencion|mensaje|warning", 1);

		if (!filter_var($correo, FILTER_VALIDATE_EMAIL))
			throw new Exception("El correo no tiene un formato valido.|Atencion|mensaje|warning", 1);
	}

	/**
	 * Filtra los ids recibidos contra el catalogo real y valida el minimo
	 * requerido cuando el usuario no es administrador.
	 */
	private function resolverOpcionesYSucursales($post, $esAdmin) {
		if ($esAdmin)
			return array(array(), array());

		$opciones = array_map("intval", (array) ($post["opciones"] ?? array()));
		$sucursales = array_map("intval", (array) ($post["sucursales"] ?? array()));

		$opcionesValidas = array_column($this->getOpciones(), "idopcion");
		$sucursalesValidas = array_column($this->getSucursales(), "idsucursal");

		$opciones = array_values(array_intersect($opciones, $opcionesValidas));
		$sucursales = array_values(array_intersect($sucursales, $sucursalesValidas));

		if (empty($opciones))
			throw new Exception("Selecciona al menos una opcion para un usuario que no es administrador.|Atencion|mensaje|warning", 1);

		if (empty($sucursales))
			throw new Exception("Selecciona al menos una sucursal para un usuario que no es administrador.|Atencion|mensaje|warning", 1);

		return array($opciones, $sucursales);
	}

	private function guardarOpciones($idadministrador, $opciones) {
		$this->claseQueries->executeQuery("delete from tradministradoropciones where idadministrador = ?", array($idadministrador));
		foreach ($opciones as $idopcion) {
			$this->claseQueries->executeQuery(
				"insert into tradministradoropciones (idadministrador, idopcion) values (?, ?)",
				array($idadministrador, $idopcion)
			);
		}
	}

	private function guardarSucursales($idadministrador, $sucursales) {
		$this->claseQueries->executeQuery("delete from tradminsucursales where idadministrador = ?", array($idadministrador));
		foreach ($sucursales as $idsucursal) {
			$this->claseQueries->executeQuery(
				"insert into tradminsucursales (idadministrador, idsucursal) values (?, ?)",
				array($idadministrador, $idsucursal)
			);
		}
	}

	public function agregarUsuario($post) {
		$this->validarDatosBase($post);

		$nombre = trim($post["nombre"]);
		$paterno = trim($post["paterno"]);
		$materno = trim($post["materno"] ?? "");
		$correo = trim($post["correo"]);
		$password = $post["password"] ?? "";
		$admin = (($post["admin"] ?? "0") == "1") ? 1 : 0;

		if ($password === "" || strlen($password) < 6)
			throw new Exception("La contrasena es obligatoria y debe tener al menos 6 caracteres.|Atencion|mensaje|warning", 1);

		if ($this->existeCorreoDuplicado($correo))
			throw new Exception("Ya existe un usuario registrado con ese correo.|Atencion|mensaje|warning", 1);

		list($opciones, $sucursales) = $this->resolverOpcionesYSucursales($post, $admin === 1);

		$query = "
		insert into tadministradores
			(nombre, paterno, materno, correo, password, admin, status)
		values
			(?, ?, ?, ?, AES_ENCRYPT(?, '" . SEED_ADMINISTRADORES . "'), ?, 1)
		";
		$params = array($nombre, $paterno, $materno, $correo, $password, $admin);
		$idadministrador = $this->claseQueries->executeQuery($query, $params, true, "No se pudo guardar el usuario");

		if ($admin === 0) {
			$this->guardarOpciones($idadministrador, $opciones);
			$this->guardarSucursales($idadministrador, $sucursales);
		}

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Usuario guardado correctamente.",
			"texto" => "Usuario guardado correctamente."
		);
	}

	public function editarUsuario($post) {
		$idadministrador = (int) ($post["id"] ?? 0);
		if ($idadministrador <= 0)
			throw new Exception("No se especifico el usuario a editar.|Atencion|mensaje|warning", 1);

		$this->validarDatosBase($post);

		$nombre = trim($post["nombre"]);
		$paterno = trim($post["paterno"]);
		$materno = trim($post["materno"] ?? "");
		$correo = trim($post["correo"]);
		$password = trim($post["password"] ?? "");
		$admin = (($post["admin"] ?? "0") == "1") ? 1 : 0;

		if ($password !== "" && strlen($password) < 6)
			throw new Exception("La contrasena debe tener al menos 6 caracteres.|Atencion|mensaje|warning", 1);

		if ($this->existeCorreoDuplicado($correo, $idadministrador))
			throw new Exception("Ya existe otro usuario registrado con ese correo.|Atencion|mensaje|warning", 1);

		if ($admin === 0 && $this->esUnicoAdminActivo($idadministrador))
			throw new Exception("No puedes quitar el rol de administrador: es el unico administrador activo del sistema.|Atencion|mensaje|warning", 1);

		list($opciones, $sucursales) = $this->resolverOpcionesYSucursales($post, $admin === 1);

		$query = "
		update tadministradores set
			nombre = ?,
			paterno = ?,
			materno = ?,
			correo = ?,
			admin = ?" . (($password !== "") ? ", password = AES_ENCRYPT(?, '" . SEED_ADMINISTRADORES . "')" : "") . "
		where
			idadministrador = ?
		";
		$params = array($nombre, $paterno, $materno, $correo, $admin);
		if ($password !== "")
			$params[] = $password;
		$params[] = $idadministrador;

		$this->claseQueries->executeQuery($query, $params, false, "No se pudo actualizar el usuario");

		if ($admin === 1) {
			$this->guardarOpciones($idadministrador, array());
			$this->guardarSucursales($idadministrador, array());
		} else {
			$this->guardarOpciones($idadministrador, $opciones);
			$this->guardarSucursales($idadministrador, $sucursales);
		}

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Usuario actualizado correctamente.",
			"texto" => "Usuario actualizado correctamente."
		);
	}

	public function eliminarUsuario($post) {
		$idadministrador = (int) ($post["id"] ?? 0);
		if ($idadministrador <= 0)
			throw new Exception("No se especifico el usuario a eliminar.|Atencion|mensaje|warning", 1);

		if ($idadministrador === (int) ($_SESSION["infoUsuario"]["idadministrador"] ?? 0))
			throw new Exception("No puedes eliminar tu propia cuenta.|Atencion|mensaje|warning", 1);

		$query = "select admin, status from tadministradores where idadministrador = ?";
		$params = array($idadministrador);
		$usuario = $this->claseQueries->fetchResults($query, $params, false, "No se encontro el usuario", false, false, "", false, false);

		if ((int) $usuario["admin"] === 1 && $this->esUnicoAdminActivo($idadministrador))
			throw new Exception("No puedes eliminar al unico administrador activo del sistema.|Atencion|mensaje|warning", 1);

		$this->claseQueries->executeQuery("update tadministradores set status = 0 where idadministrador = ?", $params, false, "No se pudo eliminar el usuario");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"texto" => "Usuario eliminado correctamente."
		);
	}
}
?>
