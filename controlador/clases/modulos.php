<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/baseClass.php");
class Modulos extends BaseClass {

	private $isDebugger;
	protected $claseQueries;

	public function __construct($con = null, $pdo = null) {
		if ($con === null) {
			include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
		}
		include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/queries.php");
		$this->isDebugger = $_SESSION["infoUsuario"]["debugger"] ?? 0;
		$this->claseQueries = new Queries($con, $pdo, $this->isDebugger);
		register_shutdown_function(array($this, "handleFatalError"));
	}

	public function isDebugger() {
		return $this->isDebugger;
	}

	/**
	 * Devuelve los módulos (menú principal) habilitados para una empresa,
	 * a partir del catálogo genérico tcatmodulos + la tabla puente
	 * trempresasmodulos (qué módulos tiene contratados/activos cada empresa).
	 */
	public function obtenerModulos($post) {
		$idempresa = $post["idempresa"];

		$query = "
		select
			*
		from
			tcatmodulos a
		left join
			trempresasmodulos b
		on
			a.idmodulo = b.idmodulo
		where
			b.idempresa = ?
		order by
			modulo asc
		";
		$params = array($idempresa);
		$modulos = $this->claseQueries->fetchResults($query, $params);

		if (empty($modulos))
			throw new Exception("No tienes ningún módulo habilitado.|¡Atención!|mensaje|warning", 1);

		$respuesta = array("result" => "success", "modulos" => $modulos);

		return $respuesta;
	}

	/**
	 * Devuelve los submódulos de un módulo, respetando permisos por usuario
	 * (tcatpermisos / trusuariopermisos) y si el submódulo está habilitado
	 * para la empresa (trempresassubmodulos).
	 */
	public function obtenerSubmodulos($post) {
		$idempresa = $post["idempresa"];
		$idmodulo = $post["idmodulo"];
		$idusuario = $post["idusuario"];

		$params = array($idempresa, $idusuario, $idusuario, $idempresa, $idmodulo);

		$query = "
		select
			a.*
		from
			tcatsubmodulos a
		inner join
			trempresassubmodulos b
		on
			b.idsubmodulo = a.idsubmodulo and
			b.idempresa = ?
		left join
			tcatpermisos c
		on
			c.permiso = a.permiso and
			c.idmodulo = a.idmodulo
		left join
			trusuariopermisos d
		on
			d.idpermiso = c.idpermiso and
			d.idusuario = ?
		left join
			tusuarios e
		on
			e.idusuario = ?
		left join
			trempresasmodulos f
		on
			f.idmodulo = c.idmodulo and
			f.idempresa = ?
		where
			a.idmodulo = ? and
			(
				e.idtipousuario = 1 or
				(
					e.idtipousuario = 2 and
					a.tipo_permiso = 0
				) or
				(
					a.tipo_permiso = 1 and
					d.idpermiso is not null
				) or
				(
					e.idtipousuario > 2 and
					d.idpermiso is not null
				)
			) and
			(
				c.idpermiso is null or
				(
					c.idpermiso is not null and
					(
						f.idempresa is not null or
						c.idmodulo = 0
					)
				)
			)
		order by
			submodulo";
		$submodulos = $this->claseQueries->fetchResults($query, $params);

		if (empty($submodulos)) {
			return [
				"result" => "error",
				"titulo" => "Atención",
				"mensaje" => "No tienes ningún submódulo habilitado.",
				"icono" => "warning",
				"submodulos" => []
			];
		}

		$respuesta = array("result" => "success", "submodulos" => $submodulos);

		return $respuesta;
	}

	/**
	 * Verifica si una empresa tiene contratado/activo un módulo determinado.
	 */
	public function tieneModulo($idempresa, $idmodulo) {
		$query = "
			SELECT 1
			FROM trempresasmodulos
			WHERE idempresa = ? AND idmodulo = ?
			LIMIT 1
		";

		$params = [$idempresa, $idmodulo];
		$result = $this->claseQueries->fetchResults($query, $params);

		return !empty($result);
	}

	/**
	 * A partir de los segmentos de URL (modulo1/modulo2/modulo3/modulo4),
	 * resuelve a qué idmodulo/idsubmodulo corresponden según tcatsubmodulos.url.
	 * Útil para bitácoras, breadcrumbs y checks de permisos por URL.
	 */
	public function obtenerIdmoduloYSubmodulo($post) {
		$modulo1 = $post["modulo1"] ?? "";
		$modulo2 = $post["modulo2"] ?? "";
		$modulo3 = $post["modulo3"] ?? "";
		$modulo4 = $post["modulo4"] ?? "";

		$url = "/$modulo1/$modulo2" . ((!empty($modulo3)) ? "/" . $modulo3 . ((!empty($modulo4)) ? "/" . $modulo4 : "") : "");

		$query = "
		select
			idsubmodulo,
			idmodulo
		from
			tcatsubmodulos
		where
			url = ?
		";
		$params = array($url);
		$respuesta = $this->claseQueries->fetchResults($query, $params, false);
		if (empty($respuesta["idsubmodulo"]) && !empty($modulo4)) {
			$url = "/$modulo1/$modulo2" . ((!empty($modulo3)) ? "/" . $modulo3 : "");
			$params = array($url);
			$respuesta = $this->claseQueries->fetchResults($query, $params, false);
			if (empty($respuesta["idsubmodulo"]) && !empty($modulo3)) {
				$url = "/$modulo1/$modulo2";
				$params = array($url);
				$respuesta = $this->claseQueries->fetchResults($query, $params, false);
			}
		}

		$respuesta = array("result" => "success", "idmodulo" => $respuesta["idmodulo"] ?? "0", "idsubmodulo" => $respuesta["idsubmodulo"] ?? "0");

		return $respuesta;
	}

	public function getInfoModulo($idmodulo) {
		$query = "SELECT * FROM tcatmodulos WHERE idmodulo = ?";
		$params = [$idmodulo];
		return $this->claseQueries->fetchResults($query, $params, false);
	}
}
?>
