// Subconjunto genérico y podado de funciones.js (intranet-xensei): conserva solo utilidades
// reutilizables de AJAX/formularios/tablas/FancyBox/SweetAlert2, sin lógica de negocio de Xensei.
var script_tag = document.getElementById("funciones");
if (script_tag != null) {
	var idusuario = script_tag.getAttribute("data-idusuario");
	var idempresa = script_tag.getAttribute("data-idempresa");
	var codigo_empresa = script_tag.getAttribute("data-codigo_empresa");
	var ultimo_acceso = script_tag.getAttribute("data-ultimo_acceso");
	var isDebugger = script_tag.getAttribute("data-debugger");
	isDebugger = isDebugger == 1;
	var modulo1 = script_tag.getAttribute("data-modulo1");
	var modulo2 = script_tag.getAttribute("data-modulo2");
	var idsubmodulo = script_tag.getAttribute("data-idsubmodulo");
	var idmodulo = script_tag.getAttribute("data-idmodulo");
	if (isDebugger) {
		console.log("idsubmodulo:", idsubmodulo);
		console.log("idmodulo:", idmodulo);
	}
}
var enviandoformulario = false;
var handle;
var ruta_base = "";

async function handleConfirmation(tipo_confirmacion, tipo_modal) {
	if (tipo_confirmacion === 2) {
		return await solicitarContrasena(tipo_modal);
	}
	return true;
}

var lista_mensajes = {
	actionNotAllowed: "Acción no permitida",
	cancelButtonText: "Cancelar",
	title: "¡Atención!",
	formMessage: "Un formulario se está enviando actualmente.",
	enterPasswordTitle: "Introduce tu contraseña",
	enterPasswordMessage: "Por favor, inserta la contraseña para continuar.",
	validating: "Validando...",
	loading: "Cargando...",
	functionFound: "función encontrada",
	functionNotFound: "función no encontrada",
	actionNotFound: "Acción no encontrada",
	unexpectedResponse: "Respuesta inesperada",
	forbidden: "Prohibido",
	errorFetching: "Error al recuperar los datos:",
	errorLoading: "Error al cargar los datos",
	enterCredentials: "Por favor ingrese sus datos de acceso",
	accept: "Aceptar",
	exit: "Salir",
	incorrectPassword: "Correo o contraseña son incorrectos.",
	sessionRestored: "Sesión restaurada.",
	didntGetResponse: "No se pudo obtener respuesta del servidor.",
	noResponse: "No hay respuesta del servidor.",
	noActions: "Sin acciones para procesar",
	fileNotFound: "Archivo no encontrado",
	errorProcessingRequest: "Error al procesar la petición.",
	networkError: "Error de red. Revisa tu conexión de internet.",
	errorProcessingResponse: "Error al procesar la respuesta.",
	noContainerFound: "No hay un contenedor llamado",
};

$(document).ready(function (e) {
	$(document).on("input change", ".is-invalid", function () {
		if (!$(this).hasClass("select2")) {
			$(this).removeClass("is-invalid");
		}
	});

	$(document).on("change", ".select2", function (e) {
		if ($(this).hasClass("is-invalid")) {
			$(this).removeClass("is-invalid");
			$(this).next(".select2").find(".select2-selection").removeClass("is-invalid");
		}
	});

	$(document).on("beforeLoad.fb", function (_e, _api, current) {
		var esUrl = current.src && typeof current.src === "string" && /^(https?:\/\/|\/)/.test(current.src);
		if (idmodulo && esUrl) {
			var sep = current.src.includes("?") ? "&" : "?";
			current.src += sep + "idmodulo=" + encodeURIComponent(idmodulo);
		}
		if (idsubmodulo && esUrl) {
			var sep = current.src.includes("?") ? "&" : "?";
			current.src += sep + "idsubmodulo=" + encodeURIComponent(idsubmodulo);
		}
	});

	$(document).on("beforeClose.fb", function (e, instance, slide) {
		instance.$refs.container.find('[data-toggle="tooltip"]').tooltip("dispose");
	});

	$(document).on("afterLoad.fb", function (e, instance, slide) {
		if (slide.type === "ajax") {
			const cleanUrl = slide.src.split("?")[0];
			instance.$refs.container.attr("data-caller-file", cleanUrl);

			if (slide.$content) {
				slide.$content.attr("data-caller-file", cleanUrl);
			}
		}
	});

	$(document).on("input", ".mayusculas", function () {
		this.value = this.value.replace(/^ /, "").replace(/ {2,}/g, " ");
		this.value = this.value.toUpperCase();
	});

	$(document).on("paste", ".mayusculas", function () {
		let el = this;
		setTimeout(function () {
			el.value = el.value.replace(/^ /, "").replace(/ {2,}/g, " ");
			el.value = el.value.toUpperCase();
		}, 10);
	});

	$("body").on("click", "[data-fancybox]", async function (e) {
		e.preventDefault();
		const current = this;
		let url = $(current).data("src") || $(current).attr("href");
		try {
			const opts = $(current).data("options");
			if (opts) url = (typeof opts === "string" ? JSON.parse(opts) : opts).src || url;
		} catch (_) { }
		if (url && !url.startsWith("#") && !url.startsWith("javascript")) {
			try {
				const check = await fetch(url, { method: "HEAD" });
				if (check.status === 404) {
					Swal.fire("Error", lista_mensajes.fileNotFound, "error");
					return;
				}
			} catch (_) { }
		}
		$.fancybox.open(current);
	});

	if ($(".buscadorMenu").length > 0) {
		const contenedores = $(".buscadorMenu").data("contenedores");
		const arrayContenedores = contenedores.split(",");

		$.each(arrayContenedores, function (i, contenedor) {
			$("#" + contenedor.trim()).children("div").addClass("buscadorMenu-item");
		});

		$(".buscadorMenu").on("keyup", function () {
			var value = $(this).val().toLowerCase();

			$.each(arrayContenedores, function (i, contenedor) {
				var sinResultados = true;

				$(".buscadorMenu-item", "#" + contenedor.trim()).each(function () {
					var resultados = $(this).text().toLowerCase().includes(value);
					$(this).toggle(resultados);
					if (resultados) {
						sinResultados = false;
					}
				});

				if (sinResultados) {
					if ($(".alertaBuscador", "#" + contenedor.trim()).length === 0) {
						$("#" + contenedor.trim()).append('<div class="col-12 mt-3 text-center"><div class="alert alert-warning alertaBuscador" role="alert">No se encontraron resultados</div></div>');
					}
				} else {
					$(".alertaBuscador", "#" + contenedor.trim()).remove();
				}
			});
		});
	}
});

function debug(response) {
	var interruptor = 1;
	if (interruptor == 1) {
		console.log(response);
	} else {
		console.log("debug OFF");
	}
}

function fancyAjax(source) {
	$.fancybox.open({
		src: source,
		type: "ajax",
		opts: {
			closeExisting: true,
		},
	});
}

function fancyAjax2(source) {
	$.fancybox.open({
		src: source,
		type: "ajax",
		opts: {
			afterClose: function () {
				recargarLista();
			},
			closeExisting: true,
		},
	});
}

function login() {
	var usuario = $("#txtUsuario").val();
	var password = $("#txtPassword").val();
	var authToken = $("#authToken").val();
	return new Promise((resolve, reject) => {
		$.ajax({
			data: {
				usuario: usuario,
				password: password,
				authToken: authToken,
			},
			url: "/controlador/login.php",
			type: "POST",
			dataType: "json",
			error: function (jqXHR, textStatus, errorThrown) {
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (data) {
				if (data.result === "success") {
					if (data.url) {
						location.href = data.url;
					} else {
						location.href = "/home.php";
					}
				} else if (data.result === "error") {
					$("#txtTituloModal").html(data.tit);
					$("#txtMensajeModal").html(data.msg);
					$("#modalLogin").modal("show");
					$("#txtLogin").val("");
					$("#txtPassword").val("");
				}
			},
		});
	});
}

function validateForm(form) {
	var valido = true;
	var id;
	var tipo;
	$(".requerido", "#" + form).each(function () {
		var elemento = $(this).prop("tagName").toLowerCase();
		if (elemento == "input") {
			tipo = $(this).attr("type");
			id = $(this).attr("id");
			valor = parseInt($(this).val());
			if (
				tipo == "text" &&
				($(this).val() == "" || $(this).val().trim().length == 0)
			) {
				$(this).focus();
				valido = false;
				return false;
			} else if (
				tipo == "password" &&
				($(this).val() == "" || $(this).val().trim().length == 0)
			) {
				$(this).focus();
				valido = false;
				return false;
			} else if (tipo == "number" && jQuery.type(valor) != "number") {
				$(this).focus();
				valido = false;
				return false;
			} else if (tipo == "email" && !validarCorreo($(this).val())) {
				$(this).focus();
				valido = false;
				return false;
			} else if (tipo == "file" && $(this).val() == "") {
				$(this).focus();
				valido = false;
				return false;
			} else if (id == "txtRfc" && !validarRFC($(this).val())) {
				$(this).focus();
				valido = false;
				return false;
			}
		} else if (elemento == "select" && $(this).val() == 0) {
			$(this).focus();
			valido = false;
			return false;
		} else if (elemento == "textarea" && $(this).val() == "") {
			$(this).focus();
			valido = false;
			return false;
		}
	});

	// Esta validación está mal ya que colorea a todos los campos requeridos aunque sólo uno sea inválido
	if (!valido && id == "txtRfc") {
		Swal.fire("Atención", "El RFC no es correcto.", "warning");
		$(".requerido").css("border", "1px solid red");
	} else if (!valido && tipo == "email") {
		Swal.fire("Atención", "El correo no es correcto.", "warning");
		$(".requerido").css("border", "1px solid red");
	} else if (!valido) {
		Swal.fire("Atención", "Debes ingresar los campos requeridos.", "warning");
		$(".requerido").css("border", "1px solid red");
	}

	return valido;
}

function eliminarAlt(proceso, modulo, id) {
	Swal.fire({
		title: "Estás seguro?",
		text: "Confirma la eliminación...",
		icon: "warning",
		buttons: true,
		dangerMode: true,
	}).then((willDelete) => {
		if (willDelete) {
			$.ajax({
				type: "POST",
				url: "/modulos/" + modulo + "/procesos.php",
				data: {
					proceso: proceso,
					id: id,
				},
				dataType: "json",
				beforeSend: function () { },
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("jqXHR: " + jqXHR);
					console.log("textStatus: " + textStatus);
					console.log("errorThrown: " + errorThrown);
				},
				success: function (response) {
					if (response.result == "success") {
						parent.recargarListaAlt();
						parent.Swal.fire({
							title: response.titulo,
							text: response.texto,
							icon: response.result,
						});
					} else {
						Swal.fire({
							title: response.titulo,
							text: response.texto,
							icon: response.result,
						});
					}
				},
			});
		}
	});
}

function eliminar(proceso, modulo, id, callback = null, anuncio = false) {
	Swal.fire({
		title: "¿Estás seguro?",
		text: "Esta accion no se puede deshacer.",
		icon: "warning",
		showCancelButton: true,
		confirmButtonText: "Sí, eliminar",
		cancelButtonText: "Cancelar",
		reverseButtons: true,
	}).then((result) => {
		if (result.isConfirmed) {
			$.ajax({
				type: "POST",
				url: "/modulos/" + modulo + "/procesos.php",
				data: {
					proceso: proceso,
					id: id,
				},
				dataType: "json",
				beforeSend: function () { },
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("jqXHR: " + jqXHR);
					console.log("textStatus: " + textStatus);
					console.log("errorThrown: " + errorThrown);
				},
				success: function (response) {
					if (response.result == "success") {
						if (typeof callback == "function") {
							callback();
						} else {
							parent.recargarLista();
						}
						if (!anuncio) {
							parent.Swal.fire({
								title: response.titulo,
								text: response.texto,
								icon: response.result,
							});

						}
					} else {
						Swal.fire({
							title: response.titulo,
							text: response.texto,
							icon: response.result,
						});
					}
				},
			});
		}
	});
}

function guardar(form, modulo, closefancybox = 0, callback = null) {
	if (validateForm(form)) {
		$.ajax({
			type: "POST",
			url: "/modulos/" + modulo + "/procesos.php",
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				showModal("Procesando datos...");
				if ($("#btnAccion").length && !$("#btnAccion").data("html")) {
					$("#btnAccion").data("html", $("#btnAccion").html());
				}
				$("#btnAccion").prop("disabled", true);
				$("#btnAccion").html("Cargando...");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				hideModal();
				if ($("#btnAccion").length) {
					$("#btnAccion").prop("disabled", false);
					$("#btnAccion").html(
						$("#btnAccion").data("html") || "Guardar"
					);
				}
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				hideModal();
				if (typeof response !== undefined && response !== null) {
					if (response.result == "success") {
						if (typeof callback == "function") {
							callback();
						} else {
							if (closefancybox == 1)
								parent.jQuery.fancybox.getInstance().close();
							parent.recargarLista();
							parent.jQuery.fancybox.getInstance().close();
						}
						parent.Swal.fire({
							title: response.titulo,
							html: response.texto || response.mensaje,
							icon: response.result,
						});
						return true;
					} else {
						if ($("#btnAccion").length) {
							$("#btnAccion").prop("disabled", false);
							$("#btnAccion").html(
								$("#btnAccion").data("html") || "Guardar"
							);
						}
						parent.Swal.fire({
							title: response.titulo,
							html: response.texto || response.mensaje,
							icon: response.result,
						});
					}
				} else {
					if ($("#btnAccion").length) {
						$("#btnAccion").prop("disabled", false);
						$("#btnAccion").html(
							$("#btnAccion").data("html") || "Guardar"
						);
					}
					parent.Swal.fire(
						"Error",
						"No se obtuvo respuesta del servidor",
						"error"
					);
				}
			},
		});
	}
}

function guardarAlt(form, modulo) {
	if (validateForm(form)) {
		$.ajax({
			type: "POST",
			url: "/modulos/" + modulo + "/procesos.php",
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				showModal("procesando datos...");
				$("#btnAccion").prop("disabled", true);
				$("#btnAccion").html("UPLOADING...");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				hideModal();
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				hideModal();
				if (typeof response !== undefined && response !== null) {
					if (response.result == "success") {
						parent.jQuery.fancybox.getInstance().close();
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
						parent.recargarListaAlt();
						return true;
					} else {
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
						return false;
					}
				} else {
					parent.Swal.fire(
						"Error",
						"No se obtuvo respuesta del servidor",
						"error"
					);
				}
			},
		});
	}
}

function guardarSinClose(form, modulo) {
	if (validateForm(form)) {
		$.ajax({
			type: "POST",
			url: "/modulos/" + modulo + "/procesos.php",
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				showModal("procesando datos...");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				hideModal();
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				hideModal();
				if (typeof response !== undefined && response !== null) {
					if (response.result == "success") {
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
						parent.recargarLista();
					} else {
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
					}
				} else {
					parent.Swal.fire(
						"Error",
						"No se obtuvo respuesta del servidor",
						"error"
					);
				}
			},
		});
	}
}

function guardarSinCloseAlt(form, modulo) {
	if (validateForm(form)) {
		$.ajax({
			type: "POST",
			url: "/modulos/" + modulo + "/procesos.php",
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				showModal("procesando datos...");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				hideModal();
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				hideModal();
				if (typeof response !== undefined && response !== null) {
					if (response.result == "success") {
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
						parent.recargarListaAlt();
					} else {
						parent.Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.result,
						});
					}
				} else {
					parent.Swal.fire(
						"Error",
						"No se obtuvo respuesta del servidor",
						"error"
					);
				}
			},
		});
	}
}

function validarCorreo(correo) {
	return /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/.test(correo);
}

function validarRFC(valor) {
	return /^([A-Z,Ñ,&]{3,4}([0-9]{2})(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[A-Z|\d]{3})$/i.test(
		valor
	);
}

function validarCURP(valor) {
	return /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/i.test(
		valor
	);
}

function validarNSS(valor) {
	return /^(\d{11})$/i.test(valor);
}

function validarRegistroPatronal(valor) {
	return /^(\d{11})$/i.test(valor);
}

// Nota: esta función era referenciada por validarFormulario2() pero no existía en el
// funciones.js original (dependía de otro script de módulo no incluido en este subconjunto).
// Se agrega una implementación genérica mínima para no dejar la referencia rota.
function validarNumeroTelefono(valor) {
	return /^[\d\s()+-]{7,20}$/.test((valor || "").trim());
}

function soloNumeros(e) {
	var key = window.event ? e.which : e.keyCode;
	if (key < 46 || key > 57 || (key == 173 && admiteNegativos)) {
		e.preventDefault();
	}
}

function soloNumeros2(inputElement, allowNegative = false) {
	let inputValue = inputElement.value;

	// Remove non-numeric and non-negative characters
	if (allowNegative) {
		inputValue = inputValue.replace(/[^-0-9.]/g, "");
	} else {
		inputValue = inputValue.replace(/[^0-9.]/g, "");
	}

	// Update the input value
	inputElement.value = inputValue;
}
function soloNumeros3(e) {
	var key = window.event ? e.which : e.keyCode;
	if (key < 46 || key > 57 || (key == 173 && admiteNegativos)) {
		e.preventDefault();
	}
}

function mayus(e) {
	e.value = e.value.toUpperCase();
}

function cargarLista(url, variable = null, div) {
	$.ajax({
		type: "POST",
		data: variable,
		url: url,
		beforeSend: function () {
			$(".se-pre-con").css("display", "inline");
		},
		success: function (data) {
			$(".se-pre-con").css("display", "none");
			$("#" + div).html(data);
		},
	});
}

function cargarTabla2(tableName = "myTable") {
	$("#" + tableName).DataTable({
		paging: false,
		order: [[0, "desc"]],
		language: {
			decimal: "",
			emptyTable: "No hay datos disponibles",
			info: "Mostrando _START_ de _END_ de _TOTAL_ resultados",
			infoEmpty: "No hay resultados",
			infoFiltered: "(filtrado de _MAX_ resultados)",
			infoPostFix: "",
			thousands: ",",
			lengthMenu: "Mostrar _MENU_ resultados",
			loadingRecords: "Cargando...",
			processing: "Procesando...",
			search: "Buscar:",
			zeroRecords: "No se encotraron resultados",
			paginate: {
				first: "Primero",
				last: "&Uacute;ltimo",
				next: "Siguiente",
				previous: "Anterior",
			},
			aria: {
				sortAscending: ": activar de forma acendente",
				sortDescending: ": activar de forma decendente",
			},
		},
	});
}

function abrirFancy(source) {
	if (idmodulo && source && typeof source === "string") {
		var sep = source.includes("?") ? "&" : "?";
		source += sep + "idmodulo=" + encodeURIComponent(idmodulo);
	}
	if (idsubmodulo && source && typeof source === "string") {
		var sep = source.includes("?") ? "&" : "?";
		source += sep + "idsubmodulo=" + encodeURIComponent(idsubmodulo);
	}
	$.fancybox.open({
		src: source,
		type: "ajax",
		opts: {
			closeExisting: true,
		},
	});
}

function editar(form, modulo, fancy = 0, recargarlista = 0) {
	if (validateForm(form)) {
		$.ajax({
			type: "POST",
			url: "/modulos/" + modulo + "/procesos.php",
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				$("#btnAccion").prop("disabled", true);
				$("#btnAccion").html("GUARDANDO...");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				if (response.result == "success") {
					if (parseInt(fancy) == 1) {
						parent.jQuery.fancybox.getInstance().close();
						$.fn.fancybox.close();
					}

					parent.Swal.fire({
						title: response.titulo,
						text: response.mensaje,
						icon: response.result,
					});

					if (parseInt(recargarlista) == 1) parent.recargarLista();
				} else {
					$("#btnAccion").prop("disabled", false);
					$("#btnAccion").html($("#btnAccion").data("html"));
					parent.Swal.fire({
						title: response.titulo,
						text: response.mensaje,
						icon: response.result,
					});
				}
			},
		});
	}
}

function showModal(texto = "") {
	$("body").loadingModal({ text: texto });
	$("body").loadingModal("animation", "cubeGrid");
}

function hideModal() {
	$("body").loadingModal("hide");
	$("body").loadingModal("destroy");
}

function validarFormulario2(formulario) {
	var error = false;
	$(".req", "#" + formulario).each(function () {
		var elemento = $(this).prop("tagName").toLowerCase();
		if (elemento == "input") {
			var tipo = $(this).attr("type");
			if (tipo == "hidden" && $(this).val() == "") {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (
				tipo == "text" &&
				($(this).val() == "" || $(this).val().trim().length == 0)
			) {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (
				tipo == "password" &&
				($(this).val() == "" || $(this).val().trim().length == 0)
			) {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (
				tipo == "number" &&
				($(this).val().trim().length == 0 || $(this).val().trim() == 0)
			) {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (tipo == "email" && !validarCorreo($(this).val())) {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (tipo == "tel" && !validarNumeroTelefono($(this).val())) {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (tipo == "file" && $(this).val() == "") {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			} else if (tipo == "radio" && $(this).val() == "") {
				Swal.fire(
					"Error",
					$(this).prop("dataset")["mensajeerror"],
					"error"
				).then(() => {
					$(this).focus();
				});
				error = true;
				return false;
			}
		} else if (elemento == "select" && $(this).val() == "") {
			Swal.fire("Error", $(this).prop("dataset")["mensajeerror"], "error").then(
				() => {
					$(this).focus();
				}
			);
			error = true;
			return false;
		} else if (elemento == "textarea" && $(this).val() == "") {
			Swal.fire("Error", $(this).prop("dataset")["mensajeerror"], "error").then(
				() => {
					$(this).focus();
				}
			);
			error = true;
			return false;
		}
	});
	return !error;
}

function cargarDatosContenedor(formulario) {
	if ($("#" + formulario).length > 0) {
		$.ajax({
			type: "POST",
			url: $("#archivo", "#" + formulario).val(),
			data: new FormData($("#" + formulario)[0]),
			processData: false,
			contentType: false,
			beforeSend: function () {
				showModal("Cargando...");
				enviandoformulario = true;
			},
			success: function (data) {
				hideModal();
				enviandoformulario = false;
				$("#" + $("#contenedor", "#" + formulario).val()).html(data);
			},
		});
		console.log("cargar datoss");
	}
}

function procesoConfirmacion(proceso, data, titulo, reload = true) {
	Swal.fire({
		title: titulo,
		icon: "warning",
		buttons: ["Cancelar", "Aceptar"],
	}).then((value) => {
		if (value) {
			var controlador = $("#controlador").val();
			$.ajax({
				type: "POST",
				url: controlador,
				data: "proceso=" + proceso + "&" + data,
				dataType: "json",
				beforeSend: function () {
					showModal("Cargando");
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("jqXHR: " + jqXHR);
					console.log("textStatus: " + textStatus);
					console.log("errorThrown: " + errorThrown);
				},
				success: function (response) {
					console.log(response);
					hideModal();
					if (response.respuesta == "success") {
						if (reload) {
							cargarDatosContenedor(response.form);
						}
						const template = `${response.mensaje} ${response.complemento != "" && response.complemento != undefined
							? "<br>" + response.complemento
							: ""
							}`;
						parent.Swal.fire({
							title: response.titulo,
							icon: response.respuesta,
							content: {
								element: "p",
								attributes: {
									innerHTML: `${template}`,
								},
							},
						});
					} else {
						Swal.fire({
							title: response.titulo,
							text: response.mensaje,
							icon: response.respuesta,
						});
					}
				},
			});
		}
	});
}

function procesoGuardar(form) {
	if (validarFormulario2(form)) {
		var controlador = $("#controlador").val();
		$.ajax({
			type: "POST",
			url: controlador,
			data: new FormData($("#" + form)[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			beforeSend: function () {
				showModal("Guardando");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				hideModal();
				console.log("jqXHR: " + jqXHR);
				console.log("textStatus: " + textStatus);
				console.log("errorThrown: " + errorThrown);
			},
			success: function (response) {
				hideModal();
				if (response.respuesta == "success") {
					$.fancybox.getInstance().close();
					const template = `${response.mensaje} ${response.complemento ? "<br>" + response.complemento : ""
						}`;
					parent.Swal.fire({
						title: response.titulo,
						icon: response.respuesta,
						content: {
							element: "p",
							attributes: {
								innerHTML: `${template}`,
							},
						},
					});
					cargarDatosContenedor(response.form);
				} else {
					parent.Swal.fire({
						title: "Error",
						text: response.mensaje,
						icon: "error",
					});
				}
			},
		});
	}
}

const defineFunction = (paramConfig, fn) => withFlexibleParams(fn, paramConfig);

window.solicitudServidor = defineFunction({
	controlador: { required: true },
	accion: { required: true },
	boton: { default: null },
	datos: { default: "" },
	datosFormData: { default: null },
	tipo_confirmacion: { default: 0 },
	titulo: { default: "" },
	mensaje: { default: "" },
	tipo_modal: { default: 1 },
	incluye_carga_imagenes: { default: false },
	desaparece_modal: { default: true },
	aplica_loading_text: { default: false },
	desenfoca_boton: { default: false },
	mensaje_modal_carga: { default: undefined },
	icono: { default: "warning" },
}, (
	controlador, accion, boton, datos,
	datosFormData, tipo_confirmacion,
	titulo, mensaje, tipo_modal,
	incluye_carga_imagenes, desaparece_modal,
	aplica_loading_text, desenfoca_boton,
	mensaje_modal_carga, icono
) => {
	if (tipo_confirmacion > 0) {
		return Swal.fire({
			title: titulo,
			html: mensaje,
			icon: icono,
			showCancelButton: true,
			confirmButtonText: "Ok",
			cancelButtonText: lista_mensajes.cancelButtonText,
			customClass: {
				actions: "my-actions",
				cancelButton: "left-gap order-1",
				confirmButton: "order-2",
			},
		}).then(async (result) => {
			if (
				result.isConfirmed &&
				(await handleConfirmation(tipo_confirmacion, tipo_modal))
			)
				return conexionServidor(controlador, accion, boton, datos, datosFormData, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton, mensaje_modal_carga);
		});
	} else {
		return conexionServidor(controlador, accion, boton, datos, datosFormData, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton, mensaje_modal_carga);
	}
});

function conexionServidor(controlador, accion, boton, datos, datosFormData, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton, mensaje_modal_carga) {
	const caller = getCallerForButton(resolveButtonElement(boton));
	const isFormData = datos == "" && datosFormData !== null;
	if (isFormData) {
		datosFormData.append("proceso", accion);
		datosFormData.append("ultimo_acceso", ultimo_acceso);
		datosFormData.append("idusuario_sesion", idusuario);
		datosFormData.append("idmodulo", idmodulo);
		datosFormData.append("idsubmodulo", idsubmodulo);
		datos = datosFormData;
	} else {
		datos = `proceso=${accion}&${datos}&ultimo_acceso=${ultimo_acceso}&idusuario_sesion=${idusuario}&idmodulo=${idmodulo}&idsubmodulo=${idsubmodulo}`;
	}
	const requestOptions = {
		method: "POST",
		body: datos,
	};
	if (!isFormData) {
		requestOptions.headers = { "Content-Type": "application/x-www-form-urlencoded" };
	} else {
		requestOptions.headers = requestOptions.headers || {};
	}
	requestOptions.headers = Object.assign({}, requestOptions.headers);
	if (!requestOptions.headers["X-Caller"] && !requestOptions.headers["x-caller"]) {
		requestOptions.headers["X-Caller"] = caller;
	}
	const ajaxOptions = isFormData ? { processData: false, contentType: false } : {};
	showModal2(tipo_modal, boton, aplica_loading_text, mensaje_modal_carga);
	if (!window.fetch) {
		return new Promise((resolve, reject) => {
			$.ajax({
				type: "POST",
				url: ruta_base + controlador + ".php",
				data: datos,
				dataType: "json",
				...ajaxOptions,
				success: function (data) {
					try {
						resolve(handleResponse(data, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton));
					} catch (error) {
						reject(handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton));
					}
				},
				error: function (error) {
					if (typeof error.code == undefined || error.code == null)
						error.code = error.status;
					reject(handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton));
				}
			});
		});
	} else {
		return fetch(ruta_base + controlador + ".php", requestOptions).then(response => {
			if (response.status === 401) {
				return response.json().then(data => {
					throw { name: "SessionExpiredError", message: data.mensaje, code: 401, authToken: data.authToken };
				});
			} else if (response.status === 404) {
				let url = extractPath(response.url);
				throw { name: "FileNotFound", message: response.statusText, code: 404, url: url };
			}
			const contentType = response.headers.get("Content-Type");
			const contentDisposition = response.headers.get("Content-Disposition");
			if (contentType && contentType.includes("application/json")) {
				return response.json();
			} else if (contentType && contentType.includes("text/") && !(contentDisposition && contentDisposition.includes("attachment"))) {
				return response.text();
			} else {
				return response.blob().then(blob => {
					const filename = getFileNameFromResponse(response) || "downloaded_file";
					downloadBlob(blob, filename);
					hideModal2(tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
					return { success: true, downloaded: true };
				});
			}
		}).then(data => {
			if (!data.downloaded) {
				return handleResponse(data, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton);
			}
		}).catch(error => {
			return handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
		});
	}
}

function resolveButtonElement(button) {
	try {
		if (!button) return null;

		if (window.jQuery && button instanceof jQuery) {
			return button[0] || null;
		}

		if (button instanceof Element) return button;

		if (typeof button === "string") {
			return document.querySelector(button);
		}

		if (button && button.target && button.target instanceof Element) return button.target;
	} catch (e) { }
	return null;
}

function getCallerForButton(buttonElement) {
	if (!buttonElement) return null;

	const container = buttonElement.closest("[data-caller-file]");
	if (container && container.dataset && container.dataset.callerFile) {
		return container.dataset.callerFile;
	}

	if (container && container.getAttribute) {
		const attr = container.getAttribute("data-caller-file");
		if (attr) return attr;
	}
	return "unknown";
}

function getFileNameFromResponse(response) {
	const contentDisposition = response.headers.get("Content-Disposition");
	if (contentDisposition && contentDisposition.includes("attachment")) {
		const match = contentDisposition.match(/filename="?([^"]+)"?/);
		return match ? match[1] : null;
	}
	return null;
}

function downloadBlob(blob, filename) {
	const url = window.URL.createObjectURL(blob);
	const a = document.createElement("a");
	a.href = url;
	a.download = filename;
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	window.URL.revokeObjectURL(url);
}

function destroyTooltip() {
	$('[data-toggle="tooltip"]').tooltip("dispose");
}

window.validarFormulario = defineFunction({
	formulario: { required: true },
	boton: { default: null },
	tipo_modal: { default: 1 },
	enviar: { default: true },
	incluye_carga_imagenes: { default: false },
	aplica_loading_text: { default: false },
	tipo_confirmacion: { default: 0 },
	titulo: { default: "" },
	mensaje: { default: "" },
	desaparece_modal: { default: true },
	desenfoca_boton: { default: false },
	mensaje_modal_carga: { default: undefined },
	deshabilita_campos_invisibles: { default: true },
	valida_todos: { default: false },
}, (
	formulario, boton, tipo_modal, enviar,
	incluye_carga_imagenes, aplica_loading_text,
	tipo_confirmacion, titulo, mensaje, desaparece_modal,
	desenfoca_boton, mensaje_modal_carga,
	deshabilita_campos_invisibles, valida_todos
) => {
	var error = false;
	if (!enviar && tipo_modal)
		showModal2(tipo_modal, boton, aplica_loading_text, mensaje_modal_carga);
	var title = lista_mensajes.title;
	var icon = "warning";
	var to_focus;
	var select2;
	$(".requerido:visible:not(:disabled)", "#" + formulario).each(function () {
		var elemento = $(this).prop("tagName").toLowerCase();
		select2 = false;
		if (elemento === "input") {
			var tipo = $(this).attr("type");
			if (tipo === "text" && $(this).val().trim().length === 0)
				error = true;
			else if (tipo === "password" && $(this).val().trim().length < 8 && $(this).val().trim().length >= 0)
				error = true;
			else if (tipo === "email" && !validarCorreo($(this).val().trim()))
				error = true;
			else if (["radio", "checkbox"].includes(tipo)) {
				var groupName = $(this).attr("name");
				if ($("input[name=" + groupName + "]:checked").length === 0)
					error = true;
			} else if (tipo === "file" && $(this).val().trim().length === 0)
				error = true;
		} else if (elemento === "select" && ($(this).val() === "" || $(this).val() === null)) {
			if ($(this).hasClass("select2")) select2 = true;
			error = true;
		} else if (elemento === "textarea" && $(this).val().trim().length === 0)
			error = true;
		if (error) {
			if (valida_todos) {
				to_focus = ((to_focus == undefined) ? $(this) : to_focus);
				if (select2) {
					$(this).addClass("is-invalid");
					$(this).next(".select2").find(".select2-selection").addClass("is-invalid");
				} else
					$(this).addClass("is-invalid");
			} else {
				swalFocus(title, $(this).data("mensajeerror").replace(/\.+$/, "") + ".", icon, $(this).attr("id"), formulario, select2);
				return false;
			}
		}
	});
	if (!enviar || error)
		hideModal2(tipo_modal, boton, desaparece_modal, aplica_loading_text);
	// if (to_focus)
	// 	swalFocus(title, $(to_focus).data("mensajeerror").replace(/\.+$/, "") + ".", icon, $(to_focus).attr("id"), formulario, select2);
	if (!error && enviar) {
		if (tipo_confirmacion > 0) {
			Swal.fire({
				title: titulo,
				text: mensaje,
				icon: "warning",
				showCancelButton: true,
				confirmButtonText: "Ok",
				cancelButtonText: lista_mensajes.cancelButtonText,
				customClass: {
					actions: "my-actions",
					cancelButton: "left-gap order-1",
					confirmButton: "order-2",
				},
			}).then(async (result) => {
				if (result.isConfirmed && (await handleConfirmation(tipo_confirmacion, tipo_modal))) {
					enviarFormulario(formulario, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton, mensaje_modal_carga, deshabilita_campos_invisibles);
				} else {
					if (boton !== null) {
						$(boton).prop("disabled", false);
						if (aplica_loading_text) {
							$(boton).html($(boton).data("original_text") || $(boton).html());
						}
						$(boton).focus();
					}
				}
			});
		} else
			enviarFormulario(formulario, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton, mensaje_modal_carga, deshabilita_campos_invisibles);
	}
	if (!error)
		return true;
	else
		return false;
});

function enviarFormulario(formulario, boton, tipo_modal = 1, incluye_carga_imagenes = false, desaparece_modal = true, aplica_loading_text = false, desenfoca_boton = false, mensaje_modal_carga = undefined, deshabilita_campos_invisibles = true) {
	try {
		if (enviandoformulario)
			throw Object.assign(new Error(lista_mensajes.formMessage), { code: 1 });
		const caller = getCallerForButton(resolveButtonElement(boton));
		if ($("#" + formulario + " #ultimo_acceso").length == 0)
			$("#" + formulario).append("<input type='hidden' name='ultimo_acceso' id='ultimo_acceso' value='" + ultimo_acceso + "'>").append("<input type='hidden' name='idusuario_sesion' value='" + idusuario + "'>").append("<input type='hidden' name='idmodulo' value='" + idmodulo + "'>").append("<input type='hidden' name='idsubmodulo' value='" + idsubmodulo + "'>");
		if (deshabilita_campos_invisibles)
			$("#" + formulario).find("input[type!=hidden]:not(:visible), select:not(:visible), textarea:not(:visible)").prop("disabled", true);
		var formData = new FormData($("#" + formulario)[0]);
		if (deshabilita_campos_invisibles)
			$("#" + formulario).find("input[type!=hidden]:not(:visible), select:not(:visible), textarea:not(:visible)").prop("disabled", false);
		const requestOptions = {
			method: "POST",
			body: formData,
		};
		requestOptions.headers = requestOptions.headers || {};
		requestOptions.headers = Object.assign({}, requestOptions.headers);
		if (!requestOptions.headers["X-Caller"] && !requestOptions.headers["x-caller"]) {
			requestOptions.headers["X-Caller"] = caller;
		}
		enviandoformulario = true;
		showModal2(tipo_modal, boton, aplica_loading_text, mensaje_modal_carga);
		if (!window.fetch) {
			return new Promise((resolve, reject) => {
				$.ajax({
					type: "POST",
					url: ruta_base + $("#controlador", "#" + formulario).val() + ".php",
					data: formData,
					processData: false,
					contentType: false,
					dataType: "json",
					success: function (data) {
						try {
							enviandoformulario = false;
							resolve(handleResponse(data, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton));
						} catch (error) {
							reject(handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton));
						}
					},
					error: function (error) {
						enviandoformulario = false;
						reject(handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton));
					},
				});
			});
		} else {
			return fetch(ruta_base + $("#controlador", "#" + formulario).val() + ".php", requestOptions).then((response) => {
				if (response.status === 401) {
					return response.json().then((data) => {
						throw {
							name: "SessionExpiredError",
							message: data.mensaje,
							code: 401,
							authToken: data.authToken,
						};
					});
				}
				const contentType = response.headers.get("Content-Type");
				if (contentType && contentType.includes("application/json"))
					return response.json();
				else return response.text();
			}).then((data) => {
				enviandoformulario = false;
				return handleResponse(data, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal, aplica_loading_text, desenfoca_boton);
			}).catch((error) => {
				console.log(error);
				enviandoformulario = false;
				return handleError(error, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
			});
		}
	} catch (error) {
		if (error.code === 1) Swal.fire("Error", error.message, "error");
		else console.error("error", error);
	}
}

function solicitarContrasena(tipo_modal) {
	return Swal.fire({
		title: lista_mensajes.enterPasswordTitle,
		text: lista_mensajes.enterPasswordMessage,
		icon: "info",
		input: "password",
		allowOutsideClick: false,
		customClass: {
			actions: "my-actions",
			confirmButton: "left-gap order-1 confirm-button",
		},
		preConfirm: (password) => {
			return validarContrasena(password, tipo_modal);
		},
	}).then((result) => {
		if (result.isConfirmed) return true;
		return false;
	});
}

function validarContrasena(password, tipo_modal) {
	return new Promise((resolve, reject) => {
		showModal2(tipo_modal);
		if (!window.fetch) {
			$.ajax({
				type: "POST",
				url: "/controlador/validar_contrasena.php",
				data: "password=" + password,
				dataType: "json",
				error: function (error) {
					hideModal2(tipo_modal);
					if (error.status == 401) {
						showToastr(error.responseJSON.mensaje, "Error", "error", {
							timeOut: 3000,
							tapToDismiss: false,
							progressBar: false,
							newestOnTop: true,
							preventDuplicates: true,
						});
					} else console.error("error", error);
					reject(false);
				},
				success: function (response) {
					hideModal2(tipo_modal);
					if (response.respuesta == "success") resolve(true);
					else if (response.status == 401) {
						showToastr(response.mensaje, "Error", "error", {
							timeOut: 3000,
							tapToDismiss: false,
							progressBar: false,
							newestOnTop: true,
							preventDuplicates: true,
						});
						resolve(false);
					}
				},
			});
		} else {
			fetch("/controlador/validar_contrasena.php", {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "password=" + password,
			})
				.then((response) => {
					if (!response.ok) {
						if (response.status === 401) {
							return response.json().then((errorData) => {
								throw {
									name: errorData.name,
									message: errorData.mensaje,
									code: 401,
								};
							});
						}
						throw new Error(lista_mensajes.unexpectedResponse);
					}
					return response.json();
				})
				.then((data) => {
					if (data.respuesta === "success") {
						hideModal2(tipo_modal);
						resolve(true);
					} else {
						throw {
							name: data.name,
							message: data.mensaje,
							code: 401,
						};
					}
				})
				.catch((error) => {
					hideModal2(tipo_modal);
					if (error.code === 401) {
						showToastr(error.message, "Error", "error", {
							timeOut: 3000,
							tapToDismiss: false,
							progressBar: false,
							newestOnTop: true,
							preventDuplicates: true,
						});
					}
					resolve(false);
				});
		}
	});
}

function showModal2(tipo_modal = 1, boton = null, aplica_loading_text = false, mensaje = "Cargando...", titulo = "") {
	destroyTooltip();
	if (boton != null) {
		$(boton).prop("disabled", true);
		if (aplica_loading_text) {
			$(boton).attr("data-original_text", $(boton).html());
			$(boton).data("original_text", $(boton).html());
			$(boton).html($(boton).data("loading_text"));
		}
	}
	if (tipo_modal) {
		switch (tipo_modal) {
			case 2:
				$("body").loadingModal({ text: mensaje, animation: "cubeGrid" });
				break;
			case 3:
				$("#faSave").removeClass("d-none");
				$("#divImagen").addClass("loading_overlay");
				break;
			case 4:
				$("#txtTituloModal").html(titulo);
				$("#txtMensajeModal").html(mensaje);
				$("#modalLogin").modal("show");
				break;
			case 1:
			default:
				$(".se-pre-con").css("display", "inline");
				break;
		}
	}
}

function hideModal2(tipo_modal = 1, boton = null, desaparece_modal = true, aplica_loading_text = false, desenfoca_boton = false, disabled = false) {
	if (boton != null) {
		$(boton).prop("disabled", disabled);
		if (aplica_loading_text)
			$(boton).html($(boton).data("original_text") || $(boton).html());
		$(boton).removeAttr("data-original_text");
		$(boton).data("original_text", "");
		if (desenfoca_boton) $(boton).blur();
		else $(boton).focus();
	}
	if (!desaparece_modal) return;
	if (tipo_modal) {
		switch (tipo_modal) {
			case 2:
				$("body").loadingModal("hide");
				$("body").loadingModal("destroy");
				break;
			case 3:
				$("#faSave").addClass("d-none");
				$("#divImagen").removeClass("loading_overlay");
				break;
			case 4:
				$("#txtTituloModal").html("");
				$("#txtMensajeModal").html("");
				$("#modalLogin").modal("hide");
				break;
			case 1:
			default:
				$(".se-pre-con").css("display", "none");
				break;
		}
	}
	$('[data-toggle="tooltip"]').tooltip();
}

function procesarAcciones(data, tipo_modal, desaparece_modal, boton, aplica_loading_text, desenfoca_boton) {
	respuesta_cortar = true;
	$.each(data.acciones, function (index, response) {
		switch (response.tipo) {
			case "reload":
				location.reload();
				break;
			case "redirigir":
				location.href = response.url;
				break;
			case "redirigir_post":
				redirigirPost(response.url, response.datos, response.json, response.parametro);
				break;
			case "mensaje":
				Swal.fire(response.opciones_swal);
				break;
			case "cerrar_fancybox":
				cerrarFancybox();
				break;
			case "abrir_fancybox":
				abrirFancybox(response.configuracion);
				break;
			case "recargar_lista":
				cargarLista2(response.url, response.contenedor, response.tipo_agregar, response.parametros, response.form);
				break;
			case "mensaje_reload":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						location.reload();
				});
				break;
			case "mensaje_redirigir":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						location.href = response.url;
				});
				break;
			case "mensaje_cerrar_fancybox":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						cerrarFancybox();
				});
				break;
			case "mensaje_abrir_fancybox":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						abrirFancybox(response.configuracion);
				});
				break;
			case "mensaje_recargar_lista":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						cargarLista2(response.url, response.contenedor, response.tipo_agregar, response.parametros, response.form);
				});
				break;
			case "mensaje_recargar_lista_cerrar_fancybox":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc)) {
						cargarLista2(response.url, response.contenedor, response.tipo_agregar, response.parametros, response.form);
						cerrarFancybox();
					}
				});
				break;
			case "mensaje_cargar_ventana":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc))
						window.open(response.url, "_blank");
				});
				break;
			case "mensaje_llamar_funcion":
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed || response.opciones_swal.allowOutsideClick || (response.opciones_swal.allowEscapeKey && respuesta.dismiss === Swal.DismissReason.esc)) {
						if (typeof window[response.funcion] === "function") {
							consoleLog(response, lista_mensajes.functionFound);
							if (response.tres_puntos)
								window[response.funcion](...response.parametros);
							else window[response.funcion](response.parametros);
						} else consoleLog(response, lista_mensajes.functionNotFound);
					}
				});
				break;
			case "inputs":
				actualizarInputs(response.inputs, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
				break;
			case "single_toast":
				showToastr(response.mensaje, response.titulo, response.icono, response.opciones, response.quitar_toasts_anteriores);
				break;
			case "modal":
				hideModal2(tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
				break;
			case "llamar_funcion":
				if (typeof window[response.funcion] === "function") {
					consoleLog(response, lista_mensajes.functionFound);
					if (response.tres_puntos)
						window[response.funcion](...response.parametros);
					else window[response.funcion](response.parametros);
				} else consoleLog(response, lista_mensajes.functionNotFound);
				break;
			case "enviar_formulario":
				enviarFormulario(response.formulario, boton, tipo_modal, false, true, aplica_loading_text, desenfoca_boton, true);
				break;
			case "cortar":
				respuesta_cortar = false;
				break;
			case "swal_opciones":
				var element = $(document.activeElement);
				element.blur();
				Swal.fire(response.opciones_swal).then((respuesta) => {
					if (respuesta.isConfirmed) {
						var funcion = response.adicionales.funcion_confirm;
						var tres_puntos = response.adicionales.tres_puntos_confirm;
						var parametros = response.adicionales.parametros_confirm;
					} else if (respuesta.isDenied) {
						var funcion = response.adicionales.funcion_deny;
						var tres_puntos = response.adicionales.tres_puntos_deny;
						var parametros = response.adicionales.parametros_deny;
					} else if (
						respuesta.isDismissed ||
						response.opciones_swal.allowOutsideClick ||
						(response.opciones_swal.allowEscapeKey &&
							respuesta.dismiss === Swal.DismissReason.esc)
					) {
						var funcion = response.adicionales.funcion_cancel;
						var tres_puntos = response.adicionales.tres_puntos_cancel;
						var parametros = response.adicionales.parametros_cancel;
					}
					if (typeof window[funcion] === "function") {
						consoleLog(funcion, lista_mensajes.functionFound);
						if (tres_puntos) window[funcion](...parametros);
						else window[funcion](parametros);
						if (element.attr("data-cantidad_original"))
							element.val(element.attr("data-cantidad_original"));
					} else consoleLog(response, lista_mensajes.functionNotFound);
				});
				break;
			default:
				consoleLog(response, lista_mensajes.actionNotFound);
				break;
		}
	});
	return respuesta_cortar;
}

function actualizarInputs(inputs, tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton) {
	$.each(inputs, function (index, elemento) {
		if (elemento.tipo == "text") {
			$("#" + elemento.id).val(elemento.valor);
			$.each(elemento.datas, function (nombre, valor) {
				$("#" + elemento.id).attr("data-" + nombre, valor);
			});
			if (elemento.accionar) $("#" + elemento.id).trigger("input");
			if (elemento.focus) $("#" + elemento.id).focus();
			$("#" + elemento.id).prop("readonly", elemento.readonly);
		} else if (elemento.tipo == "hidden") {
			$("#" + elemento.id).val(elemento.valor);
		} else if (elemento.tipo == "span") {
			$("#" + elemento.id).html(elemento.valor);
		} else if (elemento.tipo == "img") {
			$("#" + elemento.id).attr("src", elemento.valor);
		} else if (elemento.tipo == "checkbox") {
			$("#" + elemento.id).prop("checked", elemento.valor).trigger("change");
			$("#" + elemento.id).prop("disabled", elemento.disabled);
		} else if (elemento.tipo == "button") {
			$("#" + elemento.id).prop("disabled", elemento.disabled);
			if (elemento.valor) {
				$("#" + elemento.id).html(elemento.valor);
			}
			if (elemento.mostrar != undefined) {
				if (elemento.mostrar) $("#" + elemento.id).removeClass("d-none");
				else $("#" + elemento.id).addClass("d-none");
			}
			if (elemento.click) {
				$("#" + elemento.id).trigger("click");
			}
		} else if (elemento.tipo == "div") {
			$.each(elemento.atributos_css, function (nombre, valor) {
				$("#" + elemento.id).css(nombre, valor);
			});
			$.each(elemento.atributos, function (nombre, valor) {
				$("#" + elemento.id).attr(nombre, valor);
			});
			if (elemento.mostrar != undefined) {
				if (elemento.mostrar) $("#" + elemento.id).removeClass("d-none");
				else $("#" + elemento.id).addClass("d-none");
			}
			if (
				typeof elemento.clase_remover == "string" &&
				elemento.clase_remover.length
			)
				$("#" + elemento.id).removeClass(elemento.clase_remover);
			if (typeof elemento.clase == "string" && elemento.clase.length)
				$("#" + elemento.id).addClass(elemento.clase);
		} else if (elemento.tipo == "range") {
			$("#" + elemento.id).prop("disabled", elemento.disabled);
			if (elemento.maximo != undefined && elemento.maximo != null) {
				consoleLog(elemento);
				if (elemento.maximo == 0) {
					$("#" + elemento.id).prop("disabled", true);
					$("#" + elemento.id).addClass("no-hover");
					if (!$("#" + elemento.id).next("input[type=hidden]").length)
						$("#" + elemento.id).after(
							"<input type='hidden' name='" +
							elemento.name +
							"[]' id='" +
							elemento.id +
							"' value='" +
							elemento.valor +
							"'>"
						);
				} else {
					$("#" + elemento.id).prop("disabled", false);
					$("#" + elemento.id).removeClass("no-hover");
					$("#" + elemento.id).attr("max", elemento.maximo);
					if ($("#" + elemento.id).next("input[type=hidden]").length)
						$("#" + elemento.id)
							.next("input[type=hidden]")
							.remove();
				}
			}
			if (typeof elemento.valor != undefined && elemento.valor != null)
				$("#" + elemento.id).val(elemento.valor);
			if (elemento.accionar) $("#" + elemento.id).trigger("input");
		} else if (elemento.tipo == "select") {
			$("#" + elemento.id).prop("disabled", elemento.disabled);
			if (elemento.vaciar) $("#" + elemento.id).empty();
			$.each(elemento.opciones, function (index, opcion) {
				let clases = "";
				if (opcion.clases != undefined) {
					$.each(opcion.clases, function (index, clase) {
						clases += clase + " ";
					});
					clases = clases.trim();
					clases = " class='" + clases + "'";
				}
				let data_atributos = "";
				if (opcion.datas != undefined) {
					$.each(opcion.datas, function (index, data_a) {
						data_atributos += " data-" + index + "='" + data_a + "'";
					});
				}
				$("#" + elemento.id).append(
					"<option value='" +
					opcion.id +
					"' " +
					clases +
					data_atributos +
					"" +
					(opcion.deshabilitado != undefined && opcion.deshabilitado != null
						? opcion.deshabilitado
						: "") +
					(opcion.seleccionado != undefined && opcion.seleccionado != null
						? " " + opcion.seleccionado
						: "") +
					">" +
					opcion.nombre +
					"</option>"
				);
			});
			if (elemento.seleccionado != undefined) {
				consoleLog(elemento);
				$("#" + elemento.id)
					.val(elemento.seleccionado)
					.trigger("change");
			}
			if (elemento.ocultar_contenedor)
				$("#" + elemento.div_contenedor).addClass("d-none");
			else $("#" + elemento.div_contenedor).removeClass("d-none");
			if (elemento.select2) {
				if (elemento.con_dropdown_parent) {
					$("#" + elemento.id).select2({
						dropdownParent: $("#" + elemento.dropdown_parent),
						...elemento.select2_extra,
					});
				} else $("#" + elemento.id).select2(elemento.select2_extra);
				if (elemento.focus) $("#" + elemento.id).select2("open");
			}
			if (elemento.focus && !elemento.select2) $("#" + elemento.id).focus();
		} else if (elemento.tipo == "file") {
			if (elemento.fileinput) {
				if (elemento.limpiar) $("#" + elemento.id).fileinput("clear");
			} else {
				if (elemento.limpiar) $("#" + elemento.id).val("");
				$("#" + elemento.id).val(elemento.valor);
			}
		}
	});
}

function showToastr(mensaje, titulo = "", tipo = "info", opciones = null, quitar_toasts_anteriores = null, validar_cadena = true) {
	if (opciones != null && opciones.onclick != null && validar_cadena)
		opciones.onclick = new Function(opciones.onclick);
	if (quitar_toasts_anteriores === true) toastr.clear();
	toastr.options = opciones;
	toastr[tipo](mensaje, titulo);
}

function swalFocus(titulo, mensaje, icono, id, formulario = null, select2 = false) {
	Swal.fire({
		title: titulo,
		html: mensaje,
		icon: icono,
		allowOutsideClick: false,
		allowEscapeKey: false,
		customClass: {
			actions: "my-actions",
			confirmButton: "left-gap order-1 confirm-button",
		},
		didClose: () => {
			if (id) {
				if (select2)
					$(((formulario != null && formulario != undefined) ? "#" + formulario + " " : "") + "#" + id).select2("open");
				else
					$(((formulario != null && formulario != undefined) ? "#" + formulario + " " : "") + "#" + id).focus();
			}
		},
		didOpen: () => {
			Swal.getConfirmButton().focus();
		}
	});
	if (id) {
		if (select2) {
			$(((formulario != null && formulario != undefined) ? "#" + formulario + " " : "") + "#" + id).addClass("is-invalid");
			$(((formulario != null && formulario != undefined) ? "#" + formulario + " " : "") + "#" + id).next(".select2").find(".select2-selection").addClass("is-invalid");
		} else
			$(((formulario != null && formulario != undefined) ? "#" + formulario + " " : "") + "#" + id).addClass("is-invalid");
	}
}

function consoleLog(objeto, mensaje = null) {
	if (!isDebugger)
		return;
	if (mensaje != null) console.log(mensaje, objeto);
	else console.log(objeto);
}

function abrirFancybox(configuracion) {
	if (configuracion.opts.afterClose != undefined && configuracion.opts.afterClose != null)
		configuracion.opts.afterClose = new Function(configuracion.opts.afterClose);
	if (configuracion.opts.beforeClose != undefined && configuracion.opts.beforeClose != null)
		configuracion.opts.beforeClose = new Function(configuracion.opts.beforeClose);
	if (configuracion.opts.afterShow != undefined && configuracion.opts.afterShow != null)
		configuracion.opts.afterShow = new Function(configuracion.opts.afterShow);
	if (configuracion.opts.beforeShow != undefined && configuracion.opts.beforeShow != null)
		configuracion.opts.beforeShow = new Function(configuracion.opts.beforeShow);
	if (idmodulo && configuracion.src && typeof configuracion.src === "string" && !configuracion.src.includes("idmodulo=")) {
		var sep = configuracion.src.includes("?") ? "&" : "?";
		configuracion.src += sep + "idmodulo=" + encodeURIComponent(idmodulo);
	}
	if (idsubmodulo && configuracion.src && typeof configuracion.src === "string" && !configuracion.src.includes("idsubmodulo=")) {
		var sep = configuracion.src.includes("?") ? "&" : "?";
		configuracion.src += sep + "idsubmodulo=" + encodeURIComponent(idsubmodulo);
	}
	$.fancybox.open(configuracion);
}

function cerrarFancybox() {
	if (hayFancyboxAbierto()) $.fancybox.getInstance().close();
}

function hayFancyboxAbierto() {
	return !!document.querySelector(".fancybox-container");
}

function redirigirPost(url, data) {
	var form = document.createElement("form");
	form.method = "post";
	form.action = url;
	for (var key in data) {
		if (data.hasOwnProperty(key)) {
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = key;
			input.value = data[key];
			form.appendChild(input);
		}
	}
	document.body.appendChild(form);
	form.submit();
}

window.cargarLista2 = defineFunction({
	url: { required: true },
	div: { required: true },
	tipo_agregar: { default: null },
	datos: { default: "" },
	form: { default: "" },
	tipo_modal: { default: 1 },
	onSuccess: { default: null },
}, (url, div, tipo_agregar, datos, form, tipo_modal, onSuccess) => {
	const $target = $("#" + div);
	if (!$target.length) {
		if (isDebugger) {
			Swal.fire("Error", lista_mensajes.noContainerFound + ": " + div, "error");
		}
		return;
	}
	var fetch_headers = {
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
	};
	if (datos == "") {
		if (form != "" && $("#" + form).length > 0) {
			$("#" + form).append("<input type='hidden' name='ultimo_acceso' id='ultimo_acceso' value='" + ultimo_acceso + "'>").append("<input type='hidden' name='idusuario_sesion' value='" + idusuario + "'>").append("<input type='hidden' name='idmodulo' value='" + idmodulo + "'>").append("<input type='hidden' name='idsubmodulo' value='" + idsubmodulo + "'>");
			datos = new FormData($("#" + form)[0]);
			fetch_headers = { headers: {} };
		}
	} else {
		datos += `&ultimo_acceso=${ultimo_acceso}&idusuario_sesion=${idusuario}&idmodulo=${idmodulo}&idsubmodulo=${idsubmodulo}`;
	}
	if (!window.fetch) {
		$.ajax({
			type: "POST",
			url: url,
			data: datos,
			processData: false,
			beforeSend: function () {
				showModal2(tipo_modal);
			},
			error: function (error) {
				hideModal2(tipo_modal);
				console.error(error);
			},
			success: function (data) {
				hideModal2(tipo_modal);
				if (tipo_agregar == 2) {
					data = data.replace("<head/>", "");
					$("#" + div).append(data);
				} else {
					$("#" + div).html(data);
				}
				if (typeof onSuccess === "function") onSuccess(data);
			},
		});
	} else {
		showModal2(tipo_modal);
		fetch(url, {
			method: "POST",
			body: datos,
			...fetch_headers,
		}).then((response) => {
			if (!response.ok) {
				if (response.status === 403) throw new Error("Forbidden");
				if (response.status === 401) {
					return response.json().then((data) => {
						throw {
							name: "SessionExpiredError",
							message: data.mensaje,
							code: 401,
							authToken: data.authToken,
						};
					});
				}
				if (response.status === 404) throw new Error(lista_mensajes.fileNotFound);
				throw new Error(lista_mensajes.unexpectedResponse);
			}
			return response.text();
		}).then((data) => {
			hideModal2(tipo_modal);
			const isHTML = /<[a-z][\s\S]*>/i.test(data);
			if (isHTML) {
				if (tipo_agregar == 2) {
					data = data.replace("<head/>", "");
					$("#" + div).append(data);
				} else $("#" + div).html(data);
			} else $("#" + div).text(data);
			$target.attr("data-caller-file", url);
			scrollToHash();
			if (typeof onSuccess === "function") onSuccess(data);
		}).catch((error) => {
			if (error.code === 401) {
				reconnectSwal(error);
				setTimeout(() => {
					hideModal2(tipo_modal);
				}, 3000);
				return;
			} else if (error.message == "Forbidden") {
				setTimeout(() => {
					hideModal2(tipo_modal);
				}, 3000);
				return;
			}
			const detalle = ((isDebugger) ? ": " + error : "");
			$("#" + div).html(`<div class="alert alert-danger" role="alert">${lista_mensajes.errorLoading}${detalle}</div>`);
			hideModal2(tipo_modal);
		});
	}
});

function reconnectSwal(error) {
	let html = '</div><form class="form-signin" id="formLogin" action="" method="post" data-toggle="validator" style="display: inherit !important;"><input type="hidden" name="authToken" id="authToken" value="' + error.authToken + '"/><input type="hidden" name="idempresa" id="idempresa" value="' + idempresa + '"/><input type="hidden" name="desde_swal" id="desde_swal" value="1" /><input type="email" id="txtUsuario" name="txtUsuario" class="swal2-input w-75" placeholder="E-Mail" required="" autofocus="" aria-invalid="true"><input type="password" id="txtPassword" name="txtPassword" class="swal2-input w-75" placeholder="Password" required="" aria-invalid="true"></form><div>';
	showToastr(
		error.message,
		"Error",
		"error",
		{
			timeOut: 10000,
			tapToDismiss: false,
			progressBar: true,
			newestOnTop: true,
			onclick: function () {
				clearTimeout(handle);
				Swal.fire({
					title: lista_mensajes.enterCredentials,
					html: html,
					icon: "info",
					showCancelButton: true,
					confirmButtonText: lista_mensajes.accept,
					cancelButtonText: lista_mensajes.exit,
					customClass: {
						actions: "my-actions",
						cancelButton: "left-gap order-1",
						confirmButton: "order-2",
					},
					preConfirm: async () => {
						try {
							let resultado = await login();
							return resultado?.result == "success";
						} catch (error) {
							console.error("error:", error);
							Swal.showValidationMessage(lista_mensajes.incorrectPassword);
							return false;
						}
					},
				}).then((respuesta) => {
					if (respuesta.isConfirmed)
						Swal.fire("Success", lista_mensajes.sessionRestored, "success");
					else if (respuesta.isDismissed) redirectToLogin();
				});
			},
		},
		null,
		false
	);
	handle = setTimeout(() => {
		redirectToLogin();
	}, 10000);
	return;
}

function redirectToLogin() {
	location.href = "/";
}

function handleResponse(response, boton, tipo_modal, incluye_carga_imagenes, desaparece_modal = true, aplica_loading_text = false, desenfoca_boton = false) {
	if (!incluye_carga_imagenes)
		hideModal2(tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton);
	if (typeof response === undefined || response === null)
		throw Object.assign(
			new Error(lista_mensajes.didntGetResponse),
			{ code: 1 }
		);
	if (typeof response === "object" && Object.keys(response).length === 0)
		throw Object.assign(new Error(lista_mensajes.noResponse), {
			code: 1,
		});
	if (response.respuesta === "error") throw { code: 2, response };
	if (Array.isArray(response.acciones) && response.acciones.length === 0)
		throw { code: 3, response };
	return procesarAcciones(response, tipo_modal, desaparece_modal, boton, aplica_loading_text, desenfoca_boton);
}

function handleError(error, tipo_modal, boton, desaparece_modal = true, aplica_loading_text = false, desenfoca_boton = false) {
	hideModal2(tipo_modal, boton, desaparece_modal, aplica_loading_text, desenfoca_boton, error.response?.disabled);
	if (error.code === 1) {
		Swal.fire("Error", error.message, "error");
	} else if (error.code === 2) {
		switch (error.response.tipo) {
			case "single_toast":
				showToastr(error.response.mensaje, error.response.titulo, error.response.icono, error.response.opciones, error.response.quitar_toasts_anteriores);
				break;
			case "notificacion":
				consoleLog(error.response);
				break;
			case "modal":
				showModal2(error.response.tipo_modal, error.response.boton, error.response.aplica_loading_text, error.response.mensaje, error.response.titulo);
				if (error.response.tipo_modal === 4) {
					$("#authToken").val(error.response.newToken);
					$("#formLogin").trigger("reset");
				}
				break;
			case "mensaje":
			default:
				swalFocus(error.response.titulo, error.response.mensaje, error.response.icono, error.response.id, error.response.formulario, error.response.select2);
		}
	} else if (error.code === 3)
		consoleLog(error.response, lista_mensajes.noActions);
	else if (error.code === 401) reconnectSwal(error);
	else if (error.code === 404)
		Swal.fire("Error", isDebugger ? lista_mensajes.fileNotFound + ": " + error.url : lista_mensajes.errorProcessingRequest, "error");
	else if (error.message == "NetworkError when attempting to fetch resource.")
		Swal.fire("Error", lista_mensajes.networkError, "error");
	else {
		const stackLines = error.stack.split("\n");
		var lineNumber;
		const lineMatch = stackLines[0]?.match(/:(\d+):(\d+)/);
		const lineMatch2 = stackLines[1]?.match(/:(\d+):(\d+)/);
		var columnNumber;
		if (lineMatch) {
			lineNumber = lineMatch[1];
			columnNumber = lineMatch[2];
			let lineNumber2 = lineMatch2[1];
			let columnNumber2 = lineMatch2[2];
			console.log(`Line: ${lineNumber2}, Column: ${columnNumber2}`);
		}
		Swal.fire("Error", lista_mensajes.errorProcessingRequest + (isDebugger ? ": " + error.message + ` Line: ${lineNumber}, Column: ${columnNumber}` : ""), "error");
	}
	return false;
}

function extractPath(url) {
	const match = url.match(/https?:\/\/[^\/]+(\S*)/);
	return match ? match[1] : "/";
}

function scrollToHash() {
	if (window.location.hash) {
		var id = window.location.hash.substring(1);
		var $el = $("#" + id);
		if ($el.length) {
			$("html, body").animate({
				scrollTop: $el.offset().top
			}, 500);
			return true;
		}
	}
	return false;
}

function withFlexibleParams(fn, paramConfig) {
	const paramNames = Object.keys(paramConfig);
	const requiredParams = paramNames.filter((key) => paramConfig[key].required);

	return function (...inputArgs) {
		let input = inputArgs.length === 1 ? inputArgs[0] : inputArgs;

		let args = {};

		if (Array.isArray(input)) {
			if (input.length < requiredParams.length) {
				throw new Error(
					`Expected at least ${requiredParams.length} values, got ${input.length}`
				);
			}
			paramNames.forEach((name, i) => {
				if (i < input.length) {
					args[name] = input[i];
				} else if ("default" in paramConfig[name]) {
					args[name] = paramConfig[name].default;
				}
			});
		} else if (typeof input === "object" && input !== null) {
			paramNames.forEach((name) => {
				if (name in input) {
					args[name] = input[name];
				} else if (paramConfig[name].required) {
					throw new Error(`Missing required parameter: '${name}'`);
				} else if ("default" in paramConfig[name]) {
					args[name] = paramConfig[name].default;
				}
			});
		} else if (inputArgs.length >= requiredParams.length) {
			paramNames.forEach((name, i) => {
				if (i < inputArgs.length) {
					args[name] = inputArgs[i];
				} else if ("default" in paramConfig[name]) {
					args[name] = paramConfig[name].default;
				}
			});
		} else {
			throw new Error("Invalid input format for parameters");
		}

		return fn(...paramNames.map((name) => args[name]));
	};
}

function trimCh(str, chars) {
	const regex = new RegExp(`^[${chars}]+|[${chars}]+$`, "g");

	return str.replace(regex, "");
}

const regexCurp = /^[A-Z]{1}[AEIOUX]{1}[A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]\d$/i;

function esCurpValida(curp) {
	return regexCurp.test(curp.toUpperCase());
}

const regexRfc = /^([A-ZÑ&]{3}|[A-ZÑ&]{4})\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z0-9]{3}$/i;

function esRfcValido(rfc) {
	return regexRfc.test(rfc.toUpperCase());
}

/**
 * Agrega un badge a un elemento específico
 * @param {string|number} value - El valor que se mostrará dentro del badge
 * @param {string} selector - Selector CSS del elemento al que se agregará el badge
 */
function agregarBadge(value, selector) {
	if (value > 0) {
		// Crear el badge
		var badgeHTML = '<sup><span style="position: absolute; top: -55px; right: -5px;" class="badge badge-danger rounded-pill">'
			+ value +
			'</span></sup>';

		// Agregarlo al elemento seleccionado
		var elemento = document.querySelector(selector);
		if (elemento) {
			elemento.insertAdjacentHTML('beforeend', badgeHTML);
		} else {
			console.warn('No se encontró el selector:', selector);
		}
	}
}

function cerrarTodosFancys() {
	var inst;
	while ((inst = parent.jQuery.fancybox.getInstance())) inst.close();
}

/**
 * Normaliza un valor HEX de color. Si es invalido o vacio, retorna el fallback.
 * @param {string} valor - Color HEX a normalizar (con o sin #)
 * @param {string} fallback - Color HEX por defecto si el valor es invalido
 * @returns {string} Color HEX normalizado con # (ej. "#103B74")
 */
function normalizarColorHex(valor, fallback) {
	fallback = (fallback || "").trim().toUpperCase();
	valor = (valor || "").trim().toUpperCase();
	if (valor === "") return fallback;
	valor = valor.replace(/^#/, "");
	if (/^[0-9A-F]{6}$/.test(valor)) return "#" + valor;
	return fallback;
}

/**
 * Inicializa un fancy con el patron estandar: ajusta contenedor Fancybox
 * e inicializa Select2.
 * @param {string} selector - Selector del root del fancy (ej. "#fancyAsignarEstilo")
 */
function inicializarFancyX(selector) {
	var $fancy = $(selector);
	var $fancyboxContent = $fancy.closest(".fancybox-content");

	if ($fancyboxContent.length) {
		$fancyboxContent.css({
			"padding": "0",
			"background": "transparent",
			"overflow": "hidden",
			"border-radius": "14px"
		});
	}

	var scriptTag = document.getElementById("funciones");
	var color1 = scriptTag ? scriptTag.dataset.color1 : "";
	var color2 = scriptTag ? scriptTag.dataset.color2 : "";
	if (color1) $fancy[0].style.setProperty("--fancy-color-1", color1);
	if (color2) $fancy[0].style.setProperty("--fancy-color-2", color2);

	$(selector + " .select2").select2({
		dropdownParent: $(selector)
	});
}