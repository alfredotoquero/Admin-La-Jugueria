<?php
class BaseClass {
	protected $claseQueries;

	public function __call($method, $args) {
		try {
			if (method_exists($this, $method)) {
				$this->logCall($method, $args);
				return $respuesta = call_user_func_array([$this, $method], $args);
			}
			throw new Exception("Método no encontrado: $method", 2);
		} catch (Exception $e) {
			$respuesta = ($e->getCode() == 1) ? $this->handleMyException($e) : $this->handleException($e, 1);
		} catch (\Throwable $e) {
			$respuesta = $this->handleException($e, 2);
		} finally {
			return $respuesta;
		}
	}

	/**
	 * Función para manejar excepciones
	 * 
	 * @access private
	 * @param object $e
	 * @param int $code
	 * @return array
	 */
	private function handleException($e, $code) {
		$errorCode = $this->generateErrorCode($e);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/excepciones.txt", "errorCode: $errorCode - " . print_r($e, true) . " -- " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
		return array(
			"mensaje" => (($this->isDebugger()) ? $e->getMessage() : "Error inesperado (Código #00$code-" . $errorCode . ")."),
			"titulo" => "Error",
			"icono" => "error",
			...(($this->isDebugger()) ? ["linea" => $e->getLine()] : []),
			...(($this->isDebugger()) ? ["codigo" => intval($code)] : []),
			...(($this->isDebugger()) ? ["excepcion" => print_r($e, true)] : []),
			"result" => "error"
		);
	}

	/**
	 * Función para manejar las excepciones que yo arrojo explícitamente.
	 * 
	 * @access private
	 * @param object $e
	 * @return array
	 */
	private function handleMyException($e) {
		@list($mensaje, $titulo, $tipo, $adicionales) = explode("|", $e->getMessage(), 4);
		$codigo = $e->getCode();
		if (!empty($tipo) && $tipo != "mensaje") {
			if ($tipo == "single_toast")
				@list($icono, $opciones, $quitar_toasts_anteriores) = explode("|", $adicionales);
			else if ($tipo == "notificacion")
				@list($mostrar) = explode("|", $adicionales);
		} else
			@list($icono, $id, $select2, $formulario, $disabled) = explode("|", $adicionales);
		if (!empty($opciones)) {
			$opciones = explode(",", $opciones);
			$result = [];
			foreach ($opciones as $opcion) {
				list($key, $value) = explode("_", $opcion);
				$result[$key] = (($key == "timeOut") ? intval($value) : ((in_array($key, array("progressBar", "closeButton", "newestOnTop", "tapToDismiss"))) ? (bool) $value : $value));
			}
			$opciones = $result;
		}
		$respuesta = array(
			"mensaje" => rtrim($mensaje, ".") . ".",
			"titulo" => $titulo ?? "Error",
			"icono" => ((!empty($icono)) ? rtrim($icono, ".") : "error"),
			"id" => $id ?? "",
			"select2" => intval($select2 ?? 0),
			"formulario" => $formulario ?? null,
			"tipo" => $tipo ?? "mensaje",
			"disabled" => ((isset($disabled) && $disabled == 1) ? true : false),
			"opciones" => $opciones ?? array(),
			"quitar_toasts_anteriores" => ((isset($quitar_toasts_anteriores) && $quitar_toasts_anteriores == 1) ? true : false),
			"mostrar" => $mostrar ?? null,
			...(($this->isDebugger()) ? ["linea" => $e->getLine()] : []),
			...(($this->isDebugger()) ? ["codigo" => intval($codigo)] : []),
			...(($this->isDebugger()) ? ["excepcion" => print_r($e, true)] : []),
			"result" => "error"
		);
		return $respuesta;
	}

	/**
	 * Función para manejar errores fatales.
	 * 
	 * @access public
	 * @return array
	 */
	public function handleFatalError() {
		$error = error_get_last();
		if ($error !== null && $error["type"] === E_ERROR) {
			$errorCode = $this->generateErrorCode($error);
			$respuesta = array(
				"result" => "error",
				"titulo" => "Error",
				"mensaje" => (($this->isDebugger()) ? $error["message"] . "<br>File: " . $error["file"] . "<br>in line: " . $error["line"] : "Error inesperado (Código #003-" . $errorCode . ")."),
				"icono" => "error"
			);

			$logContent = "errorCode: $errorCode" . print_r($error, true) . " -- " . date("Y-m-d H:i:s") . PHP_EOL;

			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/excepciones.txt", $logContent, FILE_APPEND);
			header("Content-Type: application/json");
			http_response_code(209);
			echo json_encode($respuesta, 128);
			exit;
		}
	}

	private function generateErrorCode($e) {
		$file = ((is_array($e)) ? $e["file"] : $e->getFile());
		$line = ((is_array($e)) ? $e["line"] : $e->getLine());
		$salt = "VALOR_OCULTO";

		$data = $salt . "|" . $file . ":" . $line;
		return strtoupper(substr(md5($data), 0, 8));
	}

	private function logCall($method, $args) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$caller = $trace[2] ?? $trace[1] ?? [];
		$file = $caller["file"] ?? "unknown";
		$line = $caller["line"] ?? 0;

		$path = realpath($file);
		$filename = basename($file);
		$className = get_class($this);
		$now = date("Y-m-d H:i:s");

		$array_extra = (($this->isDebugger()) ? array(true, true, "log_call") : array());
		$array_extra = array();

		$query = "
		select
			count(*) as total
		from
			tzllamadasmetodos
		";
		$last_id = $this->claseQueries->fetchResults($query, array(), false)["total"] + 1;

		$query = "
		insert into
			tzllamadasmetodos
			(
				id,
				ruta_completa,
				nombre_archivo,
				invocador_controlador,
				nombre_clase,
				nombre_metodo,
				numero_linea,
				primera_llamada,
				ultima_llamada
			)
		values
			(
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?
			) as a
		on duplicate key update
			ultima_llamada = a.ultima_llamada,
			numero_linea = a.numero_linea,
			invocador_controlador = a.invocador_controlador,
			contador_llamadas = contador_llamadas + 1
		";
		$params = array($last_id, $path, $filename, $args[0]["HTTP_X_CALLER"] ?? $_SERVER["HTTP_X_CALLER"] ?? "", $className, $method, $line, $now, $now);
		$this->claseQueries->executeQuery($query, $params, false, "", ...$array_extra);
	}

	/**
	 * accion.
	 * 
	 * @access public
	 * @param string $tipo
	 * @param array $parametros
	 * @return array
	 */
	public function accion($tipo, $parametros = array()) {

		if (isset($parametros["mensaje"]))
			$parametros["mensaje"] = $this->cleanMessage($parametros["mensaje"], $parametros["punto"] ?? true);
		else
			$parametros["mensaje"] = "";
		switch ($tipo) {
			case "reload":
			case "cerrar_fancybox":
			case "hide_modal":
			case "cortar":
				$array = array();
			break;
			case "redirigir":
				$array = array(
					"url" => $parametros["url"],
				);
			break;
			case "redirigir_post":
				$array = array(
					"url" => $parametros["url"],
					"json" => $parametros["json"] ?? false,
					"parametro" => $parametros["parametro"] ?? null,
					"datos" => $parametros["datos"] ?? array()
				);
			break;
			case "mensaje":
			case "mensaje_reload":
			case "mensaje_cerrar_fancybox":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"] ?? "success",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "left-gap order-1",
						)
					)
				);
			break;
			case "mensaje_redirigir":
			case "mensaje_cargar_ventana":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"] ?? "success",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "left-gap order-1",
						)
					),
					"url" => $parametros["url"],
				);
			break;
			case "mensaje_recargar_lista":
			case "mensaje_recargar_lista_cerrar_fancybox":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"] ?? "success",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "left-gap order-1",
						)
					),
					"url" => $parametros["url"],
					"contenedor" => $parametros["contenedor"] ?? "divLista",
					"parametros" => $parametros["parametros"] ?? "",
					"form" => $parametros["form"] ?? "",
					"tipo_agregar" => $parametros["tipo_agregar"] ?? 1
				);
			break;
			case "mensaje_llamar_funcion":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"] ?? "success",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "left-gap order-1",
						)
					),
					"funcion" => $parametros["funcion"] ?? "recargarLista",
					"parametros" => $parametros["parametros"] ?? "",
					"tres_puntos" => $parametros["tres_puntos"] ?? false,
				);
			break;
			case "mensaje_abrir_fancybox":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"] ?? "success",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "left-gap order-1",
						)
					),
					"configuracion" => array(
						"src" => $parametros["url"],
						"type" => $parametros["type"] ?? "ajax",
						"opts" => array(
							"closeExisting" => $parametros["closeExisting"] ?? true,
							"clickSlide" => $parametros["clickSlide"] ?? false,
							"touch" => $parametros["touch"] ?? false
						)
					)
				);
				if (isset($parametros["afterClose"]))
					$array["configuracion"]["opts"]["afterClose"] = $parametros["afterClose"];
				if (isset($parametros["beforeClose"]))
					$array["configuracion"]["opts"]["beforeClose"] = $parametros["beforeClose"];
			break;
			case "abrir_fancybox":
				$array = array(
					"configuracion" => array(
						"src" => $parametros["url"],
						"type" => $parametros["type"] ?? "ajax",
						"opts" => array(
							"closeExisting" => $parametros["closeExisting"] ?? true,
							"clickSlide" => $parametros["clickSlide"] ?? false,
							"touch" => $parametros["touch"] ?? false
						)
					)
				);
				if (isset($parametros["afterClose"]))
					$array["configuracion"]["opts"]["afterClose"] = $parametros["afterClose"];
				if (isset($parametros["beforeClose"]))
					$array["configuracion"]["opts"]["beforeClose"] = $parametros["beforeClose"];
				if (isset($parametros["beforeShow"]))
					$array["configuracion"]["opts"]["beforeShow"] = $parametros["beforeShow"];
				if (isset($parametros["afterShow"]))
					$array["configuracion"]["opts"]["afterShow"] = $parametros["afterShow"];
			break;
			case "recargar_lista":
				$array = array(
					"url" => $parametros["url"],
					"contenedor" => $parametros["contenedor"] ?? "divLista",
					"parametros" => $parametros["parametros"] ?? "",
					"form" => $parametros["form"] ?? "",
					"tipo_agregar" => $parametros["tipo_agregar"] ?? 1
				);
			break;
			case "single_toast":
				$array = array(
					"icono" => $parametros["icono"] ?? "success",
					"titulo" => $parametros["titulo"],
					"mensaje" => $parametros["mensaje"],
					"opciones" => array(
						"timeOut" => $parametros["timeOut"] ?? 5000,
						"positionClass" => $parametros["positionClass"] ?? "toast-bottom-right",
						"progressBar" => $parametros["progressBar"] ?? true,
						"closeButton" => $parametros["closeButton"] ?? true,
						"newestOnTop" => $parametros["newestOnTop"] ?? true,
						"tapToDismiss" => $parametros["tapToDismiss"] ?? true,
						"onclick" => $parametros["onclick"] ?? null,
					),
					"quitar_toasts_anteriores" => $parametros["quitar_toasts_anteriores"] ?? null,
				);
			break;
			case "llamar_funcion":
				$array = array(
					"funcion" => $parametros["funcion"] ?? "recargarLista",
					"parametros" => $parametros["parametros"] ?? "",
					"tres_puntos" => $parametros["tres_puntos"] ?? false,
				);
			break;
			case "enviar_formulario":
				$array = array(
					"formulario" => $parametros["formulario"],
				);
			break;
			case "swal_opciones":
				$array = array(
					"opciones_swal" => array(
						"title" => $parametros["titulo"],
						"html" => $parametros["mensaje"],
						"icon" => $parametros["icono"],
						"showCancelButton" => $parametros["show_cancel_button"] ?? true,
						"showDenyButton" => $parametros["show_deny_button"] ?? false,
						"confirmButtonText" => $parametros["confirm_button_text"] ?? "Ok",
						"cancelButtonText" => $parametros["cancel_button_text"] ?? "Cancelar",
						"denyButtonText" => $parametros["deny_button_text"] ?? "No",
						"allowOutsideClick" => $parametros["allowOutsideClick"] ?? true,
						"allowEscapeKey" => $parametros["allowEscapeKey"] ?? true,
						"customClass" => array(
							"actions" => "my-actions",
							"confirmButton" => "order-3",
							"cancelButton" => "right-gap order-1",
							"denyButton" => "order-2",
						)
					),
					"adicionales" => array(
						"funcion_confirm" => $parametros["funcion_confirm"],
						"tres_puntos_confirm" => $parametros["tres_puntos_confirm"] ?? false,
						"parametros_confirm" => $parametros["parametros_confirm"] ?? "",
						"funcion_cancel" => $parametros["funcion_cancel"] ?? "",
						"tres_puntos_cancel" => $parametros["tres_puntos_cancel"] ?? false,
						"parametros_cancel" => $parametros["parametros_cancel"] ?? "",
						"funcion_deny" => $parametros["funcion_deny"] ?? "",
						"tres_puntos_deny" => $parametros["tres_puntos_deny"] ?? false,
						"parametros_deny" => $parametros["parametros_deny"] ?? "",
					)
				);
				if (isset($parametros["width"]))
					$array["opciones_swal"]["width"] = $parametros["width"];
			break;

			default:
				$array = array();
			break;
		}
		$array = array("tipo" => $tipo) + $array;

		return $array;
	}

	/**
	 * input.
	 * 
	 * @access public
	 * @param array $post
	 * @return array
	 */
	public function input($tipo, $parametros = array()) {
		switch ($tipo) {
			case "text":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"] ?? "",
					"name" => $parametros["name"] ?? null,
					"focus" => $parametros["focus"] ?? false,
					"accionar" => $parametros["accionar"] ?? false,
					"datas" => $parametros["datas"] ?? array(),
				);
			break;
			case "hidden":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"],
				);
			break;
			case "span":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"],
					"clase" => $parametros["clase"] ?? "",
					"remover_clase" => $parametros["remover_clase"] ?? "",
				);
			break;
			case "img":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"],
					"datas" => $parametros["datas"] ?? array(),
				);
			break;
			case "checkbox":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"],
					"accionar" => $parametros["accionar"] ?? false,
				);
				if (isset($parametros["disabled"]))
					$array["disabled"] = $parametros["disabled"];
			break;
			case "button":
				$array = array(
					"id" => $parametros["id"],
					"valor" => $parametros["valor"] ?? null,
					"click" => $parametros["click"] ?? null,
				);
				if (isset($parametros["disabled"]))
					$array["disabled"] = $parametros["disabled"];
				if (isset($parametros["mostrar"]))
					$array["mostrar"] = $parametros["mostrar"];
			break;
			case "div":
				$array = array(
					"id" => $parametros["id"],
					"atributos_css" => $parametros["atributos_css"] ?? array(),
					"atributos" => $parametros["atributos"] ?? array(),
					"clase" => $parametros["clase"] ?? "",
					"remover_clase" => $parametros["remover_clase"] ?? "",
				);
				if (isset($parametros["mostrar"]))
					$array["mostrar"] = $parametros["mostrar"];
			break;
			case "select":
				$array = array(
					"id" => $parametros["id"],
					"vaciar" => $parametros["vaciar"] ?? false,
					"opciones" => $parametros["opciones"] ?? array(),
					"focus" => $parametros["focus"] ?? false,
					"clase" => $parametros["clase"] ?? "",
					"remover_clase" => $parametros["remover_clase"] ?? "",
					"select2" => $parametros["select2"] ?? false,
					"con_dropdown_parent" => $parametros["con_dropdown_parent"] ?? false,
					"dropdown_parent" => $parametros["dropdown_parent"] ?? "",
				);
				if (isset($parametros["disabled"]))
					$array["disabled"] = $parametros["disabled"];
				if (isset($parametros["select2_extra"]))
					$array["select2_extra"] = $parametros["select2_extra"];
				if (isset($parametros["seleccionado"]) || isset($parametros["valor"]))
					$array["seleccionado"] = $parametros["seleccionado"] ?? $parametros["valor"];
			break;

			default:
				$array = array();
			break;
		}
		$array = array("tipo" => $tipo) + $array;

		return $array;
	}

	/**
	 * Clean Message.
	 * 
	 * @access private
	 * @param string $message
	 * @return string
	 */
	private function cleanMessage($message, $addDot = true) {
		$message = preg_replace("/([.!?])[.!?]+$/", "$1", trim($message));
		$message .= (!$addDot || empty($message) || in_array(substr($message, -1), [".", "?", "!"]) ? "" : ".");
		return $message;
	}

	protected function debugArray($append = true, $just_save_query_or_include_answer = false, $array_print_mode = 1) {
		return $this->isDebugger() ? [true, $append, $this->camelToSnake(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["function"]), $just_save_query_or_include_answer, $array_print_mode] : [];
	}

	private function camelToSnake($string) {
		$string = preg_replace("/([a-z])([A-Z])/", "$1_$2", $string);
		return strtolower($string);
	}

	function camelToTitle($camelCaseString) {
		$withSpaces = preg_replace("/([a-z])([A-Z])/", "$1 $2", $camelCaseString);
		$titleCase = ucwords($withSpaces);
		return $titleCase;
	}

	public function execute($method, ...$args) {
		return $this->__call($method, $args);
	}

	public function myErrorLog($var, $texto, $print = true) {
		if ($print)
			error_log("$texto: " . var_export($var, true) . "in " . __FILE__ . " on line " . __LINE__);

		return true;
	}

	public function generarBreadCrumb($modulos) {
		$codigo = $modulos["cd"] ?? $_SESSION["infoUsuario"]["codigo"];
		$modulos = array_filter($modulos, function($key) {
			return strpos($key, "modulo") !== false;
		}, ARRAY_FILTER_USE_KEY);

		$breadcrumb = '<b><nav aria-label="breadcrumb">';
		$breadcrumb .= '<ol class="breadcrumb">';
		$breadcrumb .= '<li class="breadcrumb-item"><a href="/' . $codigo . '/">Inicio</a></li>';

		if (!empty($modulos)) {
			$i = 1;
			foreach ($modulos as $modulo) {
				$active = (($i == count($modulos)) ? " active" : "");
				$href1 = (($i != count($modulos)) ? '<a href="/' . $codigo . '/' . $modulo . '">' : "");
				$href2 = (($i != count($modulos)) ? '</a>' : "");
				$title = $this->camelToTitle($modulo);
				
				$breadcrumb .= '<li class="breadcrumb-item' . $active . '">' . $href1 . $title . $href2 . '</li>';
				$i++;
			}
		}

		$breadcrumb .= '</ol>';
		$breadcrumb .= '</nav></b>';

		return $breadcrumb;
	}
}
?>