<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");
class Permisos extends BaseClass {

	private $con, $isDebugger;
	protected $claseQueries;

	public function __construct($con = null, $pdo = null) {
		if ($con === null)
			include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
		include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/queries.php");
		$this->con = $con;
		$this->isDebugger = $_SESSION["infoUsuario"]["debugger"] ?? 0;
		$this->claseQueries = new Queries($con, $pdo, $this->isDebugger);
		register_shutdown_function(array($this, "handleFatalError"));
	}

	public function isDebugger() {
		return $this->isDebugger;
	}

	/**
	 * Verifica si un rol (idtipousuario/idrol) tiene un permiso CRUD
	 * específico sobre un módulo, según la tabla trmodulosrolespermisos.
	 * C = agregar, R = consultar, U = actualizar, D = eliminar.
	 */
	public function verificarPermisoRol($idmodulo, $idrol, $tipopermiso) {
		$permiso = "";
		if ($tipopermiso == 'C') $permiso .= " AND agregar    = 1";
		if ($tipopermiso == 'R') $permiso .= " AND consultar  = 1";
		if ($tipopermiso == 'U') $permiso .= " AND actualizar = 1";
		if ($tipopermiso == 'D') $permiso .= " AND eliminar   = 1";

		$query = "SELECT * FROM trmodulosrolespermisos WHERE idmodulo = '$idmodulo' AND idrol = '$idrol' $permiso";
		$result = mysqli_query($this->con, $query);

		return (mysqli_num_rows($result) == 1);
	}

	/**
	 * Resuelve el idmodulo de tcatmodulos a partir del nombre "camelCase"
	 * usado en la URL (ej. "configuracion" -> "Configuracion") y lo guarda
	 * en sesión para referencia posterior.
	 */
	public function getIdModulo($modulo) {
		$modulo = ucwords(implode(' ', preg_split('/(?=[A-Z])/', $modulo)));
		$query = "SELECT idmodulo FROM tcatmodulos WHERE lower(modulo) = lower('$modulo')";
		$result = mysqli_query($this->con, $query);
		$r = mysqli_fetch_array($result);
		$_SESSION["permisoUsuario"]["idmodulo"] = $r["idmodulo"];
	}

	/**
	 * Verifica si un usuario tiene un permiso puntual (por nombre de
	 * permiso, tabla tcatpermisos). idtipousuario 1 = superadmin (acceso
	 * total siempre), 2 = admin de empresa (acceso total salvo que se pida
	 * $restringido = 1).
	 */
	public function tienePermiso($idusuario, $permiso, $restringido = 0) {
		$query = "SELECT * from tusuarios where idusuario = '$idusuario'";

		$result = mysqli_query($this->con, $query);
		$datos = mysqli_fetch_array($result);

		if (($datos["idtipousuario"] == 2 or $datos["idtipousuario"] == 1) && $restringido == 0) {
			return true;
		} elseif ($datos["idtipousuario"] == 1) {
			return true;
		} else {
			$query = "SELECT a.*, b.idmodulo, b.permiso, b.icon
				from trusuariopermisos a
				left join tcatpermisos b on a.idpermiso = b.idpermiso
				where a.idusuario = '$idusuario' and b.permiso = '$permiso'";
			$result = mysqli_query($this->con, $query);
			if (mysqli_num_rows($result) > 0) {
				return mysqli_fetch_array($result);
			} else {
				return false;
			}
		}
	}

	/**
	 * Igual que tienePermiso() pero verificando una lista de permisos de
	 * una sola vez (evita N consultas cuando necesitas checar varios
	 * permisos para armar, por ejemplo, una pantalla).
	 */
	public function tienePermisos($idusuario, $permisos, $restringido = 0) {
		$query = "
		select
			idtipousuario
		from
			tusuarios
		where
			idusuario = ?
		";
		$params = array($idusuario);
		$usuario = $this->claseQueries->fetchResults($query, $params, false);

		$idtipousuario = (int)($usuario["idtipousuario"] ?? 0);

		if ($idtipousuario === 1 || ($idtipousuario === 2 && $restringido == 0))
			return array_fill_keys($permisos, true);

		$respuesta = array_fill_keys($permisos, false);

		if (empty($permisos))
			return $respuesta;

		$placeholders = implode(",", array_fill(0, count($permisos), "?"));
		$query = "
		select
			a.*,
			b.idmodulo,
			b.permiso,
			b.icon
		from
			trusuariopermisos a
		left join tcatpermisos b on
			a.idpermiso = b.idpermiso
		where
			a.idusuario = ?
			and b.permiso in ($placeholders)
		";
		$params = array_merge(array($idusuario), $permisos);
		$rows = $this->claseQueries->fetchResults($query, $params);

		foreach ($rows as $row) {
			$respuesta[$row["permiso"]] = $row;
		}

		return $respuesta;
	}

	/**
	 * Ejemplo de "permiso a nivel de campo": ¿este usuario puede ver
	 * contraseñas en texto plano en algún catálogo? Ajusta o elimina según
	 * tus propias reglas de negocio.
	 */
	public function tienePermisoEditarContra($idusuario) {
		$query = "SELECT * FROM tusuarios
				WHERE idusuario = '$idusuario'
				AND ver_contrasenias = '1'";

		$result = mysqli_query($this->con, $query);

		return ($result && mysqli_num_rows($result) > 0);
	}

	public function verificarPermiso($idusuario, $permiso, $restringido = 0) {
		$query = "SELECT * from tusuarios where idusuario = '$idusuario'";

		$result = mysqli_query($this->con, $query);
		$datos = mysqli_fetch_array($result);

		if (($datos["idtipousuario"] == 2 or $datos["idtipousuario"] == 1) && $restringido == 0) {
			return true;
		} elseif ($datos["idtipousuario"] == 1) {
			return true;
		} else {
			$query = "SELECT a.*, b.idmodulo, b.permiso, b.icon
				from trusuariopermisos a
				left join tcatpermisos b on a.idpermiso = b.idpermiso
				where a.idusuario = $idusuario and b.permiso = '$permiso'";

			$result = mysqli_query($this->con, $query);
			if (mysqli_num_rows($result) > 0) {
				return mysqli_fetch_array($result);
			} else {
				return false;
			}
		}
	}

	/**
	 * Verifica si una empresa tiene contratado un módulo (por nombre).
	 */
	public function empresaModulo($idempresa, $modulo) {
		$query = "SELECT a.*, b.idmodulo, b.modulo
					from trempresasmodulos a
					left join tcatmodulos  b on a.idmodulo  = b.idmodulo
					where a.idempresa  = '$idempresa' and b.modulo  = '$modulo'
					";
		$result = mysqli_query($this->con, $query);
		if (mysqli_num_rows($result) > 0) {
			return mysqli_fetch_array($result);
		} else {
			return false;
		}
	}

	/**
	 * Devuelve las demás empresas (tenants) a las que un mismo correo tiene
	 * acceso, distintas de la empresa actual. Es la base del selector
	 * "Cambiar Empresa" del navbar en home.php.
	 */
	public function getEmpresasPorUsuario($idempresa, $correo) {
		$sql = "SELECT e.idempresa,e.alias,e.razon_social,e.codigo
		from tusuarios u
		INNER JOIN tempresas e on e.idempresa  = u.idempresa
		where e.idempresa != '$idempresa' and u.correo='$correo' and u.status = 1
		order by e.razon_social ASC";
		$result = mysqli_query($this->con, $sql);
		$empresas = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$empresas[] = $row;
		}
		return $empresas;
	}
}
