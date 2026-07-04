<?php
class Queries {

	private $con, $isDebugger, $pdo, $auditContext, $auditActionMap;

	public function __construct($con, $pdo = null, $isDebugger = 0, $auditContext = array()) {
		$this->isDebugger = $isDebugger;
		$this->con = $con;
		$this->pdo = $pdo;
		$this->auditContext = array(
			"idmodulo" => null,
			"idsubmodulo" => null,
			"idempresa" => null,
			"idusuario" => null,
			"descripcion_prefijo" => ""
		);
		$this->auditActionMap = array(
			"insert" => 1,
			"update" => 2,
			"delete" => 3
		);

		if (!empty($auditContext)) {
			$this->setAuditContext($auditContext);
		}
	}

	public function setAuditContext($context = array()) {
		if (!is_array($context)) {
			return;
		}

		$this->auditContext = array_merge($this->auditContext, $context);
	}

	public function executeQuery($query, $params = array(), $get_insert_id = false, $mensaje_comun = "", $guardar_query = false, $append = false, $filename = "", $get_affected_rows = false, $auditar = false) {
		$mensaje_comun = (($this->isDebugger) ? "" : $mensaje_comun);
		if ($guardar_query) {
			$array = array(
				"query" => $query,
				"valores" => $params
			);
			$this->guardarQuery($array, (($append) ? FILE_APPEND : ""), $filename);
		}

		$auditPayload = $this->prepareAuditPayload($query, $params, $auditar);

		$statement = mysqli_prepare($this->con, $query);
		if (!$statement) {
			$errno = mysqli_errno($this->con);
			$message = mysqli_error($this->con);
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		if (!empty($params)) {
			$respuesta = mysqli_stmt_bind_param($statement, str_repeat("s", count($params)), ...$params);
			if (!$respuesta) {
				$errno = mysqli_stmt_errno($statement);
				$message = mysqli_stmt_error($statement);
				$message = ((empty($message)) ? error_get_last()["message"] : $message);
				mysqli_stmt_close($statement);
				throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
			}
		}
		$respuesta = mysqli_stmt_execute($statement);
		if (!$respuesta) {
			$errno = mysqli_stmt_errno($statement);
			$message = mysqli_stmt_error($statement);
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			mysqli_stmt_close($statement);
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		$insert_id = mysqli_stmt_insert_id($statement);
		$affected_rows = mysqli_stmt_affected_rows($statement);
		if ($get_insert_id)
			$id = $insert_id;
		if ($get_affected_rows)
			$affected_rows_response = $affected_rows;
		mysqli_stmt_close($statement);

		if (!empty($auditPayload)) {
			$this->finalizeAuditPayload($auditPayload, $insert_id, $affected_rows);
		}

		return $id ?? $affected_rows_response ?? true;
	}

	/**
	 * Fetch Results.
	 * 
	 * @access public
	 * @param string $query
	 * @param array $params
	 * @param bool $fetch_all to decide whether to retrieve one or all rows
	 * @param bool $guardar_query to decide whether to save the query or not in a text file
	 * @param bool $append 
	 * @param string $filename the name of the file where the query is placed
	 * @param string $incluir_respuesta to decide if the result is also saved in the file
	 * @return array
	 */
	public function fetchResults($query, $params = array(), $fetch_all = true, $mensaje_comun = "", $guardar_query = false, $append = false, $filename = "", $incluir_respuesta = false, $accept_empty = true) {
		if ($guardar_query) {
			$array = array(
				"query" => $query,
				"valores" => $params
			);
			$this->guardarQuery($array, (($append) ? FILE_APPEND : ""), $filename);
		}

		$statement = mysqli_prepare($this->con, $query);
		if (!$statement) {
			$errno = mysqli_errno($this->con);
			$message = mysqli_error($this->con);
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		if (!empty($params)) {
			$respuesta = mysqli_stmt_bind_param($statement, str_repeat("s", count($params)), ...$params);
			if (!$respuesta) {
				$errno = mysqli_stmt_errno($statement);
				$message = mysqli_stmt_error($statement);
				$message = ((empty($message)) ? error_get_last()["message"] : $message);
				mysqli_stmt_close($statement);
				throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
			}
		}
		$respuesta = mysqli_stmt_execute($statement);
		if (!$respuesta) {
			$errno = mysqli_stmt_errno($statement);
			$message = mysqli_stmt_error($statement);
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			mysqli_stmt_close($statement);
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		$result = mysqli_stmt_get_result($statement);
		if ($fetch_all)
			$data = mysqli_fetch_all($result, MYSQLI_ASSOC);
		else
			$data = mysqli_fetch_assoc($result);
		mysqli_stmt_close($statement);

		if ($incluir_respuesta) {
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/" . ((!empty($filename)) ? $filename : (($append) ? "querieshistorial" : "queries")) . ".txt", print_r($data, true), FILE_APPEND);
		}

		if (empty($data) && !$accept_empty) {
			throw new Exception(((!empty($mensaje_comun)) ? $mensaje_comun : "Registros no encontrados") . ".", 1);
		}

		return $data;
	}

	/**
	 * Execute Query PDO.
	 * 
	 * @access public
	 * @param string $query
	 * @param array $params
	 * @param bool $get_insert_id to decide whether to retrieve one or all rows
	 * @param bool $get_affected_rows to decide whether to get or not the amount of affected rows
	 * @param string $mensaje_comun the message to show to the non-debugger user
	 * @param bool $guardar_query to decide whether to save the query or not in a text file
	 * @param bool $append 
	 * @param string $filename the name of the file where the query is placed
	 * @return array
	 */
	public function executeQueryP($query, $params = array(), $get_insert_id = false, $get_affected_rows = false, $mensaje_comun = "", $guardar_query = false, $append = false, $filename = "") {
		$mensaje_comun = (($this->isDebugger) ? "" : $mensaje_comun);
		if ($guardar_query) {
			$array = array(
				"query" => $query,
				"valores" => $params
			);
			$this->guardarQuery($array, (($append) ? FILE_APPEND : ""), $filename);
		}

		$statement = $this->pdo->prepare($query);
		if (!$statement) {
			$errno = $this->pdo->errorCode();
			$message = $this->pdo->errorInfo()[2];
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		$respuesta = $statement->execute($params);
		if (!$respuesta) {
			$errno = $this->pdo->errorCode();
			$message = $this->pdo->errorInfo()[2];
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			$statement = null;
			throw new Exception($mensaje_comun ?: "Error: " . $message, ((!empty($mensaje_comun)) ? 1 : $errno));
		}
		if ($get_insert_id)
			$id = $this->pdo->lastInsertId();
		if ($get_affected_rows)
			$affected_rows = $statement->rowCount();
		$statement = null;

		return $id ?? $affected_rows ?? true;
	}

	/**
	 * Fetch Results PDO.
	 * 
	 * @access public
	 * @param string $query
	 * @param array $params
	 * @param bool $fetch_all to decide whether to retrieve one or all rows
	 * @param bool $accept_empty to validate empty fetched response before returning. If the response is empty, an exception is thrown
	 * @param string $mensaje_comun the message to show to the non-debugger user
	 * @param bool $guardar_query to decide whether to save the query or not in a text file
	 * @param bool $append 
	 * @param string $filename the name of the file where the query is placed
	 * @param string $incluir_respuesta to decide if the result is also saved in the file
	 * @return array
	 */
	public function fetchResultsP($query, $params = array(), $fetch_all = true, $accept_empty = true, $mensaje_comun = "", $guardar_query = false, $append = false, $filename = "", $incluir_respuesta = false) {
		$mensaje_comun1 = (($this->isDebugger) ? "" : $mensaje_comun);
		if ($guardar_query) {
			$array = array(
				"query" => $query,
				"valores" => $params
			);
			$this->guardarQuery($array, (($append) ? FILE_APPEND : ""), $filename);
		}

		$statement = $this->pdo->prepare($query);
		if (!$statement) {
			$errno = $this->pdo->errorCode();
			$message = $this->pdo->errorInfo()[2];
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			throw new Exception($mensaje_comun1 ?: "Error: " . $message, ((!empty($mensaje_comun1)) ? 1 : $errno));
		}
		$respuesta = $statement->execute($params);
		if (!$respuesta) {
			$errno = $this->pdo->errorCode();
			$message = $this->pdo->errorInfo()[2];
			$message = ((empty($message)) ? error_get_last()["message"] : $message);
			$statement = null;
			throw new Exception($mensaje_comun1 ?: "Error: " . $message, ((!empty($mensaje_comun1)) ? 1 : $errno));
		}
		if ($fetch_all)
			$data = $statement->fetchAll(PDO::FETCH_ASSOC);
		else
			$data = $statement->fetch();
		$statement = null;

		if ($incluir_respuesta) {
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/" . ((!empty($filename)) ? $filename : (($append) ? "querieshistorial" : "queries")) . ".txt", print_r($data, true), FILE_APPEND);
		}

		if (empty($data) && !$accept_empty) {
			throw new Exception(((!empty($mensaje_comun)) ? rtrim($mensaje_comun, ".") : "No data found") . ".", 1);
		}

		return $data;
	}

	/**
	 * Guardar Query. Guarda un query en un archivo de texto ubicado en txts/. Este método recibe: 1. un post con el query del tipo que incluye signos de interrogacion (?) + el arreglo de valores a ligar, 2. el tipo de guardado 3. el nombre del archivo en que se guardarán los queries (si no se provee ninguno, será queries o querieshistorial según sea el caso). Aqui se hace esa sustitucion para ver cómo sería el query si se ejecutara directamente en la base de datos. Este método es útil para depurar queries.
	 * 
	 * @access public
	 * @param array $post
	 * @return array
	 */
	public function guardarQuery($post, $tipo_put_contents = FILE_APPEND, $nombre_archivo = "") {

		$respuesta = false;
		$query = $post["query"];
		$valores = $post["valores"];

		$query = str_replace("\t", "    ", $query);

		// despues del primer salto de linea (\n o PHP_EOL) en la cadena del query, calculamos la cantidad de espacios en blanco que hay
		$espacios = 0;
		for ($i = 2; $i < strlen($query); $i++) {
			if ($query[$i] == " ") {
				$espacios++;
			} else {
				break;
			}
		}

		// recorremos cada una de las lineas del query y le quitamos la cantidad de espacios en blanco que indica $espacios
		$query = explode("\n", $query);
		foreach ($query as $key => $value) {
			// si la linea contiene al menos un #, la eliminamos
			if (strpos($value, "#") !== false || trim($value) == "") {
				unset($query[$key]);
			} else {
				$query[$key] = substr($value, $espacios);
			}
		}
		$query = implode("\n", $query);

		$query = str_replace("%s", "per_c_s", $query);
		$query = str_replace("?", "'%s'", $query);
		// en vsprintf, ignoramos todos los % que no tengan una s justo después
		$query = preg_replace("/%([^s])/", "||$1", $query);
		$query = vsprintf($query, $valores);
		$query = str_replace("||", "%", $query);
		$query = str_replace("per_c_s", "%s", $query);

		foreach ($valores as $key => $value) {
			if (!is_numeric($key)) {
				$query = str_replace(":$key", "'$value'", $query);
			}
		}

		// a todos los textos "'null'" les quitamos las comillas sencillas
		$query = str_replace("'null'", "null", $query);

		$fecha = date("Y-m-d H:i:s");

		// Obtenemos el archivo y línea del archivo que llamó a guardar el query. Esto con propósitos de depuración
		$backtrace = debug_backtrace();
		$file = $backtrace[1]["file"];
		$line = $backtrace[1]["line"];

		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/txts/".((!empty($nombre_archivo)) ? $nombre_archivo : "querieshistorial").".txt")) {
			touch($_SERVER["DOCUMENT_ROOT"]."/txts/".((!empty($nombre_archivo)) ? $nombre_archivo : "querieshistorial").".txt");
			chmod($_SERVER["DOCUMENT_ROOT"]."/txts/".((!empty($nombre_archivo)) ? $nombre_archivo : "querieshistorial").".txt", 0777);
		}

		if ($tipo_put_contents == FILE_APPEND) {
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/".((!empty($nombre_archivo)) ? $nombre_archivo : "querieshistorial").".txt", $query . " -- File: " . $file . PHP_EOL . " -- Line: " . $line . PHP_EOL . " -- " . $fecha . PHP_EOL, FILE_APPEND);
		} else {
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/txts/".((!empty($nombre_archivo)) ? $nombre_archivo : "queries").".txt", $query . " -- File: " . $file . PHP_EOL . " -- Line: " . $line . PHP_EOL);
		}

		$respuesta = array("result" => "success");
	}

	public function debugQuery(string $sql, array $params): string{
		foreach ($params as $value) {
			if (is_null($value)) {
				$value = 'NULL';
			} elseif (is_numeric($value)) {
				// se queda igual
			} else {
				// escapado básico para visualización
				$value = "'" . addslashes($value) . "'";
			}

			$sql = preg_replace('/\?/', $value, $sql, 1);
		}

		return $sql;
	}

	private function prepareAuditPayload($query, $params, $auditar) {
		if (empty($auditar)) {
			return null;
		}

		$context = $this->buildAuditContext($auditar);
		$sqlDebug = $this->debugQuery($query, $params);
		$info = $this->detectActionAndTable($sqlDebug);

		if (empty($info["action"]) || empty($info["table"])) {
			return null;
		}

		$pkColumn = $context["columna"] ?? null;
		$pkValue = $context["idregistro"] ?? null;
		if (empty($pkColumn) || is_null($pkValue)) {
			$detectedPk = $this->detectPkFromSql($sqlDebug, $info["table"], $pkColumn);
			$pkColumn = $pkColumn ?: $detectedPk["column"];
			if (is_null($pkValue)) {
				$pkValue = $detectedPk["value"];
			}
		}

		return array(
			"context" => $context,
			"action" => $info["action"],
			"table" => $info["table"],
			"pk_column" => $pkColumn,
			"pk_value" => $pkValue,
			"query" => $query,
			"sql_debug" => $sqlDebug,
			"params" => $params
		);
	}

	private function finalizeAuditPayload($payload, $insertId, $affectedRows) {
		try {
			$action = $payload["action"];
			$pkColumn = $payload["pk_column"];
			$pkValue = $payload["pk_value"];

			if ($action === "insert" && is_null($pkValue) && !empty($insertId)) {
				$pkValue = $insertId;
			}

			$data = $this->buildLogData($payload, $pkColumn, $pkValue, array());

			$excludedKeys = array("proceso", "controlador", "idempresa", "idEmpresa", "ultimo_acceso", "idmodulo", "idsubmodulo", "ruta_pdf");

			$request = null;
			if (!empty($_POST) && is_array($_POST)) {
				$request = $_POST;
			}

			if (is_array($request)) {
				foreach ($excludedKeys as $key) {
					unset($request[$key]);
				}
			}

			$this->insertAuditLog($data, $request);
		} catch (\Throwable $e) {
			if ($this->isDebugger) {
				error_log("error: " . ($e->getMessage() ?: error_get_last()["message"]) . " in " . __FILE__ . " on line " . ($e->getLine() ?: __LINE__));
			}
			return;
		}
	}

	private function buildAuditContext($auditar) {
		$config = is_array($auditar) ? $auditar : array();
		$sessionData = $_SESSION["infoUsuario"] ?? array();

		$context = array_merge($this->auditContext, $config);
		$context["idempresa"] = $context["idempresa"] ?? ($sessionData["idempresa"] ?? null);
		$context["idusuario"] = $context["idusuario"] ?? ($sessionData["idusuario"] ?? null);
		$context["idmodulo"] = $context["idmodulo"] ?? 0;
		$context["idsubmodulo"] = $context["idsubmodulo"] ?? $_POST["idsubmodulo"] ?? 0;

		return $context;
	}

	private function detectActionAndTable($query) {
		$normalized = trim(preg_replace('/\s+/', ' ', $query));
		$action = null;
		$table = null;

		if (preg_match('/^insert\s+into\s+`?([a-zA-Z0-9_]+)`?/i', $normalized, $matches)) {
			$action = "insert";
			$table = $matches[1];
		} elseif (preg_match('/^update\s+`?([a-zA-Z0-9_]+)`?/i', $normalized, $matches)) {
			$action = "update";
			$table = $matches[1];
		} elseif (preg_match('/^delete\s+from\s+`?([a-zA-Z0-9_]+)`?/i', $normalized, $matches)) {
			$action = "delete";
			$table = $matches[1];
		}

		$table = $this->sanitizeIdentifier($table);

		return array(
			"action" => $action,
			"table" => $table
		);
	}

	private function detectPkFromSql($query, $table = null, $preferredColumn = null) {
		if (!preg_match('/\bwhere\b(.+)$/is', $query, $whereMatch)) {
			return array("column" => null, "value" => null);
		}

		$where = $whereMatch[1];
		if (!preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b\s*=\s*(NULL|\'[^\']*\'|[0-9]+(?:\.[0-9]+)?)/i', $where, $matches, PREG_SET_ORDER)) {
			return array("column" => null, "value" => null);
		}

		$candidates = array();
		foreach ($matches as $match) {
			$column = $this->sanitizeIdentifier($match[1]);
			if (is_null($column)) {
				continue;
			}

			$value = $match[2];
			if (strcasecmp($value, "NULL") === 0) {
				$value = null;
			} elseif (preg_match('/^\'.*\'$/', $value)) {
				$value = substr($value, 1, -1);
			}

			$candidates[] = array(
				"column" => $column,
				"value" => $value
			);
		}

		if (empty($candidates)) {
			return array("column" => null, "value" => null);
		}

		if (!empty($preferredColumn)) {
			$preferredColumn = $this->sanitizeIdentifier($preferredColumn);
			if (!is_null($preferredColumn)) {
				foreach ($candidates as $candidate) {
					if (strcasecmp($candidate["column"], $preferredColumn) === 0) {
						return $candidate;
					}
				}
			}
		}

		if (!empty($table)) {
			$expectedPk = $this->buildExpectedPkColumnFromTable($table);
			if (!empty($expectedPk)) {
				foreach ($candidates as $candidate) {
					if (strcasecmp($candidate["column"], $expectedPk) === 0) {
						return $candidate;
					}
				}
			}
		}

		foreach ($candidates as $candidate) {
			if (preg_match('/^id[a-zA-Z0-9_]*$/', $candidate["column"])) {
				return $candidate;
			}
		}

		return $candidates[0];
	}

	private function buildExpectedPkColumnFromTable($table) {
		$table = $this->sanitizeIdentifier((string)$table);
		if (is_null($table) || $table === "") {
			return null;
		}

		$base = $table;
		if (strpos($base, "t") === 0 && strlen($base) > 1) {
			$base = substr($base, 1);
		}

		if (substr($base, -3) === "ies") {
			$base = substr($base, 0, -3) . "y";
		} elseif (substr($base, -1) === "s") {
			$base = substr($base, 0, -1);
		}

		$base = $this->sanitizeIdentifier($base);
		if (is_null($base) || $base === "") {
			return null;
		}

		return "id" . $base;
	}

	private function sanitizeIdentifier($value) {
		if (!is_string($value) || $value === "") {
			return null;
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
			return null;
		}

		return $value;
	}

	private function buildLogData($payload, $pkColumn, $pkValue, $changedFields) {
		$context = $payload["context"];
		$action = $payload["action"];
		$table = $payload["table"];
		$idAccion = $this->auditActionMap[$action] ?? 0;
		$descripcion = $context["descripcion"] ?? "";

		if ($descripcion === "") {
			$descripcion = $this->buildDefaultDescription($action, $table, $pkValue, $changedFields, $context["descripcion_prefijo"] ?? "");
		}

		return array(
			"idmodulo" => (string) ($context["idmodulo"] ?? 0),
			"idsubmodulo" => (string) ($context["idsubmodulo"] ?? $_POST["idsubmodulo"] ?? 0),
			"idaccion" => (string) $idAccion,
			"idusuario" => (string) ($context["idusuario"] ?? 0),
			"idempresa" => (string) ($context["idempresa"] ?? 0),
			"tabla" => (string) $table,
			"columna" => (string) ($pkColumn ?: "id"),
			"idregistro" => (string) ($pkValue ?? 0),
			"descripcion" => $descripcion
		);
	}

	private function buildDefaultDescription($action, $table, $pkValue, $changedFields, $prefijo = "") {
		$prefix = trim((string) $prefijo);
		$registro = (!is_null($pkValue) && $pkValue !== "") ? " #{$pkValue}" : "";

		switch ($action) {
			case "insert":
				$base = "Se insertó un registro en {$table}{$registro}";
				break;
			case "delete":
				$base = "Se eliminó un registro de {$table}{$registro}";
				break;
			default:
				$total = count($changedFields);
				$detalle = ($total > 0) ? " ({$total} campos modificados)" : "";
				$base = "Se actualizó un registro en {$table}{$registro}{$detalle}";
				break;
		}

		return ($prefix !== "") ? ($prefix . ": " . $base) : $base;
	}

	private function insertAuditLog($data, $request = null) {
		try {
			if (!($this->con instanceof mysqli)) {
				return;
			}

			$peticionJson = null;
			if (is_array($request)) {
				$peticionJson = json_encode($request, JSON_UNESCAPED_UNICODE);
				if ($peticionJson === false) {
					$peticionJson = null;
				}
			}

			$query = "
			insert into
				tlogsistema
				(
					idempresa,
					idmodulo,
					idsubmodulo,
					idaccion,
					idusuario,
					tabla,
					columna,
					idregistro,
					descripcion,
					peticion_json
				)
			values
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			";

			$stmt = mysqli_prepare($this->con, $query);
			if (!$stmt) {
				return;
			}

			$ok = mysqli_stmt_bind_param(
				$stmt,
				"ssssssssss",
				$data["idempresa"],
				$data["idmodulo"],
				$data["idsubmodulo"],
				$data["idaccion"],
				$data["idusuario"],
				$data["tabla"],
				$data["columna"],
				$data["idregistro"],
				$data["descripcion"],
				$peticionJson
			);
			if (!$ok) {
				mysqli_stmt_close($stmt);
				return;
			}

			$executed = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		} catch (\Throwable $e) {
			if ($this->isDebugger) {
				error_log("error: " . ($e->getMessage() ?: error_get_last()["message"]) . " in " . __FILE__ . " on line " . ($e->getLine() ?: __LINE__));
			}
			return;
		}
	}
}
?>