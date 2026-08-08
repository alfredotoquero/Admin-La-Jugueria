<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");

class Sucursales extends BaseClass {

	// El ticket del punto de venta imprime el folio rellenado a 7 digitos.
	const FOLIO_MAXIMO = 9999999;

	// Tope del fondo de caja para no desbordar el decimal de la columna.
	const FONDO_MAXIMO = 999999.99;

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
			s.idsucursal,
			s.nombre,
			s.ticket_nombre,
			s.ticket_rfc,
			s.fondoinicial,
			s.registro,
			coalesce(f.ultimofolio, 0) + 1 as siguiente_folio
		from
			tsucursales s
		left join
			tfolios f on f.idsucursal = s.idsucursal
		where
			s.status = 1
		order by
			s.nombre
		";
		return $this->claseQueries->fetchResults($query);
	}

	public function getSucursal($idsucursal) {
		$query = "
		select
			s.idsucursal,
			s.nombre,
			s.ticket_negocio,
			s.ticket_calle,
			s.ticket_numero,
			s.ticket_colonia,
			s.ticket_codigopostal,
			s.ticket_ciudad,
			s.ticket_nombre,
			s.ticket_rfc,
			s.ticket_regimen,
			s.ticket_nombreimpresora,
			s.fondoinicial,
			coalesce(f.ultimofolio, 0) + 1 as siguiente_folio,
			coalesce(f.ultimofolio_corte, 0) + 1 as siguiente_folio_corte,
			coalesce(f.ultimofolio_cortez, 0) + 1 as siguiente_folio_cortez,
			coalesce(f.ultimofolio_cuentaz, 0) + 1 as siguiente_folio_cuentaz
		from
			tsucursales s
		left join
			tfolios f on f.idsucursal = s.idsucursal
		where
			s.idsucursal = ?
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

	/**
	 * Valida el fondo de caja capturado y lo devuelve normalizado a 2 decimales.
	 * Es el monto con el que el punto de venta abre el corte de esta sucursal.
	 */
	private function resolverFondoInicial($post) {
		$capturado = trim($post["fondoinicial"] ?? "");

		if ($capturado === "")
			throw new Exception("El campo \"Fondo de caja\" es obligatorio.|Atencion|mensaje|warning", 1);

		if (!is_numeric($capturado))
			throw new Exception("El campo \"Fondo de caja\" debe ser un monto valido.|Atencion|mensaje|warning", 1);

		$fondo = (float) $capturado;

		if ($fondo < 0)
			throw new Exception("El campo \"Fondo de caja\" no puede ser negativo.|Atencion|mensaje|warning", 1);

		if ($fondo > self::FONDO_MAXIMO)
			throw new Exception("El campo \"Fondo de caja\" no puede ser mayor a $" . number_format(self::FONDO_MAXIMO, 2) . ".|Atencion|mensaje|warning", 1);

		return number_format($fondo, 2, ".", "");
	}

	/**
	 * Los cuatro folios administrables por sucursal. Cada definicion indica:
	 * - campo:    nombre del input en el formulario
	 * - columna:  contador en tfolios
	 * - etiqueta: como se nombra en los mensajes de error
	 * - piso:     consulta que devuelve el folio mas alto ya emitido en esa
	 *             sucursal, para impedir que se retroceda por debajo de un
	 *             documento ya impreso.
	 */
	private function definicionesFolios() {
		return array(
			array(
				"campo" => "siguiente_folio",
				"columna" => "ultimofolio",
				"etiqueta" => "Siguiente folio de venta",
				"piso" => "select coalesce(max(folio), 0) as folio from tcuentas where idsucursal = ?",
			),
			array(
				"campo" => "siguiente_folio_corte",
				"columna" => "ultimofolio_corte",
				"etiqueta" => "Siguiente folio de corte",
				"piso" => "select coalesce(max(folio), 0) as folio from tcortes where idsucursal = ?",
			),
			array(
				"campo" => "siguiente_folio_cortez",
				"columna" => "ultimofolio_cortez",
				"etiqueta" => "Siguiente folio de verificacion",
				"piso" => "select coalesce(max(folio), 0) as folio from tcortesz where idsucursal = ?",
			),
			array(
				"campo" => "siguiente_folio_cuentaz",
				"columna" => "ultimofolio_cuentaz",
				"etiqueta" => "Siguiente folio de ticket verificado",
				// tcuentasz no tiene idsucursal propio: se resuelve por tcortesz.
				"piso" => "
					select coalesce(max(cz.folio), 0) as folio
					from tcuentasz cz
					inner join tcortesz z on z.idcorte = cz.idcorte
					where z.idsucursal = ?
				",
			),
		);
	}

	/**
	 * Valida los folios capturados y los devuelve como "ultimo folio", que es lo
	 * que almacena tfolios: quien emite el documento incrementa el contador
	 * antes de asignarlo, asi que ultimo folio = siguiente folio - 1.
	 *
	 * @param array $post datos del formulario
	 * @param int|null $idsucursal sucursal en edicion (null en alta)
	 * @return array columna de tfolios => valor a guardar
	 */
	private function resolverUltimosFolios($post, $idsucursal = null) {
		$valores = array();

		foreach ($this->definicionesFolios() as $definicion) {
			$capturado = trim($post[$definicion["campo"]] ?? "");

			if ($capturado === "")
				throw new Exception("El campo \"" . $definicion["etiqueta"] . "\" es obligatorio.|Atencion|mensaje|warning", 1);

			if (!ctype_digit($capturado))
				throw new Exception("El campo \"" . $definicion["etiqueta"] . "\" debe ser un numero entero.|Atencion|mensaje|warning", 1);

			$siguienteFolio = (int) $capturado;

			if ($siguienteFolio < 1)
				throw new Exception("El campo \"" . $definicion["etiqueta"] . "\" debe ser mayor o igual a 1.|Atencion|mensaje|warning", 1);

			// Los tickets rellenan el folio a 7 digitos; arriba de ese tope el
			// identificador impreso pierde el formato.
			if ($siguienteFolio > self::FOLIO_MAXIMO)
				throw new Exception("El campo \"" . $definicion["etiqueta"] . "\" no puede ser mayor a " . number_format(self::FOLIO_MAXIMO) . ".|Atencion|mensaje|warning", 1);

			$ultimoFolio = $siguienteFolio - 1;

			if (!empty($idsucursal)) {
				// No se permite retroceder por debajo de un folio ya impreso: se
				// duplicarian folios entre documentos de la misma sucursal.
				$fila = $this->claseQueries->fetchResults($definicion["piso"], array($idsucursal), false);
				$folioEmitido = (int) ($fila["folio"] ?? 0);

				if ($ultimoFolio < $folioEmitido)
					throw new Exception("Esta sucursal ya emitio el folio " . number_format($folioEmitido) . " en \"" . $definicion["etiqueta"] . "\". El valor no puede ser menor a " . number_format($folioEmitido + 1) . ".|Atencion|mensaje|warning", 1);
			}

			$valores[$definicion["columna"]] = $ultimoFolio;
		}

		return $valores;
	}

	/**
	 * Alta o actualizacion de los folios de la sucursal. Se usa upsert porque
	 * las sucursales dadas de alta antes de esta version pueden no tener
	 * renglon en tfolios.
	 */
	private function guardarFoliosSucursal($idsucursal, $valores) {
		$columnas = array_keys($valores);
		$asignaciones = array();
		foreach ($columnas as $columna) {
			$asignaciones[] = $columna . " = ?";
		}

		$query = "
		insert into tfolios
			(idsucursal, " . implode(", ", $columnas) . ")
		values
			(?, " . implode(", ", array_fill(0, count($columnas), "?")) . ")
		on duplicate key update
			" . implode(", ", $asignaciones) . "
		";
		$params = array_merge(array($idsucursal), array_values($valores), array_values($valores));
		$this->claseQueries->executeQuery($query, $params, false, "No se pudieron guardar los folios de la sucursal");
	}

	public function agregarSucursal($post) {
		$this->validarDatos($post);

		$nombre = trim($post["nombre"]);

		if ($this->existeNombreDuplicado($nombre))
			throw new Exception("Ya existe una sucursal registrada con ese nombre.|Atencion|mensaje|warning", 1);

		$fondoInicial = $this->resolverFondoInicial($post);
		$ultimosFolios = $this->resolverUltimosFolios($post);

		mysqli_begin_transaction($this->con);
		try {
			$query = "
			insert into tsucursales
				(nombre, ticket_negocio, ticket_calle, ticket_numero, ticket_colonia, ticket_codigopostal, ticket_ciudad, ticket_nombre, ticket_rfc, ticket_regimen, ticket_nombreimpresora, fondoinicial, status)
			values
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
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
				$fondoInicial,
			);
			$idsucursal = $this->claseQueries->executeQuery($query, $params, true, "No se pudo guardar la sucursal");

			// Sin este renglon el punto de venta registra las ventas con folio 0:
			// su "update tfolios ... LAST_INSERT_ID(ultimofolio + 1)" no afecta filas.
			$this->guardarFoliosSucursal($idsucursal, $ultimosFolios);

			mysqli_commit($this->con);
		} catch (Exception $e) {
			mysqli_rollback($this->con);
			throw $e;
		}

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

		$fondoInicial = $this->resolverFondoInicial($post);
		$ultimosFolios = $this->resolverUltimosFolios($post, $idsucursal);

		mysqli_begin_transaction($this->con);
		try {
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
				ticket_nombreimpresora = ?,
				fondoinicial = ?
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
				$fondoInicial,
				$idsucursal,
			);
			$this->claseQueries->executeQuery($query, $params, false, "No se pudo actualizar la sucursal");

			$this->guardarFoliosSucursal($idsucursal, $ultimosFolios);

			mysqli_commit($this->con);
		} catch (Exception $e) {
			mysqli_rollback($this->con);
			throw $e;
		}

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
