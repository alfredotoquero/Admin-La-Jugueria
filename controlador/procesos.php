<?php
/**
 * Dispatcher central de peticiones AJAX.
 *
 * NOTA DE MIGRACIÓN: en el proyecto original este archivo definía la clase
 * Procesos pero nunca se instanciaba en ningún lado (código muerto: solo
 * tenía un puñado de "case" rotos apuntando a variables $c/$u que no
 * existían, sobras de un patrón de arquitectura "un archivo procesos_x.php
 * por módulo" que reemplazó a este dispatcher). Aquí se recuperó la idea
 * (un único punto de entrada AJAX que enruta por "modulo1") y se dejó
 * realmente funcional, pero sin ningún caso de negocio de Xensei.
 *
 * El front-end (tu propio funciones.js, o cualquier script tuyo) hace un
 * POST a /controlador/procesos.php con al menos:
 *   - modulo1: identifica qué "módulo" de negocio atiende la petición.
 *   - Cualquier otro dato que ese módulo necesite.
 *
 * Este archivo NO debe contener lógica de negocio: solo valida la sesión,
 * arma el envelope JSON y enruta hacia la clase/controlador adecuado.
 *
 * Para agregar un módulo nuevo:
 *   1. Crea tu propia clase (idealmente extendiendo BaseClass) en, por
 *      ejemplo, controlador/clases/tuModulo.php.
 *   2. Agrega un "case" en el switch de abajo con el valor de "modulo1"
 *      que usarás desde el front-end.
 */

include($_SERVER["DOCUMENT_ROOT"] . "/includes/session.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/seguridad2.php");
include($_SERVER["DOCUMENT_ROOT"] . "/includes/conn.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/controlador/clases/permisos.php");

header("Content-Type: application/json");

class Procesos {

	private $con;

	public function __construct($con) {
		$this->con = $con;
	}

	public function ejecuta_procesos($parametros) {
		$modulo1 = $parametros['modulo1'] ?? '';

		switch ($modulo1) {

			// Caso real de arquitectura genérica (no es lógica de negocio):
			// alimenta el selector "Cambiar Empresa" del navbar en home.php.
			// Devuelve las demás empresas (tenants) a las que el usuario
			// autenticado también tiene acceso con el mismo correo.
			case 'cargarEmpresasAsignadas':
				$permisos = new Permisos($this->con);
				$respuesta = $permisos->getEmpresasPorUsuario($parametros['idempresa'] ?? 0, $parametros['correo'] ?? '');
				break;

			// EJEMPLO: agrega aquí tus módulos de negocio. El patrón usado en
			// este proyecto es un archivo controlador/procesos_[modulo].php
			// por módulo, que a su vez hace su propio switch (normalmente
			// por $_POST['proceso'] o $_POST['accion']) y llena $respuesta.
			// case 'miModulo':
			//     include($_SERVER["DOCUMENT_ROOT"] . "/controlador/procesos_miModulo.php");
			//     break;

			// EJEMPLO: agrega más módulos siguiendo el mismo patrón.
			// case 'otroModulo':
			//     include($_SERVER["DOCUMENT_ROOT"] . "/controlador/procesos_otroModulo.php");
			//     break;

			default:
				$respuesta = array(
					"result" => "error",
					"titulo" => "Error",
					"mensaje" => "No se encontró el proceso solicitado.",
					"icono" => "error",
				);
				break;
		}

		return $respuesta;
	}
}

$p = new Procesos($con);
$respuesta = $p->ejecuta_procesos($_POST);

echo json_encode($respuesta, 128);
