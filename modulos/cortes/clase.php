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
			c.folio,
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
			c.status,
			(
				c.status = 1 and c.z = 0 and c.idcorte = (
					select min(t2.idcorte) from tcortes t2
					where t2.idsucursal = c.idsucursal and t2.status = 1 and t2.z = 0
				)
			) as puede_verificar
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

	/**
	 * Listado de verificaciones (tcortesz) visible para el usuario actual:
	 * mismo alcance y filtros que getCortes(), pero sobre el archivo de
	 * cortes ya verificados.
	 */
	public function getVerificaciones($idadministrador, $filtros = array()) {
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
			$condiciones .= " and cz.idsucursal = ?";
			$params[] = $idsucursalFiltro;
		}
		if ($idusuarioFiltro > 0) {
			$condiciones .= " and cz.idusuario = ?";
			$params[] = $idusuarioFiltro;
		}
		if ($fechaDesde !== "") {
			$condiciones .= " and cz.fechainicio >= ?";
			$params[] = $fechaDesde;
		}
		if ($fechaHasta !== "") {
			$condiciones .= " and cz.fechainicio <= ?";
			$params[] = $fechaHasta;
		}

		$query = "
		select
			cz.idcorte,
			cz.folio,
			cz.idsucursal,
			s.nombre as sucursal,
			cz.idusuario,
			u.nombre as usuario_nombre,
			u.apaterno as usuario_apaterno,
			u.amaterno as usuario_amaterno,
			cz.fechainicio,
			cz.horainicio,
			cz.fechafinal,
			cz.horafinal,
			cz.folioinicial,
			cz.foliofinal,
			cz.fondoinicial,
			cz.gastos,
			cz.ventas,
			cz.fondofinal
		from
			tcortesz cz
		inner join
			tsucursales s on s.idsucursal = cz.idsucursal
		left join
			tusuarios u on u.idusuario = cz.idusuario
		where
			cz.idsucursal in ($placeholders)
			$condiciones
		order by
			cz.fechainicio desc, cz.horainicio desc
		";

		return $this->claseQueries->fetchResults($query, $params);
	}

	/**
	 * Verificacion (tcortesz) con los datos fiscales de su sucursal
	 * (tsucursales.ticket_*), para la vista imprimible del resumen. Valida
	 * que pertenezca a una sucursal permitida para el usuario actual.
	 */
	public function getVerificacionParaImprimir($idcorte, $idadministrador) {
		$query = "
		select
			cz.idcorte, cz.folio, cz.idsucursal, cz.fechainicio, cz.horainicio, cz.fechafinal, cz.horafinal,
			cz.folioinicial, cz.foliofinal, cz.fondoinicial, cz.gastos, cz.ventas, cz.fondofinal,
			s.nombre as sucursal,
			s.ticket_negocio, s.ticket_calle, s.ticket_numero, s.ticket_colonia, s.ticket_codigopostal,
			s.ticket_ciudad, s.ticket_nombre, s.ticket_rfc, s.ticket_regimen
		from
			tcortesz cz
		inner join
			tsucursales s on s.idsucursal = cz.idsucursal
		where
			cz.idcorte = ?
		";
		$corte = $this->claseQueries->fetchResults($query, array($idcorte), false, "No se encontro la verificacion", false, false, "", false, false);

		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (!in_array((int) $corte["idsucursal"], $sucursalesUsuario))
			throw new Exception("No se encontro la verificacion.|Atencion|mensaje|warning", 1);

		return $corte;
	}

	/**
	 * Cuentas (tickets) de una verificacion con sus productos, para la
	 * impresion de tickets. Valida que la verificacion pertenezca a una
	 * sucursal permitida.
	 */
	public function getCuentasVerificacion($idcorte, $idadministrador) {
		$this->getVerificacionParaImprimir($idcorte, $idadministrador);

		$query = "select idcuenta, folio, total, cambio, fecha, hora from tcuentasz where idcorte = ? order by idcuenta";
		$cuentas = $this->claseQueries->fetchResults($query, array($idcorte));

		if (empty($cuentas))
			return $cuentas;

		$idsCuentas = array_column($cuentas, "idcuenta");
		$placeholders = implode(",", array_fill(0, count($idsCuentas), "?"));
		$query = "
		select
			rp.idcuenta,
			rp.cantidad,
			rp.precio,
			p.nombre as producto
		from
			trcuentaproductosz rp
		inner join
			tproductos p on p.idproducto = rp.idproducto
		where
			rp.idcuenta in ($placeholders)
		order by
			rp.idcuenta, rp.idcuentaproducto
		";
		$productos = $this->claseQueries->fetchResults($query, $idsCuentas);

		$productosPorCuenta = array();
		foreach ($productos as $producto) {
			$productosPorCuenta[$producto["idcuenta"]][] = $producto;
		}

		foreach ($cuentas as &$cuenta) {
			$cuenta["productos"] = $productosPorCuenta[$cuenta["idcuenta"]] ?? array();
		}

		return $cuentas;
	}

	/**
	 * Un corte puntual, validando que su sucursal este dentro de las
	 * permitidas para el usuario actual (si no, se trata como no encontrado).
	 */
	public function getCorte($idcorte, $idadministrador) {
		$query = "
		select
			idcorte, folio, idsucursal, idusuario, fechainicio, horainicio, fechafinal, horafinal,
			folioinicial, foliofinal, fondoinicial, gastos, ventas, fondofinal, z, status
		from
			tcortes
		where
			idcorte = ?
		";
		$corte = $this->claseQueries->fetchResults($query, array($idcorte), false, "No se encontro el corte", false, false, "", false, false);

		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (!in_array((int) $corte["idsucursal"], $sucursalesUsuario))
			throw new Exception("No se encontro el corte.|Atencion|mensaje|warning", 1);

		return $corte;
	}

	/**
	 * Corte cerrado con los datos fiscales de su sucursal (tsucursales.ticket_*),
	 * para la vista imprimible. Valida sucursal permitida y que este cerrado.
	 */
	public function getCorteParaImprimir($idcorte, $idadministrador) {
		$query = "
		select
			c.idcorte, c.folio, c.idsucursal, c.fechainicio, c.horainicio, c.fechafinal, c.horafinal,
			c.folioinicial, c.foliofinal, c.fondoinicial, c.gastos, c.ventas, c.fondofinal, c.status,
			s.nombre as sucursal,
			s.ticket_negocio, s.ticket_calle, s.ticket_numero, s.ticket_colonia, s.ticket_codigopostal,
			s.ticket_ciudad, s.ticket_nombre, s.ticket_rfc, s.ticket_regimen
		from
			tcortes c
		inner join
			tsucursales s on s.idsucursal = c.idsucursal
		where
			c.idcorte = ?
		";
		$corte = $this->claseQueries->fetchResults($query, array($idcorte), false, "No se encontro el corte", false, false, "", false, false);

		$sucursalesUsuario = array_column($this->getSucursalesUsuario($idadministrador), "idsucursal");
		if (!in_array((int) $corte["idsucursal"], $sucursalesUsuario))
			throw new Exception("No se encontro el corte.|Atencion|mensaje|warning", 1);

		if ((int) $corte["status"] !== 1)
			throw new Exception("Solo se pueden imprimir cortes cerrados.|Atencion|mensaje|warning", 1);

		return $corte;
	}

	/**
	 * Cuentas (tickets) de un corte con sus productos, para la pantalla de
	 * verificacion. Valida que el corte pertenezca a una sucursal permitida.
	 */
	public function getCuentasCorte($idcorte, $idadministrador) {
		$this->getCorte($idcorte, $idadministrador);

		$query = "select idcuenta, folio, total, cambio, fecha, hora from tcuentas where idcorte = ? order by idcuenta";
		$cuentas = $this->claseQueries->fetchResults($query, array($idcorte));

		if (empty($cuentas))
			return $cuentas;

		// Traer los productos de TODAS las cuentas en una sola consulta (evita
		// N+1: antes se hacia una consulta por cuenta, muy lento con cortes
		// de muchas cuentas) y agruparlos en PHP por idcuenta.
		$idsCuentas = array_column($cuentas, "idcuenta");
		$placeholders = implode(",", array_fill(0, count($idsCuentas), "?"));
		$query = "
		select
			rp.idcuenta,
			rp.cantidad,
			rp.precio,
			p.nombre as producto
		from
			trcuentaproductos rp
		inner join
			tproductos p on p.idproducto = rp.idproducto
		where
			rp.idcuenta in ($placeholders)
		order by
			rp.idcuenta, rp.idcuentaproducto
		";
		$productos = $this->claseQueries->fetchResults($query, $idsCuentas);

		$productosPorCuenta = array();
		foreach ($productos as $producto) {
			$productosPorCuenta[$producto["idcuenta"]][] = $producto;
		}

		foreach ($cuentas as &$cuenta) {
			$cuenta["productos"] = $productosPorCuenta[$cuenta["idcuenta"]] ?? array();
		}

		return $cuentas;
	}

	/**
	 * Solo el corte cerrado y no verificado mas antiguo de su sucursal puede
	 * verificarse (misma regla que puede_verificar en getCortes()).
	 */
	public function esProximoAVerificar($idcorte, $idsucursal) {
		$query = "
		select idcorte from tcortes
		where idsucursal = ? and status = 1 and z = 0
		order by idcorte
		limit 1
		";
		$fila = $this->claseQueries->fetchResults($query, array($idsucursal), false);
		return !empty($fila) && (int) $fila["idcorte"] === (int) $idcorte;
	}

	/**
	 * Reserva un bloque de folios consecutivos del contador indicado para la
	 * sucursal y devuelve el PRIMERO del bloque.
	 *
	 * El incremento es atomico en una sola sentencia y deja el ultimo folio del
	 * bloque en LAST_INSERT_ID(), que es por conexion; dos procesos
	 * concurrentes nunca reciben el mismo folio. Es el mismo patron que usa el
	 * punto de venta al cobrar.
	 *
	 * @param int $idsucursal
	 * @param string $columna contador en tfolios (valor interno, nunca del usuario)
	 * @param int $cantidad folios a reservar
	 * @return int primer folio del bloque
	 */
	private function consumirFolios($idsucursal, $columna, $cantidad) {
		$columnasValidas = array("ultimofolio", "ultimofolio_corte", "ultimofolio_cortez", "ultimofolio_cuentaz");
		if (!in_array($columna, $columnasValidas, true))
			throw new Exception("Contador de folios invalido.|Atencion|mensaje|warning", 1);

		$cantidad = (int) $cantidad;
		if ($cantidad < 1)
			throw new Exception("No hay documentos a los que asignar folio.|Atencion|mensaje|warning", 1);

		$query = "update tfolios set $columna = LAST_INSERT_ID($columna + ?) where idsucursal = ?";
		$afectadas = (int) $this->claseQueries->executeQuery($query, array($cantidad, $idsucursal), false, "No se pudo asignar el folio", false, false, "", true);

		// Sin renglon en tfolios el UPDATE no afecta filas y no se asigna folio
		// alguno. Se corta aqui para no guardar documentos con folio 0.
		if ($afectadas < 1)
			throw new Exception("La sucursal no tiene configurados sus folios. Configuralos en el modulo de Sucursales.|Atencion|mensaje|warning", 1);

		// LAST_INSERT_ID() es por conexion y estamos en la misma sesion dentro
		// de la transaccion, asi que no hay carrera posible con otro proceso.
		$fila = $this->claseQueries->fetchResults("select LAST_INSERT_ID() as folio", array(), false, "No se pudo asignar el folio");
		$ultimoFolio = (int) ($fila["folio"] ?? 0);

		if ($ultimoFolio < $cantidad)
			throw new Exception("No se pudo asignar el folio de la sucursal.|Atencion|mensaje|warning", 1);

		return $ultimoFolio - $cantidad + 1;
	}

	/**
	 * Verificacion de un corte: el admin confirma cuales cuentas (tickets)
	 * realmente formaron parte del corte. Copia el corte y las cuentas
	 * seleccionadas a las tablas *z (archivo de verificacion), recalcula
	 * totales sobre lo seleccionado, y marca el corte original como
	 * verificado (z = 1). No se modifica el corte original salvo ese flag.
	 */
	public function verificarCorte($post) {
		$idadministrador = $_SESSION["infoUsuario"]["idadministrador"];

		$idcorte = (int) ($post["idcorte"] ?? 0);
		if ($idcorte <= 0)
			throw new Exception("No se especifico el corte a verificar.|Atencion|mensaje|warning", 1);

		$corte = $this->getCorte($idcorte, $idadministrador);

		if ((int) $corte["status"] !== 1)
			throw new Exception("Solo se pueden verificar cortes cerrados.|Atencion|mensaje|warning", 1);
		if ((int) $corte["z"] !== 0)
			throw new Exception("Este corte ya fue verificado.|Atencion|mensaje|warning", 1);
		if (!$this->esProximoAVerificar($idcorte, $corte["idsucursal"]))
			throw new Exception("Debes verificar primero el corte pendiente mas antiguo de la sucursal.|Atencion|mensaje|warning", 1);

		$idsCuentasPost = array_map("intval", (array) ($post["idcuenta"] ?? array()));
		$idsCuentasPost = array_values(array_unique(array_filter($idsCuentasPost, function ($v) { return $v > 0; })));
		if (empty($idsCuentasPost))
			throw new Exception("Debes seleccionar al menos una cuenta.|Atencion|mensaje|warning", 1);

		$placeholders = implode(",", array_fill(0, count($idsCuentasPost), "?"));
		$query = "select idcuenta, total from tcuentas where idcorte = ? and idcuenta in ($placeholders)";
		$cuentasValidas = $this->claseQueries->fetchResults($query, array_merge(array($idcorte), $idsCuentasPost));
		if (count($cuentasValidas) !== count($idsCuentasPost))
			throw new Exception("Alguna de las cuentas seleccionadas no pertenece a este corte.|Atencion|mensaje|warning", 1);

		mysqli_begin_transaction($this->con);
		try {
			// Folios propios de la verificacion y de los tickets verificados,
			// tomados del consecutivo configurado para la sucursal.
			$folioCortez = $this->consumirFolios($corte["idsucursal"], "ultimofolio_cortez", 1);
			$folioCuentazInicial = $this->consumirFolios($corte["idsucursal"], "ultimofolio_cuentaz", count($cuentasValidas));

			$query = "
			insert into tcortesz
				(idusuario, folio, idsucursal, fechainicio, horainicio, fechafinal, horafinal, folioinicial, foliofinal, fondoinicial, gastos, ventas, fondofinal)
			select
				idusuario, ?, ?, fechainicio, horainicio, fechafinal, horafinal, 0, 0, fondoinicial, gastos, 0, 0
			from
				tcortes
			where
				idcorte = ?
			";
			$idcorteN = $this->claseQueries->executeQuery($query, array($folioCortez, $corte["idsucursal"], $idcorte), true, "No se pudo iniciar la verificacion");

			$total = 0;
			$folioCuentaz = $folioCuentazInicial;
			foreach ($cuentasValidas as $cuenta) {
				$query = "insert into tcuentasz (idcorte, folio, total, cambio, fecha, hora) select ?, ?, total, cambio, fecha, hora from tcuentas where idcuenta = ?";
				$idcuentaN = $this->claseQueries->executeQuery($query, array($idcorteN, $folioCuentaz, $cuenta["idcuenta"]), true, "No se pudo copiar la cuenta verificada");
				$folioCuentaz++;

				$total += (float) $cuenta["total"];

				$query = "
				insert into trcuentaproductosz (idcuenta, idproducto, cantidad, precio)
				select ?, idproducto, cantidad, precio from trcuentaproductos where idcuenta = ? order by idcuentaproducto
				";
				$this->claseQueries->executeQuery($query, array($idcuentaN, $cuenta["idcuenta"]), false, "No se pudo copiar los productos de la cuenta verificada");
			}

			// Rango de folios de los tickets verificados. Antes se calculaba con
			// min/max(idcuenta), que son las PK autoincrementales de las copias
			// y no folios, asi que el rango reportado era incorrecto.
			$query = "select min(folio) as folioinicial, max(folio) as foliofinal from tcuentasz where idcorte = ?";
			$folios = $this->claseQueries->fetchResults($query, array($idcorteN), false);

			$fondofinal = ((float) $corte["fondoinicial"] + $total) - (float) $corte["gastos"];

			$query = "update tcortesz set ventas = ?, fondofinal = ?, folioinicial = ?, foliofinal = ? where idcorte = ?";
			$this->claseQueries->executeQuery($query, array($total, $fondofinal, $folios["folioinicial"], $folios["foliofinal"], $idcorteN), false, "No se pudo actualizar el corte verificado");

			$query = "update tcortes set z = 1 where idcorte = ?";
			$this->claseQueries->executeQuery($query, array($idcorte), false, "No se pudo marcar el corte como verificado");

			mysqli_commit($this->con);
		} catch (Exception $e) {
			mysqli_rollback($this->con);
			throw $e;
		}

		return array(
			"result" => "success",
			"titulo" => "Listo",
			"mensaje" => "Corte verificado correctamente.",
			"texto" => "Corte verificado correctamente."
		);
	}
}
?>
