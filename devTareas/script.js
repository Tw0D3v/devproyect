/ eslint-disable-next-line no-var, no-unused-vars
var _CONSIGNACIONES = "../controladores/funcionesConsignaciones.php";
// eslint-disable-next-line no-unused-vars, no-undef, no-var
var Consignaciones = class extends Formulario3 {
    constructor (aParametros) {
        super(aParametros);
        const frm = this;
        frm.oidConsignacion = 0;
        frm.vrConsignacion = 0;
        frm.vrCruce = 0;
        frm.vrPdte = 0;
        frm.aElegidos = {};
        frm.vrActual = 0;
        frm.vrPdteItem = 0;
        frm.tipoMayor = 0;
        frm.oidTercero = 0;
        frm.botonActual = "btnGestion";
        frm.accionActual = "consignacion";
        frm.uploadFiles = null;
        frm.assignChange();
        frm.assignClicks();
        frm.instantiateTables();
        frm.instantiateHelps();
        frm.cambiarPropiedad();
        frm.procesarSubidos();
    }

    assignClicks () {
        const frm = this;
        /* activar botones de menú izquierda */
        frm._$(".optMenu").click(function (evt) {
            const btn = (evt.target.nodeName == "'BUTTON'") ? evt.target : evt.target.parentNode;
            frm._$(".optMenu").removeClass("optMenuElegido");
            frm._$(btn).addClass("optMenuElegido");
            frm._$("#seccGestionar,#seccHistorico,#seccCxp").hide();
            frm.botonActual = btn.id;
            switch (btn.id) {
            case "btnGestion":
                frm.oidConsignacion = 0;
                frm.accionActual = "consignacion";
                frm._$("#seccGestionar, #navGestion, #frmConsignacion").show();
                frm.listarConsignaciones({ tipo: "gestion" });
                break;
            case "btnHistorico":
                frm._$("#seccGestionar,#tblAbonos,#btnRptAbonos").hide();
                frm._$("#seccHistorico").show();
                frm.listarConsignaciones({ tipo: "historico" });
                break;
            case "btnCxPagar":
                frm._$("input[name=optTipoInfo][value='CXP']").checked(true);
                frm._$("#seccCxp").show();
                frm.listarCtasCruzar({ tipo: "consulta", ocultarBoton: "N" });
                break;
            }
        });
        /* activar botón menú */
        frm._$("#iconAbrirMenu").click(function () {
            if (frm._$(".formFiltro").element.style.display == "flex") {
                frm._$(".formFiltro").hide();
            } else {
                frm._$(".formFiltro").show();
            }
        });
        /* activar cambio de fecha,ciudad y oficina */
        frm._$("#fechaini,#fechafin,#cmbCiudadFiltro,#cmbOficinaFiltro").change(function () {
            if (this.id == "cmbCiudadFiltro") {
                frm.listarOficinas("cmbOficinaFiltro", this.value);
            }
        });
        /* activar boton filtros */
        frm._$("#btnFiltros").click(function () {
            const idBoton = (frm.botonActual != "btnGestion" || frm.accionActual === "consignacion") ? frm._$(".optMenuElegido").val("id") : "btnCxPagar";
            frm._$("#frmDatos").hide();
            switch (idBoton) {
            case "btnGestion":
                frm.listarConsignaciones({ tipo: "gestion", accionActual: frm.accionActual });
                break;
            case "btnHistorico":
                frm._$("#seccGestionar").hide();
                frm._$("#seccHistorico").show();
                frm._$("input[name=optTipoInfo]:checked").val("");
                frm._$("input[name=optBanco]:checked").val("");
                frm._$("input[name=optRecibido]:checked").val("");
                if (frm._$("input[name=optTipoInfo]:checked").val() == "CG") {
                    frm._$("#tblCgHistorico,#btnRptCgHistorico").show();
                    frm._$("#tblAbonos,#btnRptAbonos").hide();
                    const aDatos = { tipo: "historico", chkBanco: frm.chkBanco, chkRecibido: frm.chkRecibido };
                    frm.listarConsignaciones(aDatos);
                } else {
                    frm._$("#tblCgHistorico,#btnRptCgHistorico").hide();
                    frm._$("#tblAbonos,#btnRptAbonos").show();
                    frm.listarCtasCruzar({ tipo: "consulta", alcance: "historico" });
                }
                break;
            case "btnCxPagar":
                frm.aParametros = (frm.botonActual == "btnGestion") ? { ocultarBoton: "S" } : { tipo: "consulta" };
                frm.listarCtasCruzar(frm.aParametros);
                break;
            }
        });
        /* activar botón mostrar frmConsignacion */
        frm._$("#btnNvaCsg").click(function () {
            frm.botonActual = "btnGestion";
            frm.accionActual = "";
            frm._$("lstUploadFiles").html("");
            frm._$(".noedita").disabled(false);
            frm._$("#cmbCuentas").val("elegir");
            frm._$("#fechaConsignacion").datepicker("setDate", new Date());
            frm._$("#txtVrEfectivo, #txtVrCheque, #txtVrTdebito, #txtVrTcredito,#txtVrTransfer,#txtVrVales,#txtTotal,#txtPdte,#txtMayorValor,#txtMenorValor").val("0");
            frm._$("#txtDescripcion").val("");
            frm._$("#txtNota").val("");
            frm._$("#E_tercero").val("");
            frm._$("#cmbTipoDocumento").val("elegir");
            frm._$("#navGestion, #tblConsignados_wrapper").hide();
            frm._$("#frmConsignacion").show();
            frm.activarGrabarCsg();
        });
        frm._$("#btnCancelar").click(function () {
            frm.oidConsignacion = 0;
            frm.$("#btnGestion").click();
        });
        // ACTIVAR BOTON REPORTE CONSIGNACION
        frm._$("#btnReporte").click(function () {
            const datosForm = Formulario3.tomarCamposForm(frm._$("#frmDatos"));
            frm.post("../controladores/funcionesControlconsig.php", { funcionphp: "armarReporte", datosForm: datosForm }, function (data) {
                window.open(data.xls, "reporte");
            });
        });
        /* activar grabar detalle */
        frm._$("#btnGrabarDetalle").click(function () {
            this.disabled(false);
            const noContar = [0];
            if (Object.keys(noContar).length - noContar > 0) {
                frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "grabarDetalle", oidCsg: frm.oidConsignacion, noContar: [] }, function (data) {
                    if (data.msgError) {
                        console.log("error");
                    } else {
                        frm._$("#btnCancelar").click();
                    }
                });
            } else {
                Notiflix.Report.Warning("Atencion !!!",
                    "Al menos un registro debe tener valor a cruzar", "Entendido",
                    function () {
                        frm._$("#btnGrabarDetalle").disabled(true);
                    });
            }
        });
        /* activar boton reporte cxcruzar */
        frm._$("#btnListarCxP").click(function () {
            frm.listarCtasCruzar({ salida: "archivo", tipo: "consulta" });
        });
        /* activar boton reporte histórico consignaciones */
        frm._$("#btnRptCgHistorico").click(function () {
            frm.listarConsignaciones({
                tipo: "historico",
                salida: "archivo",
                chkBanco: frm._$("input[name=optBanco]:checked").val(""),
                chkRecibido: frm._$("input[name=optRecibido]:checked").val("")
            });
        });
        /* activar boton reporte histórico abonos */
        frm._$("#btnRptAbonos").click(function () {
            frm.listarCtasCruzar({
                tipo: "historico",
                salida: "archivo"
            });
        });
    }

    assignChange () {
        const frm = this;
        frm._$("#E_ciudad").change(function () {
            frm.listarCuentas("cmbCuentas");
            frm.listarOficinas("E_oidoficina");
        });
        frm._$("#cmbCiudadFiltro").change(function () {
            frm.listarCuentas("cmbCtaFiltro");
        }); 
        frm._$("#cmbTipoDocumento").change(function () {
            frm.post("../controladores/funcionesTiposDocumentos.php", { funcionphp: "obtenerTipo", oidTipo: this.value }, function (data) {
                if (data.tipo) {
                    frm.tipoMayor = data.tipo;
                } else {
                    Notiflix.Report.Warning("ATENCION !!!", data.msgError, "Entendido",
                        function () {
                            frm._$("#cmbTipoDocumento").val("elegir");
                        });
                }
            });
        });
        // CAMBIO VALOR CONSIGNACION Y VALOR VALES
        frm._$("#txtVrEfectivo,#txtVrCheque,#txtVrTdebito,#txtVrTcredito,#txtVrTransfer,#txtVrVales,#txtMayorValor,#txtMenorValor").change(function () {
            frm.recalcular();
        });
        frm.activarGrabarCsg();
        // activar cambio de cuenta bancaria
        frm._$("#cmbCuentas").change(function () {
            if (this.value != "elegir") {
                frm.post("../controladores/funcionesCuentasBancos.php", { funcionphp: "obtenerCuenta", oidCuenta: this.value }, function (data) {
                    if (data.cuenta) {
                        const contenido = "Titular: " + data.cuenta.titular + "</br>" +
                                                    "Banco: " + data.cuenta.banco;
                        frm._$("#datosCuenta").html(contenido);
                    }
                });
            }
        });
        // activar cambio de filtro Tercero documento
        frm._$("#E_tercero").change(function () {
            frm.listarCtasCruzar({ tipo: "consulta", ocultarBoton: "S", sinFechas: "X" });
        });
    }

    adicionalInicializar () {
        const frm = this;
        frm.listarCiudades("E_ciudad");
        frm.listarCiudades("cmbCiudadFiltro");
        frm.listarTiposDocumento("cmbTipoDocumento,cmbTipoDocFiltro");
        frm._$("#fechaini").dateTime({
            dateFormat: "YYYY-mm-dd",
            altField: "#dshNfechaini" + frm.oidOpcion,
            altFormat: "M dd/ YYYY",
            timeFormat: false
        });
        frm._$("#fechafin").dateTime({
            dateFormat: "YYYY-mm-dd",
            altField: "#dshNfechafin" + frm.oidOpcion,
            altFormat: "M dd/ YYYY",
            timeFormat: false
        });
        frm._$("#fechaConsignacion").dateTime({
            dateFormat: "YYYY-mm-dd",
            altField: "#dshNfecha" + frm.oidOpcion,
            altFormat: "M dd/ YYYY"
        });

        if (frm._$("#seccCxp").element.style.display == "none") {
            frm.listarConsignaciones({ tipo: "historico" });
        }
    }

    instantiateHelps () {
        const frm = this;
        const aAyuda = [];
        aAyuda["#hlpTerc"] = {
            modulo: "TERCEROS",
            campo: "E_tercero" + frm.oidOpcion,
            label: "dshNtercero" + frm.oidOpcion
        };
        aAyuda["#hlpTercAsoc"] = {
            modulo: "TERCEROS",
            campo: "txtTercero" + frm.oidOpcion,
            label: "dshNtercasoc" + frm.oidOpcion
        };
        const helpWindow = new Ayuda3(aAyuda, {
            oidOpcion: frm.oidOpcion,
            isNew: true
        });
        helpWindow.activateHelp();
        const aVrf = [];
        aVrf["E_tercero" + frm.oidOpcion] = {
            modulo: "TERCEROS",
            retornoFormulario: "dshNtercero" + frm.oidOpcion,
            vacio: "TODOS LOS TERCEROS"
        };
        aVrf["txtTercero" + frm.oidOpcion] = {
            modulo: "TERCEROS",
            retornoFormulario: "dshNtercasoc" + frm.oidOpcion,
            vacio: "SIN TERCERO ASOCIADO",
            retornoAdicional: {
                oid: (oid) => {
                    frm._$("#E_tercero").element.value = (frm._$("#txtTercero").val());
                    frm._$("#dshNtercero").element.innerHTML = (frm._$("#dshNtercasoc").html());
                }
            }
        };
        Formulario3.activarVerificacion(aVrf);
    }

    listarCiudades (idCombo = "E_ciudad") {
        const frm = this;
        const idComboCuentas = (idCombo == "E_ciudad") ? "cmbCuentas" : "cmbCtaFiltro";
        frm.listarCatalogo2({
            idCombo: [idCombo],
            nombreCatalogo: "Ciudades",
            singular: "ciudad",
            archivoFn: "CiudadesCorreo",
            aPost: {
                soloPropias: "S",
                tipoRetorno: 2,
                tipoFiltro: "todas"
            },
            funcionOK: function (electa) {
                if (idComboCuentas == "cmbCtaFiltro" || idComboCuentas == "cmbCuentas") {
                    frm.listarOficinas("E_oidoficina", electa);
                }
                if (idCombo == "cmbCiudadFiltro") { frm.listarOficinas("cmbOficinaFiltro", electa) }
            },
            funcionFail: function () {
                cerrarPestaña(frm.oidOpcion);
            }
        });
    }

    listarCuentas (idCombo, idCiudad, idCuenta) {
        const frm = this;
        const opciones = (idCombo == "cmbCuentas") ? { oidCiudad: idCiudad } : { tipoFiltro: "todas", oidCiudad: idCiudad };
        opciones.idCuenta = idCuenta;
        frm.listarCatalogo2({
            idCombo: [idCombo],
            nombreCatalogo: "Cuentas",
            singular: "cuenta",
            archivoFn: "CuentasBancos",
            aPost: opciones,
            funcionOK: function () {
                if (idCombo == "cmbCuentas") {
                    frm._$("#cmbCuentas").change();
                }
                if (idCombo == "cmbCtaFiltro") {
                    const evento = new CustomEvent("evtCtlg");
                    document.dispatchEvent(evento);
                }
            },
            funcionFail: function () {
                cerrarPestaña(frm.oidOpcion);
            }
        });
    }

    listarOficinas (idCombo, idCiudad, oidElecta) {
        const frm = this;
        const cFiltro = (idCombo == "cmbOficinaFiltro") ? "todas" : "elegir";
        frm.listarCatalogo2({
            idCombo: [idCombo],
            nombreCatalogo: "Oficinas",
            singular: "oficina",
            aPost: {
                oidCiudad: idCiudad,
                tipoFiltro: cFiltro,
                idOficina: oidElecta
            },
            funcionOK: function () {
                frm._$("#cmbOficinaFiltro").val("todas");
            },
            funcionFail: function () {
                cerrarPestaña(frm.oidOpcion);
            }
        });
    }

    listarTiposDocumento (idCombo) {
        const frm = this;
        frm.listarCatalogo2({
            idCombo: [idCombo],
            nombreCatalogo: "Tipos",
            singular: "tipo",
            archivoFn: "TiposDocumentos",
            aPost: {
                actividad: "CRUCE"
            },
            funcionFail: function () {
                cerrarPestaña(frm.oidOpcion);
            }
        });
    }

    activarGrabarCsg () {
        const frm = this;
        const btnConsig = frm._$("#btnGrabar").element;
        const btnSaveConsig = function () {
            frm.quitarMsgError();
            frm.accionActual = "";
            const datosForm = Formulario3.tomarCamposForm(frm._$("#frmConsignacion"),
                { oidTbl: frm.oidConsignacion, tipoMayor: frm.tipoMayor, oidTercero: frm.oidTercero });
            frm.post("../modelos/modeloFormulario2.php", { funcionphp: "validarDatos", datosForm: datosForm, aFiltros: Formulario3.filtrosDatos }, function (data) {
                if (data.msgNoValido) {
                    frm.ubicarMsgError(data.msgNoValido);
                    frm._$("#btnGrabar").disabled(true);
                } else {
                    // Graba encabezado documento y habilita detalle
                    frm.grabarConsignacion(data.datosFrm);
                }
            });
        };
        btnConsig.removeEventListener("click", btnSaveConsig);
        btnConsig.addEventListener("click", btnSaveConsig);
    }

    grabarConsignacion (datosForm) {
        const frm = this;
        const datos = { data: { funcionphp: "grabarConsignacion", datosForm: frm.datosForm } };
        frm.uploadFiles.sendFiles("../controladores/funcionesConsignaciones.php", datos, function (data) {
            if (data.msgError) {
                Notiflix.Report.Warning("Atencion !!!", data.mensaje, "Entendido");
            } else {
                Notiflix.Report.Warning("SU-WEB", "Datos de consignación grabados", function () {
                    frm.listarAdjuntos(data.oidEncabezado);
                    frm.vrEfectivo = JSON.parse(datosForm).txtVrEfectivo;
                    frm.vrCheque = JSON.parse(datosForm).txtVrCheque;
                    frm.vrTdebito = JSON.parse(datosForm).txtVrTdebito;
                    frm.vrTcredito = JSON.parse(datosForm).txtVrTcredito;
                    frm.vrTransfer = JSON.parse(datosForm).txtVrTransfer;
                    frm.vrConsignacion = frm.vrEfectivo + frm.vrCheque + frm.vrTdebito + frm.vrTcredito + frm.vrTransfer;
                    frm.recalcular();
                    frm._$("#btnGrabar").disabled(true);
                    frm._$("#btnGrabarDetalle").disabled(true);
                    frm.tblDetalle.clear();
                    frm.tblDetalle.data = [];
                    frm._$("#tblDetalle_wrapper").hide();
                    frm.oidConsignacion = (data.oidEncabezado) ? data.oidEncabezado : JSON.parse(datosForm).oidTbl;
                    frm._$("#dshNtercero").html("");
                    frm.listarCtasCruzar({ ocultarBoton: "S", sinFechas: "X" });
                    frm._$("#seccCxp,#btnGrabarDetalle").show();
                });
            }
        });
    }

    listarConsignaciones (aDatos) {
        const frm = this;
        frm._$("input[name=optBanco]:checked").val("");
        frm._$("input[name=optRecibido]:checked").val("");
        const tipo = (aDatos.tipo) ? aDatos.tipo : "";
        frm._$("#frmConsignacion, #seccCxp,#tblDetalle_wrapper").hide();
        if (tipo == "" || frm.accionActual == "consignacion") {
            frm._$("#tblConsignados_wrapper").show();
            frm._$("#navGestion").css({ display: "flex" });
            if (!aDatos.salida) {
                frm.tblConsignados.clear();
                frm.tblConsignados.data = [];
                } else {
                if (!aDatos.salida) {
                    frm.tblCgHistorico.clear();
                    frm.tblCgHistorico.data = [];
                }
            }
            if (!aDatos.salida) {
                aDatos.salida = "pantalla";
            }
            const datosForm = Formulario3.tomarCamposForm(frm._$("#frmDatos"), aDatos);
            frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "listarConsignaciones", datosForm: datosForm }, function (data) {
                if (data.nombreSession) {
                    frm.Reporte.generarGenericoXls(data.nombreSession);
                } else {
                    if (data.listadoDatos) {
                        if (tipo == "" || tipo == "gestion") {
                            frm.tblConsignados.insert(data.listadoDatos);
                            frm.formatearCamposNumero();
                        } else {
                            frm.tblCgHistorico.insert(data.listadoDatos);
                        }
                    }
                }
            }).Notiflix.Report.Warning("Atencion !!!",
                "No se pudo listar consignaciones, por favor intente nuevamente",
                "Entiendo",
                () => {
                    cerrarPestaña(frm.oidOpcion);
                });
        }
    }

    tomarConsignacion (oidRegistro, accion = "gestion") {
        const frm = this;
        frm._$("#lstUploadFiles").html("");
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "tomarConsignacion", oidRegistro: frm.oidRegistro }, function (data) {
            if (data.msgError) {
                console.log("error");
            } else {
                if (data.datosForm) {
                    const aDatos = data.datosForm;
                    for (const campo in aDatos) {
                        if (frm._$("campo")) {
                            frm._$("campo").val(aDatos[campo]);
                        }
                        if (campo == "dshNtercasoc") {
                            frm._$("#campo").html(aDatos[campo]);
                        }
                        if (campo == "oidTercero") frm.oidTercero = aDatos[campo];
                        if (campo == "E_tipo") {
                            frm._$("input[name=E_tipo][value='" + aDatos[campo] + "']").checked(true);
                        }
                    }
                    frm.listarOficinas = ("E_oidoficina", data.datosForm.E_ciudad, data.datosForm.E_oidoficina);
                    if (aDatos.txtTercero != "") frm._$("E_tercero").val(aDatos.txtTercero);
                    frm.oidConsignacion = oidRegistro;
                    frm._$(".noedita").disabled(true);
                    if (accion == "gestion") {
                        frm.activarGrabarCsg();
                    } else {
                        frm._$("#btnGrabar").disabled(true);
                        // ESTO QUEDA PENDIENTE ASIGNAR AGO 29 2022 frm._$("#btnGrabar").off("click");
                    }
                    frm._$("#seccGestionar,#frmConsignacion").show();
                    frm._$("#navGestion, #tblConsignados_wrapper, #seccHistorico");
                    frm.aElegidos = {};
                    frm.$("#fechaConsignacion").datepicker("setDate", aDatos.fechaConsignacion);
                    frm._$("E_ciudad").val((aDatos.E_ciudad > 0) ? aDatos.E_ciudad : "todas");
                    frm.listarAdjuntos(oidRegistro);
                    frm.listarCuentas("cmbCuentas", aDatos.E_ciudad, aDatos.cmbCuentas);
                    frm.listarDetalle(oidRegistro, accion);
                }
            }
        });
    }

    listarCtasCruzar (parametros) {
        const frm = this;
        if (parametros.ocultarBoton && parametros.ocultarBoton == "S") {
            frm._$("#btnListarCxP").hide();
        }
        if (frm.botonActual == "btnCxPagar") { frm._$("#btnListarCxP").show() }
        const aOpciones = parametros;
        aOpciones.botonActual = frm.botonActual;
        if (frm._$("#cmbTipoDocumento").val() != "elegir") {
            aOpciones.tipoDocumento = frm._$("#cmbTipoDocumento").val();
        }
        if (frm._$("#cmbTipoDocFiltro").val() != "elegir") {
            aOpciones.tipoDocumento = frm._$("cmbTipoDocFiltro").val();
        }
        if (frm._$("#E_tercero").val() != "") {
            aOpciones.terceroDoc = frm._$("#E_tercero").val();
        }
        if (!parametros.salida) {
            frm.tblCxP.clear();
            frm.tblCxP.data = [];
            frm.tblAbonos.clear();
            frm.tblAbonos.data = [];
            aOpciones.salida = "pantalla";
        }
        if (frm.vrPdte > 0 || parametros.tipo) {
            if (parametros.tipo) {
                aOpciones.tipo = parametros.tipo;
                if (parametros.tipo == "consulta" && frm.botonActual != "btnGestion") { frm._$("#btnGrabarDetalle").hide() }
            } else {
                aOpciones.cmbCiudadFiltro = (frm._$("#cmbCiudadFiltro").val() > 0) ? frm._$("cmbCiudadFiltro").val() : frm._$("E_ciudad").val();
            }
            const datosForm = Formulario3.tomarCamposForm(frm._$("#frmDatos"), aOpciones);
            frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "listarCxP", datosForm: datosForm }, function (data) {
                if (data.nombreSession) {
                    frm.Reporte.generarGenericoXls(data.nombreSession);
                } else {
                    if (data.listaCxP) {
                        if (frm.botonActual == "btnHistorico") {
                            frm.tblAbonos.insert(data.listaCxP);
                        } else {
                            frm.tblCxP.insert(data.listaCxP);
                        }
                        frm.formatearCamposNumero();
                    }
                }
            });
        } else {
            if (frm.oidConsignacion > 0) {
                Notiflix.Report.Warning("Atencion !!!", "La consignación no tiene pendientes por cruzar", "Entendido", function () {
                    frm._$("#btnCancelar").click();
                });
            }
        }
    }

    listarDetalle (oidCsg, accion) {
        const frm = this;
        frm.$("#tblDetalle_wrapper").show();
        frm.tblDetalle.clear();
        frm.tblDetalle.data = [];
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "listarDetalle", oidCsg: frm.oidCsg, accion: accion }, function (data) {
            if (data.lstRegistros) {
                frm.tblDetalle.insert(data.lstRegistros);
            }
            frm.aElegidos["0"] = data.vrCruce;
            frm.vrPdte = frm.vrConsignacion - data.vrCruce;
            frm.vrCruce = data.vrCruce;
            frm._$("txtPdte").val(frm.formatoNumero(frm.vrConsignacion - data.vrCruce));
            frm.recalcular();
        });
    }

    recalcular () {
        const frm = this;
        let efectivo = frm._$("#txtVrEfectivo").val();
        efectivo = efectivo.replace(/,/g, "");
        let cheque = frm._$("#txtVrCheque").val();
        cheque = cheque.replace(/,/g, "");
        let tDebito = frm._$("txtVrTdebito").val();
        tDebito = tDebito.replace(/,/g, "");
        let tCredito = frm._$("#txtVrTcredito").val();
        tCredito = tCredito.replace(/,/g, "");
        let transfer = frm._$("txtVrTransfer").val();
        transfer = transfer.replace(/,/g, "");
        let vales = frm._$("txtVrVales").val();
        vales = vales.replace(/,/g, "");
        let menorValor = frm._$("txtMenorValor").val();
        menorValor = menorValor.replace(/,/g, "");
        let mayorValor = frm._$("txtMayorValor").val();
        mayorValor = mayorValor.replace(/,/g, "");
        const total = efectivo * 1 + cheque * 1 + tDebito * 1 + tCredito * 1 + transfer * 1 + vales * 1 + menorValor * 1 - mayorValor * 1;
        frm._$("#txtTotal").val(frm.formatoNumero(total));
        frm._$("#txtPdte").val(frm.formatoNumero(total - frm.vrCruce));
        frm.vrPdte = total - frm.vrCruce;
        if (total < frm.vrCruce && frm.vrPdte != 0) {
            Notiflix.Report.Warning("Atencion", "Lo consignado no debe ser inferior a la suma de los cruces registrados", "Entendido", function () {
                frm._$("#btnGrabar").disabled(true);
            });
        } else {
            frm.vrConsignacion = total;
            frm._$("#btnGrabar").disabled(true);
            frm.vrPdte = total - frm.vrCruce;
        }
    }

    ConfirmBorraItem (boton) {
        const frm = this;
        boton.disabled(true);
        Notiflix.Confirm.show("ATENCION !!!",
            "Esta seguro de borrar el ítem ?",
            "SI",
            "NO",
            function (type) {
                if (type === "SI") {
                    frm.borrarDetalle(boton);
                }
                boton.disabled(true);
            }
        );
    }

    borrarDetalle (boton) {
        const frm = this;
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "borrarDetalle", idBoton: boton.id }, function (data) {
            if (data.msgError) {
                Notiflix.Report.Warning("SU-WEB", data.mensaje, "Entendido");
            } else {
                frm.vrPdte = frm.vrPdte + (data.valorItem) * 1;
                frm.vrCruce = frm.vrCruce - (data.valorItem) * 1;
                frm._$("#txtPdte").val(frm.formatoNumero(frm.vrPdte));
                frm.tblDetalle.row(frm._$(boton).element.parent().element.parent()).remove();
            }
        });
    }

    cambiarPropiedad (col, moid, estado) {
        const frm = this;
        const datosForm = JSON.stringify({
            campo: (col == 6) ? "banco" : "recibido", oidReg: moid, estado: estado
        });
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "cambiarPropiedad", datosForm: datosForm }, function (data) {
            if (data.msgError) {
                Notiflix.Report.Warning("Atencion !!!", data.msgError, "Entiendo");
            }
        }, function (evento) {
            const mensaje = "No se pudo cambiar el estado, por favor intente nuevamente </br>";
            Formulario3.msgFail(evento, mensaje, "warning", () => {
                cerrarPestaña(frm.oidOpcion);
            });
        });
    }

    procesarSubidos (respuesta) {
        const frm = this;
        if (respuesta.msgError) {
            Notiflix.Report.Warning("Atencion !!!", respuesta.msgError, "Entendido");
        } else {
            for (const archivo in respuesta.lstArchivos) {
                frm._$("#imgConsigna").html("<img src='../informacion/documentos/" + respuesta.lstArchivos[archivo] + "'/>");
            }
        }
    }

    listarAdjuntos (oidRegistro) {
        const frm = this;
        frm._$("divAdjuntos").html("");
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "listarAdjuntos", oidRegistro: oidRegistro }, function (data) {
            if (data.lstArchivos) {
                for (const adj of data.lstArchivos) {
                    UploadFiles.createImagePreview({
                        preview: `/consignaciones/${adj.archivo}`,
                        file: `/consignaciones/${adj.archivo}`
                    }, () => {
                        Notiflix.Confirm.show(
                            "ATENCION !!!",
                            "Está seguro de borrar este archivo?",
                            "SI",
                            "NO",
                            function (type) {
                                if (type == "SI") {
                                    frm.borrarAdjunto(adj.oidAdjunto, oidRegistro);
                                }
                            }
                        );
                    }, null, "divAdjuntos", "120px", "120px");
                }
            }
        });
    }

    borrarAdjunto (oidAdjunto, oidRegistro) {
        const frm = this;
        frm.post("../controladores/funcionesConsignaciones.php", { funcionphp: "borrarAdjuntos", oidAdjunto: oidAdjunto }, function (data) {
            if (data.msgError) {
                Notiflix.Report.Warning("Atencion !!!", data.msgError, "Entendido");
            } else {
                frm.listarAdjuntos(oidRegistro);
            }
        });
    }

    instantiateTables () {
        const frm = this;
        frm.tblConsignados = new simpleDatatables.DataTable("#tblConsignados" + frm.oidOpcion, {
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            labels: {
                placeholder: "Buscando registros...",
                noRows: "No hay registros con estos parámetros",
                info: "Mostrando {rows} registros"
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                select: [1],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });

        frm.tblConsignados.on("dataTable.update", function () {
            frm._$("#tblConsignados").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("title", "click para elegir esta categoria").click(function () {
                    const idCat = this.querySelectorAll("span").id;
                    const tituloTcp = this.querySelectorAll("span").innerHTML;
                    const aDatos = {
                        titulo: tituloTcp,
                        oid: idCat
                    };
                    frm.listarConsignaciones(aDatos);
                });
            });
        });

        frm.tblCxP = new simpleDatatables.DataTable("#tblCxP" + frm.oidOpcion, {
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                select: [0],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });
        frm.tblCxP.on("datatable.update", function () {
            frm._$("#tblCxP").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("titulo", "click para elegir Topicos");
                tdAction.change(function () {
                    const vrCasilla = this.value;
                    const vrCampo = vrCasilla.replace(/,/g, "");
                    let tAbonos = 0;
                    for (const key in frm.aElegidos) {
                        if (key != this.id) { tAbonos += frm.aElegidos[key] }
                    }
                    frm.vrPdte = frm.vrConsignacion - tAbonos;
                    if (vrCampo * 1 > frm.vrPdteItem) {
                        frm.vrCampo = frm.vrPdteItem;
                    }
                    if (vrCampo * 1 > frm.vrPdte) {
                        frm.vrCampo = frm.vrPdte;
                    }
                    this.value = vrCampo;
                    if (vrCampo * 1 > 0) {
                        frm.aElegidos[this.id] = vrCampo * 1;
                    } else {
                        delete frm.aElegidos[this.id];
                    }
                    frm.vrPdte = frm.vrPdte - vrCampo * 1;
                    frm._$("#txtPdte").val(frm.formatoNumero(frm.vrPdte));
                });
            });
        });

        frm.tblDetalle = new simpleDatatables.DataTable("#tblDetalle" + frm.oidOpcion, {
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                targets: 5,
                createdCell: function (td, cellData, rowData, row, _col) {
                    frm._$(td).addClass("tdcmd");
                }
            }, {
                select: [1],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });

        frm.tblDetalle.on("datatable.update", function () {
            frm._$("tblDetalle").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("title", "click para elegir esta categoria").click(function () {
                    const oidCsg = this.querySelectorAll("span").id;
                    const tituloTcp = this.querySelectorAll("span").innerHTML;
                    const accion = {
                        titulo: tituloTcp,
                        oidConsignacion: oidCsg
                    };
                    frm.listarDetalle(oidCsg, accion);
                });
            });
        });

        frm.tblCgHistorico = new simpleDatatables.DataTable("#tblCgHistorico" + frm.oidOpcion, {
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            labels: {
                placeholder: "Buscando registros...",
                noRows: "No hay registros con estos parámetros",
                info: "Mostrando {rows} registros"
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                select: [1],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });

        frm.tblCgHistorico.on("datatable.update", function () {
            frm._$("#tblCgHistorico").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("title", "click para elegir esta categoria").click(function () {
                    const idCat = this.querySelector("span").id;
                    const tituloTpc = this.querySelector("span").innerHTML;
                    const aDatos = {
                        titulo: tituloTpc, oid: idCat
                    };
                    frm.listarTopicos(aDatos);
                });
            });
        });

        frm.tblAbonos = new simpleDatatables.DataTable("#tblAbonos" + frm.oidOpcion, {
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            labels: {
                placeholder: "Buscando registros...",
                noRows: "No hay registros con estos parámetros",
                info: "Mostrando {rows} registros"
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                select: [1],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });

        frm.tblAbonos.on("datatable.update", function () {
            frm._$("#tblAbonos").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("title", "click para elegir esta categoria").click(function () {
                    const idCat = this.querySelector("span").id;
                    const tituloTpc = this.querySelector("span").innerHTML;
                    const aDatos = {
                        titulo: tituloTpc, oid: idCat
                    };
                    frm.listarConsignaciones(aDatos);
                });
            });
        });
    }
};

(function consignaciones () {
    f3.post(_CONSIGNACIONES, {
        funcionphp: "inicializar",
        oidOpcion: oidUltimaOpcion
    }, function (data) {
        const frm = new Consignaciones(data, ultimaUrl);
        frm.adicionalInicializar();
    });
})();











