<?php
/**
 * Helpers para armar tickets como comandos ESC/POS crudos.
 * El resultado (un string de bytes) se manda tal cual, en base64, a QZ Tray
 * desde el navegador (ver js/impresion.js / imprimirTicket), que lo reenvía a
 * la impresora térmica.
 *
 * Es copia de assets/php/otros/escpos.php del punto de venta (La-Jugueria) para
 * que los tickets del panel salgan con el mismo formato: si se cambia uno, cambiar
 * el otro. $infoticket usa las mismas llaves (negocio, calle, numero, ...).
 */

/**
 * Columnas de texto que caben en una linea de la impresora termica en Font A,
 * segun el tamano de impresion configurado en la sucursal
 * (tsucursales.ticket_tamanoimpresion):
 * - 72 mm (rollo de 80 mm): 42 caracteres. Es lo que imprime la Bixolon
 *   SRP-330II (no 48, que fue lo que se asumio al migrar a QZ Tray y hacia
 *   que cada separador y cada fila arrastraran 6 caracteres al renglon
 *   siguiente).
 * - 58 mm: 32 caracteres (cabezal de 384 puntos, el estandar en ese rollo).
 * Todas las tablas del ticket se arman a partir de este ancho; si se cambia de
 * modelo de impresora, este es el unico lugar a tocar.
 */
function anchoTicket($tamanoImpresion){
	$columnas = array(72 => 42, 58 => 32);
	$tamanoImpresion = (int) $tamanoImpresion;
	return isset($columnas[$tamanoImpresion]) ? $columnas[$tamanoImpresion] : $columnas[72];
}

function escposInit(){
	return chr(27).chr(64); // ESC @ - inicializa la impresora
}

function escposAlign($align){
	$valores = array("left" => 0, "center" => 1, "right" => 2);
	$valor = isset($valores[$align]) ? $valores[$align] : 0;
	return chr(27).chr(97).chr($valor); // ESC a n
}

function escposBold($on){
	return chr(27).chr(69).chr($on ? 1 : 0); // ESC E n
}

function escposTamano($doble){
	return chr(29).chr(33).chr($doble ? 0x11 : 0x00); // GS ! n (doble alto+ancho o normal)
}

function escposLinea($texto = ""){
	return $texto."\n";
}

/**
 * Arma una fila con varias columnas de ancho fijo (para tablas tipo
 * "CANT. | PRODUCTO | PRECIO | IMPORTE" o "TOTAL ... $123.45").
 *
 * @param array $columnas cada elemento es [texto, ancho, alinear] con alinear = 'left'|'right'
 * @return string
 */
function escposFila($columnas){
	$linea = "";
	foreach($columnas as $columna){
		list($texto, $ancho, $align) = $columna;
		$linea .= $align === "right"
			? str_pad($texto, $ancho, " ", STR_PAD_LEFT)
			: str_pad($texto, $ancho, " ", STR_PAD_RIGHT);
	}
	return rtrim($linea)."\n";
}

/**
 * Texto libre partido por palabra completa para que ninguna palabra quede
 * cortada al final del renglon. Respeta la alineacion activa.
 */
function escposParrafo($texto, $ancho){
	$escpos = "";
	foreach(dividirTexto($texto, $ancho) as $linea){
		$escpos .= escposLinea($linea);
	}
	return $escpos;
}

function escposSeparador($ancho){
	return escposLinea(str_repeat("=", $ancho));
}

/**
 * Fila "ETIQUETA ........ $123.45": el valor ocupa siempre las ultimas 11
 * columnas. Si la etiqueta no cabe en el espacio restante (pasa en 58 mm con
 * textos como "FOLIO INICIAL DEL CORTE") se parte en varios renglones y el
 * valor queda en el ultimo.
 */
function escposFilaMonto($etiqueta, $valor, $ancho){
	$anchoValor = 11;
	$lineas = dividirTexto($etiqueta, $ancho - $anchoValor);
	$ultima = array_pop($lineas);
	$escpos = "";
	foreach($lineas as $linea){
		$escpos .= escposLinea($linea);
	}
	return $escpos.escposFila(array(array($ultima, $ancho - $anchoValor, "left"), array($valor, $anchoValor, "right")));
}

/**
 * Encabezado comun de todos los tickets: datos fiscales de la sucursal,
 * fecha/hora/folio y el primer separador.
 */
function escposEncabezado($infoticket, $lineaTicket, $ancho){
	$escpos = escposAlign("center");
	$escpos .= escposBold(true).escposTamano(true);
	// En doble ancho cada caracter ocupa dos columnas.
	$escpos .= escposParrafo($infoticket["negocio"], (int) floor($ancho / 2));
	$escpos .= escposTamano(false).escposBold(false);
	$escpos .= escposParrafo($infoticket["calle"]." No. ".$infoticket["numero"], $ancho);
	$escpos .= escposParrafo($infoticket["colonia"]." C.P. ".$infoticket["codigopostal"], $ancho);
	$escpos .= escposParrafo($infoticket["ciudad"], $ancho);
	$escpos .= escposParrafo($infoticket["nombre"], $ancho);
	$escpos .= escposParrafo($infoticket["rfc"], $ancho);
	$escpos .= escposParrafo($infoticket["regimen"], $ancho);
	$escpos .= escposParrafo($lineaTicket, $ancho);
	$escpos .= escposAlign("left");
	$escpos .= escposSeparador($ancho);
	return $escpos;
}

/**
 * Anchos de las columnas CANT | PRODUCTO | PRECIO | IMPORTE de la tabla de
 * productos vendidos (venta y reimpresion). Ambos tamanos usan la misma
 * distribucion; en 58 mm solo se angostan las columnas. Siempre suman el
 * ancho del ticket.
 */
function columnasProductos($ancho){
	// 58 mm: 5 + 11 + 8 + 8 = 32. PRECIO e IMPORTE caben hasta $999.99; un
	// importe mayor lo resuelve escposFilaProducto.
	if($ancho == 32){
		return array(5, 11, 8, 8);
	}
	// 72 mm: 5 + 17 + 9 + 11 = 42
	return array(5, $ancho - 25, 9, 11);
}

function escposEncabezadoProductos($ancho){
	list($cant, $producto, $precio, $importe) = columnasProductos($ancho);
	return escposFila(array(
		array("CANT", $cant, "left"),
		array("PRODUCTO", $producto, "left"),
		array("PRECIO", $precio, "right"),
		array("IMPORTE", $importe, "right")
	));
}

/**
 * Un renglon de la tabla de productos. Si el nombre no cabe en su columna se
 * parte en varios renglones dentro de esa misma columna; cantidad, precio e
 * importe van solo en el primero.
 *
 * Si el precio o el importe no caben en su columna (en 58 mm, $1,000.00 o
 * mas), en vez de desacomodar el renglon se imprimen en un renglon extra
 * debajo del nombre, alineados a la derecha.
 */
function escposFilaProducto($cantidad, $nombre, $precio, $importe, $ancho){
	list($anchoCant, $anchoProducto, $anchoPrecio, $anchoImporte) = columnasProductos($ancho);
	$escpos = "";
	if(strlen($precio) > $anchoPrecio || strlen($importe) > $anchoImporte){
		$numLinea = 1;
		foreach(dividirTexto($nombre, $anchoProducto) as $linea){
			$escpos .= escposFila(array(
				array($numLinea==1 ? $cantidad : "", $anchoCant, "left"),
				array($linea, $anchoProducto, "left")
			));
			$numLinea++;
		}
		return $escpos.escposFila(array(
			array("", $anchoCant, "left"),
			array($precio, $ancho - $anchoCant - 11, "right"),
			array($importe, 11, "right")
		));
	}
	$numLinea = 1;
	foreach(dividirTexto($nombre, $anchoProducto) as $linea){
		$escpos .= escposFila(array(
			array($numLinea==1 ? $cantidad : "", $anchoCant, "left"),
			array($linea, $anchoProducto, "left"),
			array($numLinea==1 ? $precio : "", $anchoPrecio, "right"),
			array($numLinea==1 ? $importe : "", $anchoImporte, "right")
		));
		$numLinea++;
	}
	return $escpos;
}

/**
 * Cierra el ticket: avanza el papel y lo corta.
 *
 * Es indispensable emitirlo. Con la extension printer_* esto lo hacia solo el
 * driver GDI de Windows al cerrar el documento (printer_end_doc); mandando
 * bytes crudos por QZ Tray el driver ya no interviene, y sin esto las ultimas
 * lineas se quedan dentro de la impresora (hay que darle FEED a mano).
 *
 * El avance previo existe porque el cabezal termico esta ~2cm antes de la
 * cuchilla: lo ya impreso en ese tramo debe salir antes de cortar.
 */
function escposCorte($lineas = 4){
	return str_repeat("\n", $lineas).chr(29).chr(86).chr(66).chr(0); // GS V B n - corte parcial
}

function escposAbrirCajon(){
	// Mismo pulso que ya se mandaba con printer_write() en el código anterior.
	return chr(27).chr(112).chr(0).chr(100).chr(250);
}

/**
 * Parte un texto largo en varias líneas de máximo $length caracteres,
 * cortando por palabra completa (para nombres de producto largos). Una palabra
 * que por si sola no cabe en el renglon se corta en pedazos, para que nunca se
 * desborde la columna y desacomode el resto de la fila.
 */
function dividirTexto($cadena, $length){
	$palabras = array();
	foreach(explode(" ", trim((string) $cadena)) as $palabra){
		foreach(str_split($palabra, max(1, $length)) as $pedazo){
			$palabras[] = $pedazo;
		}
	}
	$texto = "";
	$lineas = array();
	foreach($palabras as $palabra){
		if((strlen($texto) + strlen($palabra)) <= $length){
			$texto .= $palabra." ";
		}else{
			if(strlen($texto) > 0){
				$texto = substr($texto, 0, -1);
			}
			$lineas[] = $texto;
			$texto = $palabra." ";
		}
	}
	if(strlen($texto) > 0){
		$texto = substr($texto, 0, -1);
		$lineas[] = $texto;
	}
	return $lineas;
}
?>
