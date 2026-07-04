<?php
function fecha_formateada($fecha, $con_hora = 1, $incluir_segundos = 0) {
	$fecha = explode(" ", $fecha);
	$hora = $fecha[1] ?? "";
	$fecha = $fecha[0];

	$fecha = explode("-", $fecha);
	$dia = $fecha[2];
	$mes = $fecha[1];
	$ano = $fecha[0];

	$fecha = $dia . "/";

	switch ($mes) {
		case "01":
			$fecha .= "Ene";
			break;
		case "02":
			$fecha .= "Feb";
			break;
		case "03":
			$fecha .= "Mar";
			break;
		case "04":
			$fecha .= "Abr";
			break;
		case "05":
			$fecha .= "May";
			break;
		case "06":
			$fecha .= "Jun";
			break;
		case "07":
			$fecha .= "Jul";
			break;
		case "08":
			$fecha .= "Ago";
			break;
		case "09":
			$fecha .= "Sep";
			break;
		case "10":
			$fecha .= "Oct";
			break;
		case "11":
			$fecha .= "Nov";
			break;
		case "12":
			$fecha .= "Dic";
			break;
	}

	$fecha .= "/" . $ano;

	if ($hora != "" && $con_hora == 1) {
		$fecha .= " " . (($incluir_segundos) ? date("H:i:s", strtotime($hora)) : date("H:i", strtotime($hora)));
	}

	return $fecha;
}

/**
 * Devuelve solo la hora de un datetime.
 *
 * @param string $datetime Datetime en formato 'YYYY-MM-DD HH:MM:SS'
 * @param bool $incluir_segundos True para incluir segundos, false solo HH:MM
 * @return string Hora formateada
 */
function solo_tiempo($datetime, $incluir_segundos = false) {
    if (empty($datetime)) {
        return "-"; // si no hay valor
    }

    // Convertimos a timestamp
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return "-"; // si el formato no es válido
    }

    // Formato de salida
    return $incluir_segundos ? date("H:i:s", $timestamp) : date("H:i", $timestamp);
}

/**
 * se asume que la fecha viene como "m-d"
 */
function fecha_formateada3($fecha) {

	$fecha = explode("-", $fecha);
	$mes = $fecha[0];
	$dia = $fecha[1];

	$fecha = $dia . " de ";

	switch ($mes) {
		case "01":
			$fecha .= "Enero";
			break;
		case "02":
			$fecha .= "Febrero";
			break;
		case "03":
			$fecha .= "Marzo";
			break;
		case "04":
			$fecha .= "Abril";
			break;
		case "05":
			$fecha .= "Mayo";
			break;
		case "06":
			$fecha .= "Junio";
			break;
		case "07":
			$fecha .= "Julio";
			break;
		case "08":
			$fecha .= "Agosto";
			break;
		case "09":
			$fecha .= "Septiembre";
			break;
		case "10":
			$fecha .= "Octubre";
			break;
		case "11":
			$fecha .= "Noviembre";
			break;
		case "12":
			$fecha .= "Diciembre";
			break;
	}

	return $fecha;
}

function hora_formateada($hora)
{
	$hora = explode(":", trim($hora));
	return $hora[0] . ":" . $hora[1];
}

function crear_min_horas($v)
{
	return ($v > 9) ? $v : '0' . $v;
}

// funcion para convertir 67.6 a 1 hr 8 min. Si el tiempo es menor a 1 hora, no mostrar hr
function formatear_tiempo($tiempo)
{
	// horas es el piso de tiempo / 60
	$horas = floor($tiempo / 60);
	// minutos es el techo de tiempo % 60
	$minutos = fmod($tiempo, 60);
	$minutos = (($minutos < 1) ? 1 : round($minutos));
	// si el tiempo es menor a 60 minutos, no mostrar horas
	if ($horas == 0) {
		return $minutos . " min";
	} else {
		return $horas . " hr " . $minutos . " min";
	}
}

/**
 * Formatea una fecha o datetime al formato dd/mm/YYYY o dd/mm/YYYY HH:mm:ss.
 * Detecta automáticamente si el valor incluye componente de hora.
 * Usar esta función para cualquier columna de fecha/datetime que se muestre al usuario.
 */
function fecha_display($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '-';
    }
    $partes  = explode(' ', trim($fecha));
    $soloFecha = $partes[0];
    $tieneHora = isset($partes[1]) && $partes[1] !== '00:00:00';
    $f = explode('-', $soloFecha);
    $base = $f[2] . '/' . $f[1] . '/' . $f[0];
    return $tieneHora ? $base . ' ' . $partes[1] : $base;
}

function fecha_formateada2($fecha, $conHora = 0)
{
	$fecha = explode(" ", $fecha);
	$hora = $fecha[1];
	$fecha = $fecha[0];

	$fecha = explode("-", $fecha);
	$dia = $fecha[2];
	$mes = $fecha[1];
	$ano = $fecha[0];

	$fecha = $dia . "/" . $mes . "/" . $ano;

	if ($hora != "" && $conHora == 1) {
		$fecha .= " " . date("H:i", strtotime($hora));
	}

	return $fecha;
}

function fecha_natural($datetimeStr, $incluir_dia_semana = 1, $incluir_hora = 1) {
	$dias = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
	$meses = [
		1 => "enero", "febrero", "marzo", "abril", "mayo", "junio",
		"julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
	];

	$date = new DateTime($datetimeStr);

	$diaSemanaIndex = (int)$date->format("w"); // 0 (Domingo) to 6 (Sábado)
	$diaSemana = (($incluir_dia_semana) ? $dias[$diaSemanaIndex] : "");

	$diaMes = (int)$date->format("j"); // 1-31
	$mes = $meses[(int)$date->format("n")]; // 1-12
	$anio = $date->format("Y");

	$hora = $date->format("g:i a"); // formato de 12 horas com am/pm

	$s = ((explode(":", $hora)[0] != 1) ? "s" : "");

	$hora = (($incluir_hora) ? " a la$s " . $hora : "");

	return ((!empty($diaSemana)) ? "$diaSemana " : "") . "$diaMes de $mes de $anio$hora";
}

function formatear_hora($hora) {
	if (empty($hora) || $hora == "00:00:00" || $hora == "00:00") {
		return "N/A";
	}
	$date = new DateTime($hora);

	return $date->format("g:i a");
}

$regexCurp = '/^[A-Z]{1}[AEIOUX]{1}[A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM]'
			. '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)'
			. '[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]\d$/i';

function esCurpValida($curp) {
	global $regexCurp;
	return (bool) preg_match($regexCurp, strtoupper($curp));
}

// Estricto sin guiones
$regexRfc = '/^([A-ZÑ&]{3}|[A-ZÑ&]{4})'            // 3 (moral) ó 4 (física) letras
			. '\d{2}(0[1-9]|1[0-2])'                 // mes
			. '(0[1-9]|[12]\d|3[01])'                // día
			. '[A-Z0-9]{3}$/i';                      // homoclave

// Variante que permite guiones intermedios (AAA-YYMMDD-XXX)
$regexRfcConGuiones = '/^([A-ZÑ&]{3,4})-?\d{2}(0[1-9]|1[0-2])-?(0[1-9]|[12]\d|3[01])-?[A-Z0-9]{3}$/i';

function esRfcValido($rfc, $permitirGuiones = false) {
	global $regexRfc, $regexRfcConGuiones;
	$rx = $permitirGuiones ? $regexRfcConGuiones : $regexRfc;
	return (bool) preg_match($rx, strtoupper($rfc));
}

function log_time($tag = null) {
	global $lastCheckpoint, $timings;

	$now = microtime(true);
	$diff = $now - $lastCheckpoint;
	$lastCheckpoint = $now;

	$timings[] = [
		"time" => $diff,
		"tag" => $tag
	];
}

function fileExistsExternal($url) {
	$parsed = parse_url($url);

	if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
		return false; // Not a valid URL
	}

	// Rebuild the URL with encoded path and query
	$encodedUrl = $parsed['scheme'] . '://' . $parsed['host'];

	if (isset($parsed['port'])) {
		$encodedUrl .= ':' . $parsed['port'];
	}

	if (isset($parsed['path'])) {
		$encodedUrl .= implode('/', array_map('rawurlencode', explode('/', $parsed['path'])));
	}

	if (isset($parsed['query'])) {
		$encodedUrl .= '?' . $parsed['query']; // optional: encode this too
	}

	// Now check headers
	$headers = @get_headers($encodedUrl, 1);
	if (!$headers) return false;

	$statusLine = $headers[0];
	return (
		strpos($statusLine, '200') !== false ||
		strpos($statusLine, '301') !== false ||
		strpos($statusLine, '302') !== false
	);
}

function generarPeriodoEnLetra(string $fechaInicio, string $fechaFin, string $formatoEntrada = 'Y-m-d'): string { 
	$inicio = DateTime::createFromFormat($formatoEntrada, $fechaInicio);
	$fin    = DateTime::createFromFormat($formatoEntrada, $fechaFin);

	if (!$inicio || !$fin) {
		return '';
	}

	// Meses en español, en mayúsculas
	$meses = [
		1 => 'ENERO',
		2 => 'FEBRERO',
		3 => 'MARZO',
		4 => 'ABRIL',
		5 => 'MAYO',
		6 => 'JUNIO',
		7 => 'JULIO',
		8 => 'AGOSTO',
		9 => 'SEPTIEMBRE',
		10 => 'OCTUBRE',
		11 => 'NOVIEMBRE',
		12 => 'DICIEMBRE',
	];

	$diaIni  = (int)$inicio->format('j');
	$mesIni  = (int)$inicio->format('n');
	$anioIni = (int)$inicio->format('Y');

	$diaFin  = (int)$fin->format('j');
	$mesFin  = (int)$fin->format('n');
	$anioFin = (int)$fin->format('Y');

	$mesNombreIni = $meses[$mesIni];
	$mesNombreFin = $meses[$mesFin];

	// Mismo año
	if ($anioIni === $anioFin) {
		// Mismo mes
		if ($mesIni === $mesFin) {
			// 19 AL 27 DE DICIEMBRE DE 2025
			return sprintf(
				'%d AL %d DE %s DE %d',
				$diaIni,
				$diaFin,
				$mesNombreIni,
				$anioIni
			);
		}

		// Diferente mes, mismo año
		// 29 DE NOVIEMBRE AL 5 DE DICIEMBRE 2025
		return sprintf(
			'%d DE %s AL %d DE %s %d',
			$diaIni,
			$mesNombreIni,
			$diaFin,
			$mesNombreFin,
			$anioIni
		);
	}

	// Diferente año y posiblemente diferente mes
	// 27 DE DICIEMBRE 2025 AL 6 DE ENERO 2026
	return sprintf(
		'%d DE %s %d AL %d DE %s %d',
		$diaIni,
		$mesNombreIni,
		$anioIni,
		$diaFin,
		$mesNombreFin,
		$anioFin
	);
}

function smart_number_format($numero, $digitos = 2) {
	return ((fmod($numero, 1) == 0) ? (string)(int)$numero : number_format($numero, $digitos, '.', ''));
}

/**
 * Escapa un valor para salida segura en HTML. Nombre estandar usado en
 * vistas/fancys del sistema (ver agent_manuals/agents_MODULO.md, 5.1).
 */
function formatearLabel($valor) {
	return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}