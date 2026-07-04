<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Sucursales extends BaseClass {

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
			o.url = 'sucursales'
		limit 1
		";
		$params = array($idadministrador);
		$fila = $this->claseQueries->fetchResults($query, $params, false);

		return !empty($fila);
	}

	public function getSucursales() {
		$query = "
		select
			idsucursal,
			nombre,
			ticket_nombre,
			ticket_rfc,
			registro
		from
			tsucursales
		where
			status = 1
		order by
			nombre
		";
		return $this->claseQueries->fetchResults($query);
	}

	public function getSucursal($idsucursal) {
		$query = "
		select
			idsucursal,
			nombre,
			ticket_negocio,
			ticket_calle,
			ticket_numero,
			ticket_colonia,
			ticket_codigopostal,
			ticket_ciudad,
			ticket_nombre,
			ticket_rfc,
			ticket_regimen,
			ticket_nombreimpresora
		from
			tsucursales
		where
			idsucursal = ?
		";
		$params = array($idsucursal);
		return $this->claseQueries->fetchResults($query, $params, false, "No se encontro la sucursal", false, false, "", false, false);
	}

	private function existeNombreDuplicado($nombre, $idsucursal = null) {
		$query = "select idsucursal from tsucursales where nombre = ? and status = 1";
		$params = array($nombre);
		if (!empty($idsucursal)) {
			$query .= " and idsucursal != ?";
			$params[] = $idsucursal;
		}
		$fila = $this->claseQueries->fetchResults($query, $params, false);
		return !empty($fila);
	}

	private function validarDatos($post) {
		$campos = array(
			"nombre" => "Nombre de la sucursal",
			"ticket_negocio" => "Nombre del negocio",
			"ticket_calle" => "Calle",
			"ticket_numero" => "Numero",
			"ticket_colonia" => "Colonia",
			"ticket_codigopostal" => "Codigo postal",
			"ticket_ciudad" => "Ciudad",
			"ticket_nombre" => "Razon social",
			"ticket_rfc" => "RFC",
			"ticket_regimen" => "Regimen fiscal",
			"ticket_nombreimpresora" => "Nombre de la impresora",
		);

		foreach ($campos as $campo => $etiqueta) {
			if (trim($post[$campo] ?? "") === "")
				throw new Exception("El campo \"$etiqueta\" es obligatorio.|Atencion|mensaje|warning", 1);
		}

		if (strlen(trim($post["ticket_codigopostal"])) !== 5 || !ctype_digit(trim($post["ticket_codigopostal"])))
			throw new Exception("El codigo postal debe tener 5 digitos.|Atencion|mensaje|warning", 1);

		if (!esRfcValido(trim($post["ticket_rfc"])))
			throw new Exception("El RFC no tiene un formato valido.|Atencion|mensaje|warning", 1);
	}

	public function agregarSucursal($post) {
		$this->validarDatos($post);

		$nombre = trim($post["nombre"]);

		if ($this->existeNombreDuplicado($nombre))
			throw new Exception("Ya existe una sucursal registrada con ese nombre.|Atencion|mensaje|warning", 1);

		$query = "
		insert into tsucursales
			(nombre, ticket_negocio, ticket_calle, ticket_numero, ticket_colonia, ticket_codigopostal, ticket_ciudad, ticket_nombre, ticket_rfc, ticket_regimen, ticket_nombreimpresora, status)
		values
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
		";
		$params = array(
			$nombre,
			trim($post["ticket_negocio"]),
			trim($post["ticket_calle"]),
			trim($post["ticket_numero"]),
			trim($post["ticket_colonia"]),
			trim($post["ticket_codigopostal"]),
			trim($post["ticket_ciudad"]),
			trim($post["ticket_nombre"]),
			trim($post["ticket_rfc"]),
			trim($post["ticket_regimen"]),
			trim($post["ticket_nombreimpresora"]),
		);
		$this->claseQueries->executeQuery($query, $params, true, "No se pudo guardar la sucursal");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Sucursal guardada correctamente.",
			"texto" => "Sucursal guardada correctamente."
		);
	}

	public function editarSucursal($post) {
		$idsucursal = (int) ($post["id"] ?? 0);
		if ($idsucursal <= 0)
			throw new Exception("No se especifico la sucursal a editar.|Atencion|mensaje|warning", 1);

		$this->validarDatos($post);

		$nombre = trim($post["nombre"]);

		if ($this->existeNombreDuplicado($nombre, $idsucursal))
			throw new Exception("Ya existe otra sucursal registrada con ese nombre.|Atencion|mensaje|warning", 1);

		$query = "
		update tsucursales set
			nombre = ?,
			ticket_negocio = ?,
			ticket_calle = ?,
			ticket_numero = ?,
			ticket_colonia = ?,
			ticket_codigopostal = ?,
			ticket_ciudad = ?,
			ticket_nombre = ?,
			ticket_rfc = ?,
			ticket_regimen = ?,
			ticket_nombreimpresora = ?
		where
			idsucursal = ?
		";
		$params = array(
			$nombre,
			trim($post["ticket_negocio"]),
			trim($post["ticket_calle"]),
			trim($post["ticket_numero"]),
			trim($post["ticket_colonia"]),
			trim($post["ticket_codigopostal"]),
			trim($post["ticket_ciudad"]),
			trim($post["ticket_nombre"]),
			trim($post["ticket_rfc"]),
			trim($post["ticket_regimen"]),
			trim($post["ticket_nombreimpresora"]),
			$idsucursal,
		);
		$this->claseQueries->executeQuery($query, $params, false, "No se pudo actualizar la sucursal");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Sucursal actualizada correctamente.",
			"texto" => "Sucursal actualizada correctamente."
		);
	}

	public function eliminarSucursal($post) {
		$idsucursal = (int) ($post["id"] ?? 0);
		if ($idsucursal <= 0)
			throw new Exception("No se especifico la sucursal a eliminar.|Atencion|mensaje|warning", 1);

		$query = "select idsucursal from tsucursales where idsucursal = ? and status = 1";
		$params = array($idsucursal);
		$fila = $this->claseQueries->fetchResults($query, $params, false, "No se encontro la sucursal", false, false, "", false, false);

		$this->claseQueries->executeQuery("update tsucursales set status = 0 where idsucursal = ?", $params, false, "No se pudo eliminar la sucursal");

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"texto" => "Sucursal eliminada correctamente."
		);
	}
}
?>
