









<?php

session_start();
include("../recursos/php/funcionesSuweb.php");
require_once '../vendor/autoload.php';
require_once '../recursos/php/xlsCreator/xlsCreator.php';
require_once '../recursos/php/xlsCreator/xlsCell.php';
require_once '../recursos/php/UploadFile.php';

$response = array();
$available = testAvailable();
if (!$available[0]) {
    $response['FATAL'] = $available[1];
} else {
    $usuario = $_SESSION['usuario'];
    $mempresa = $_SESSION['empresa'];
    $base = $_SESSION['base'];
    $orgOfi = origenInfo($mempresa, 'OFICINAS');
    $orgTer = origenInfo($mempresa, 'TERCEROS');
    $totaltf = 0;
    $totalav = 0;
    $fechareg = date('Y-m-d');
    $db = conectarse();
}
if (isset($_POST['funcionphp']) && !isset($response['FATAL'])) {
    switch ($_POST['funcionphp']) {
        case 'inicializar':
            $response=[
                'aGeneral' =>[
                'filtrosDatos' => fijarFiltrosFormulario(),
                ],
                'oidOpcion' => $_POST['oidOpcion']];
            break;

        case 'listarConsignaciones':
            if (isset($_POST['datosForm'])) {
                $aForm = json_decode($_POST['datosForm']);
                $tipoListado = (isset($aForm->tipo)) ? $aForm->tipo : '';
                $cadenaSuma = "(C.vrefectivo+C.vrcheque+C.vrtdebito+C.vrtcredito+C.vrtransfer+C.vrvales-C.vrmayor+C.vrmenor)";
                switch ($tipoListado) {
                    case '':
                        $cOperador = '<';
                        $cFechas = '';
                        break;
                    case 'gestion':
                        $cOperador = '<';
                        $cFechas = "AND C.fecha BETWEEN '" . $aForm->fechaini . "' AND '" . $aForm->fechafin . "'";
                        break;
                    default:
                        $cOperador = '>=';
                        $cFechas = "AND C.fecha BETWEEN '" . $aForm->fechaini . "' AND '" . $aForm->fechafin . "'";
                }
                $cTipoDoc = ($aForm->cmbTipoDocFiltro !='elegir')? " AND C.tipocruce=".$aForm->cmbTipoDocFiltro:'';
                if (isset($aForm->optTipoInfo) && $aForm->optTipoInfo == 'CG' && $aForm->botonActual != 'btnGestion') {
                    $cFechas = "AND C.fecha BETWEEN '" . $aForm->fechaini . "' AND '" . $aForm->fechafin . "'";
                }
                $cCuentas = ($aForm->cmbCtaFiltro != 'todas' && $aForm->cmbCtaFiltro !='') ? "AND C.oidcuenta=" . $aForm->cmbCtaFiltro : '';
                if ($aForm->cmbCiudadFiltro != 'todas') {
                    $cCiudad = ($aForm->cmbCiudadFiltro != 'todas') ? " AND Cb.oidciudad=" . $aForm->cmbCiudadFiltro : '';
                    $cOficina = (in_array($aForm->cmbOficinaFiltro, ['','todas']) ===false)? " AND C.oidoficina=" . $aForm->cmbOficinaFiltro : '';
                    $gCiudad = ", Cb.oidciudad";
                    $cTitular="AND T.tercero='".$_SESSION['empresa']."'";
                } else {
                    $cCiudad = '';
                    $cOficina ='';
                    $gCiudad = '';
                    $cTitular= '';
                }
                $cBanco = (isset($aForm->chkBanco) && $aForm->chkBanco != 'TVB') ? "C.banco='" . $aForm->chkBanco . "'" : "C.banco!=''";
                $cRcbd = (isset($aForm->chkRecibido) && $aForm->chkRecibido != 'TRC') ? "C.recibido='" . $aForm->chkRecibido . "'" : "C.recibido!=''";
                if ($cBanco != '' || $cRcbd != '') {
                    $cProp = " AND (" . $cBanco . (($cRcbd != '') ? ' AND ' : '') . $cRcbd . ")";
                }
                $querry = "SELECT C.oid,T.nombretercero,Cb.cuenta,C.fecha,$cadenaSuma as valor ," .
                    "SUM(COALESCE(D.valor,0)),$cadenaSuma-SUM(COALESCE(D.valor,0)) as vrpdte, " .
                    "Td.codigo as codcruce, C.banco, C.recibido, " .
                    "(SELECT ARRAY(SELECT archivo FROM adjuntos WHERE modulo='CONSIGNACIONES' AND oiddoc=C.oid)) as adjuntos " .
                    "FROM consignaciones as C " .
                    "LEFT OUTER JOIN Cuentasbancos as Cb ON(C.oidcuenta=Cb.oid) " .
                    "LEFT OUTER JOIN Terceros as T ON(Cb.oidtitular=T.oid) " .
                    "LEFT OUTER JOIN consignaciondetalles as D ON(D.oidencab=C.oid) " .
                    "LEFT OUTER JOIN Tiposdocumentos as Td ON(C.tipocruce=Td.oid) " .
                    "GROUP BY C.oid,T.nombretercero,Cb.cuenta $gCiudad ,C.fecha,Td.codigo,T.tercero " .
                    "HAVING SUM(COALESCE(D.valor,0))$cOperador $cadenaSuma " .
                    "$cCiudad $cOficina $cTipoDoc $cCuentas $cFechas $cProp $cTitular " .
                    "ORDER BY C.oid,T.nombretercero,C.fecha";
                if ($aForm->salida == 'pantalla') {
                    $datos = $db->GetAll($querry);
                    if ($datos) {
                        $aReg = array();
                        foreach ($datos as $reg) {
                            $aTmp = array(
                                $reg['nombretercero'],
                                $reg['cuenta'],
                                $reg['fecha'],
                                number_format($reg['valor']),
                                number_format($reg['vrpdte']),
                                $reg['codcruce'],
                            );
                            if ($tipoListado != '' && $tipoListado != 'gestion') {
                                $aTmp[] = "<span data-oidreg=" . $reg['oid'] . " class='fas fa-" . (($reg['banco'] == 'N') ? 'ban' : 'check-circle') . "' title='click para cambiar'></span>";
                                $aTmp[] = "<span data-oidreg=" . $reg['oid'] . " class='fas fa-" . (($reg['recibido'] == 'N') ? 'ban' : 'check-circle') . "' title='click para cambiar'></span>";
                            } else {
                                echo $aTmp;
                            }
                            $lista = str_replace(array('{', '}','"'), '', $reg['adjuntos']);
                            $lstArch = '';
                            if ($lista != '') {
                                $aArch = explode(',', $lista);
                                foreach ($aArch as $archivo) {
                                    $lstArch .= "<a href='../informacion/consignaciones/" . $archivo . "' target='_blank'><span class='fas fa-image'></span></a>";
                                }
                            }
                            $aTmp[] = $lstArch;
                            if ($tipoListado == '' || $tipoListado == 'gestion') {
                                $aTmp[] = "<button class='btn btn-warning' onclick='tomarConsignacion(" . $reg['oid'] . ")'>Editar</button>";
                            }
                            if ($tipoListado == 'historico') {
                                $aTmp[] = "<button class='btn btn-info' onclick=\"tomarConsignacion(" . $reg['oid'] . ")\">Consultar</button>";
                            }
                            $aReg[] = $aTmp;
                        }
                        $response['listadoDatos'] = $aReg;
                    }
                } else {
                    $aReporte['query'] = "SELECT C.oid,T.nombretercero,Cb.cuenta," .
                        "C.fecha,(C.vrefectivo+C.vrcheque+C.vrtdebito+C.vrtcredito+C.vrtransfer+C.vrvales-C.vrmayor+C.vrmenor) as valor ," .
                        "Td.codigo as codcruce, C.banco, C.recibido, Tc.nombretercero as ntercruce, " .
                        "O.nombreofic, Cc.fecha as fechacr, D.valor as valorcr " .
                        "FROM consignaciones as C " .
                        "LEFT OUTER JOIN Cuentasbancos as Cb ON(C.oidcuenta=Cb.oid) " .
                        "LEFT OUTER JOIN Terceros as T ON(Cb.oidtitular=T.oid) " .
                        "LEFT OUTER JOIN consignaciondetalles as D ON(D.oidencab=C.oid) " .
                        "LEFT OUTER JOIN Tiposdocumentos as Td ON(C.tipocruce=Td.oid) " .
                        "LEFT OUTER JOIN Ctasxcruzar as Cc ON(D.oiddocumento=Cc.oid) " .
                        "LEFT OUTER JOIN Terceros as Tc ON(Cc.tercero=Tc.oid) " .
                        "LEFT OUTER JOIN Oficinas as O ON(Cc.oidoficina=O.oid) " .
                        "WHERE 1=1 $cCiudad $cOficina $cTipoDoc $cFechas $cProp " .
                        "ORDER BY C.oid,T.nombretercero,C.fecha, Tc.nombretercero, O.nombreofic";
                    $aReporte['titulo'] = "REPORTE DE CONSIGNACIONES";
                    $aReporte['columnas'] = array(
                        'titulos' => array('TITULAR DE CUENTA', 'CUENTA', 'FECHA CONSIGNACION', 'VALOR', 'TIPO DOC', 'V.BANCO', 'RECIBIDO', 'TERCERO CRUCE', 'OFICINA CRUCE', 'FECHA DOCUMENTO', 'VALOR DOCUMENTO'),
                        'valores' => array('nombretercero', 'cuenta', 'fecha', 'valor', 'codcruce', 'banco', 'recibido', 'ntercruce', 'nombreofic', 'fechacr', 'valorcr'),
                        'tipos' => array('texto', 'texto', 'fecha', 'numero', 'texto', 'texto', 'texto', 'texto', 'texto', 'fecha', 'numero')
                    );
                    $_SESSION['rptConsignaciones'] = json_encode($aReporte);
                    $response['nombreSession'] = 'rptConsignaciones';
                }
            }
            break;

        case 'tomarConsignacion':
            if (isset($_POST['oidRegistro'])) {
                $querry = "SELECT C.oidcuenta,C.fecha,C.vrefectivo,C.vrcheque," .
                    "C.vrtdebito, C.vrtcredito,C.vrtransfer,C.vrvales,C.estado," .
                    "C.nota,C.tipo,C.descripcion,C.tipocruce, Cb.oidciudad, " .
                    "C.vrmayor, C.vrmenor, C.oidoficina, C.oidtercero, T.tercero, T.nombretercero ".
                    "FROM consignaciones as C " .
                    "INNER JOIN Cuentasbancos as Cb ON(C.oidcuenta=Cb.oid) " .
                    "LEFT OUTER JOIN Terceros as T ON (C.oidtercero=T.oid) ".
                    "WHERE C.oid=" . $_POST['oidRegistro'];
                $datos = $db->GetRow($querry);
                if ($datos) {
                    $response['datosForm'] = array(
                        'cmbCuentas' => $datos['oidcuenta'],
                        'fechaConsignacion' => $datos['fecha'],
                        'txtVrEfectivo' => number_format($datos['vrefectivo']),
                        'txtVrCheque' => number_format($datos['vrcheque']),
                        'txtVrTdebito' => number_format($datos['vrtdebito']),
                        'txtVrTcredito' => number_format($datos['vrtcredito']),
                        'txtVrTransfer' => number_format($datos['vrtransfer']),
                        'txtDescripcion' => $datos['descripcion'],
                        'txtVrVales' => number_format($datos['vrvales']),
                        'txtNota' => $datos['nota'],
                        'txtMayorValor' => number_format($datos['vrmayor']),
                        'txtMenorValor' => number_format($datos['vrmenor']),
                        'cmbTipoDocumento' => $datos['tipocruce'],
                        'E_ciudad' => $datos['oidciudad'],
                        'E_oidoficina' => $datos['oidoficina'],
                        'oidTercero' => $datos['oidtercero'],
                        'txtTercero' => $datos['tercero'],
                        'dshNtercasoc' => $datos['nombretercero']
                    );
                }
            }
            break;

        case 'listarAdjuntos':
            if (isset($_POST['oidRegistro'])) {
                $querry = "SELECT oid,archivo FROM adjuntos WHERE modulo='CONSIGNACIONES' AND oiddoc=" . $_POST['oidRegistro'];
                $datos = $db->GetAll($querry);
                $fotos = array();
                if ($datos) {
                    foreach ($datos as $reg) {
                        $file = '../informacion/consignaciones/' . $reg['archivo'];
                        if (file_exists($file)) {
                            $fotos[] = [
                                "oidAdjunto" => $reg['oid'],
                                "nombre" => $reg['archivo'],
                                "archivo" => $file
                            ];
                        }
                    }
                }
                $response['lstArchivos'] = $fotos;
            }
            break;

        case 'borrarAdjuntos':
            if (isset($_POST['oidAdjunto'])) {
                $cmdSQL = "DELETE FROM adjuntos WHERE oid=" . $_POST['oidAdjunto'] . " RETURNING archivo";
                $mensaje = '';
                $msgError = '';
                $db->StartTrans();
                $borrado = $db->Execute($cmdSQL);
                if ($db->ErrorMsg() != '') {
                    $msgError .= $db->ErrorMsg() . "\n";
                }
                if ($db->HasFailedTrans()) {
                    $mensaje = "No se pudo borrar el archivo adjunto";
                }
                $db->CompleteTrans();
                if ($msgError != '') {
                    $mensaje .= $msgError . "\n";
                    $response['msgError'] = $msgError;
                } else {
                    $docroot = $_SERVER['DOCUMENT_ROOT'];
                    $ruta = $docroot . '/informacion/consignaciones/';
                    $aDir = array('', 'x200/', 'x32/');
                    foreach ($aDir as $directorio) {
                        if (file_exists($ruta . $directorio . $borrado->fields[0])) {
                            unlink($ruta . $directorio . $borrado->fields[0]);
                        }
                    }
                }
                if ($mensaje != '') {
                    $response['mensaje'] = $mensaje;
                }
            }
            break;

        case 'grabarConsignacion':
            if (isset($_POST['datosForm'])) {
                // tomar valores de formulario
                $aForm = json_decode($_POST['datosForm'], true);
                $regCsg = array(
                    'oidcuenta' => $aForm['cmbCuentas'],
                    'fecha' => $aForm['fechaConsignacion'],
                    'oidoficina'=>$aForm['E_oidoficina'],
                    'oidtercero'=>($aForm['oidTercero']>0)? $aForm['oidTercero']:null,
                    'tipocruce' => $aForm['cmbTipoDocumento'],
                    'vrefectivo' => $aForm['txtVrEfectivo'],
                    'vrcheque' => $aForm['txtVrCheque'],
                    'vrtdebito' => $aForm['txtVrTdebito'],
                    'vrtcredito' => $aForm['txtVrTcredito'],
                    'vrtransfer' => $aForm['txtVrTransfer'],
                    'descripcion' => $aForm['txtDescripcion'],
                    'vrvales' => $aForm['txtVrVales'],
                    'vrmayor'=>$aForm['txtMayorValor'],
                    'vrmenor'=>$aForm['txtMenorValor'],
                    'estado' => 0,
                    'nota' => $aForm['txtNota'],
                    'usuario' => $usuario,
                );
                $querry = "SELECT * FROM consignaciones WHERE oid=" . $aForm['oidTbl'];
                $datCsg = $db->Execute($querry);
                if ($aForm['oidTbl'] == 0) {
                    $cmdSQL = $db->GetInsertSQL($datCsg, $regCsg) . " RETURNING oid";
                } else {
                    $cmdSQL = $db->GetUpdateSQL($datCsg, $regCsg);
                }
                $falla = "NO";
                $msgError = "";
                $mensaje = "";
                $db->StartTrans();
                $consec = $db->Execute($cmdSQL);
                $msgError .= (strlen($db->ErrorMsg()) > 0) ? $db->ErrorMsg() . "<br/>" : '';
                if ($db->HasFailedTrans()) {
                    $mensaje .= "No se pudo grabar la consignación </br>";
                }
                $db->CompleteTrans();
                if ($msgError != '') {
                    $mensaje .= $msgError . "\n";
                    $response['msgError'] = $msgError;
                } else {
                    $oidCsg = ($aForm['oidTbl'] > 0) ? $aForm['oidTbl'] : $consec->fields[0];
                    if ($oidCsg) {
                        $response['oidEncabezado'] = $oidCsg;
                        if (!empty($_FILES)) {
                            $filesCtrl = new \PHP\FilesController($oidCsg, [
                                "db" => $db, "modulo" => "CONSIGNACIONES",
                                "repetido" => true, "ruta_archivo" => "consignaciones",
                                "conservar_nombres" => true
                            ]);
                            $filesResponse = $filesCtrl->uploadFiles($_FILES);
                            if ($filesResponse->status != 200) {
                                if ($filesResponse->status == 201) {
                                    $db->Execute($filesResponse->msg);
                                } else {
                                    $response['msgError'] = $filesResponse->msg;
                                }
                            }
                        }
                    } else {
                        if (strlen($db->ErrorMsg()) > 0) {
                            $response['msgError'] = $db->ErrorMsg();
                        }
                    }
                }
                $response['mensaje'] = $mensaje;
            }
            break;

        case 'listarCxP':
            if (isset($_POST['datosForm'])) {
                $aForm = json_decode($_POST['datosForm']);
                $cTercero = (isset($aForm->terceroDoc) && $aForm->terceroDoc!='') ? " AND D.tercero='" . $aForm->terceroDoc . "'" : '';
                $cCiudad = (isset($aForm->cmbCiudadFiltro) && $aForm->cmbCiudadFiltro != 'todas') ? "AND O.oidciudad=" . $aForm->cmbCiudadFiltro : '';
                $cTipoDocumento = (isset($aForm->tipoDocumento) && $aForm->tipoDocumento != '') ? " AND C.tipodocumento=" . $aForm->tipoDocumento : '';
                $cFecha = ($aForm->botonActual == 'btnGestion' && !isset($aForm->sinFechas)) ? " AND C.fecha BETWEEN '" . $aForm->fechaini . "' AND '" . $aForm->fechafin . "'" : '';
                $cOficina =(isset($aForm->cmbOficinaFiltro) && $aForm->cmbOficinaFiltro != 'todas') ? "AND C.oidoficina=" . $aForm->cmbOficinaFiltro : '';
                if ($aForm->botonActual != 'btnHistorico') {
                    $querry = "SELECT C.oid,D.tercero,D.nombretercero,Td.codigo,C.nrodocumento," .
                        "O.nombreofic,C.fecha,C.valor,C.valorneto,Tq.tcpago " .
                        "FROM ctasxcruzar as C " .
                        "LEFT OUTER JOIN Terceros as D ON(C.tercero=D.oid) " .
                        "LEFT OUTER JOIN Oficinas as O ON(C.oidoficina=O.oid) " .
                        "LEFT OUTER JOIN Tiposdocumentos as Td ON(C.tipodocumento=Td.oid) " .
                        "LEFT OUTER JOIN Tiquetes as Tq ON(C.nrodocumento=Tq.tiquete) " .
                        "WHERE C.valorneto>0 $cCiudad $cOficina $cTercero $cTipoDocumento $cFecha";
                    if ($aForm->salida == 'pantalla') {
                        $datos = $db->GetAll($querry);
                        if ($datos) {
                            $aReg = array();
                            foreach ($datos as $reg) {
                                $aReg[] = array(
                                    $reg['nombretercero'],
                                    $reg['codigo'],
                                    $reg['nrodocumento'],
                                    $reg['nombreofic'],
                                    $reg['fecha'],
                                    number_format($reg['valor']),
                                    number_format($reg['valorneto']),
                                    (!isset($aForm->tipo) || $aForm->botonActual == 'btnGestion') ? "<input id='det" . $reg['oid'] . "' type='text' class='form-control inputnro' size='10' maxlength='10' onfocus='tomarPdte(this)' >" : ''
                                );
                            }
                            // ultima columna se quito esto al final onchange='cambiarPdte(this)'
                        }
                    } else {
                        $aReporte['query'] = $querry . ' ORDER BY D.nombretercero,Td.codigo,C.fecha';
                        $aReporte['titulo'] = "REPORTE DE DOCUMENTOS POR CRUZAR";
                        $aReporte['columnas'] = array(
                            'titulos' => array('NOMBRE DE TERCERO', 'CODIGO TERCERO', 'TIPO DOC', 'DOCUMENTO', 'OFICINA', 'FECHA', 'VALOR INICIAL', 'VALOR PDTE', 'TARJETA USADA'),
                            'valores' => array('nombretercero', 'tercero', 'codigo', 'nrodocumento', 'nombreofic', 'fecha', 'valor', 'valorneto', 'tcpago'),
                            'tipos' => array('texto', 'texto', 'texto', 'texto', 'texto', 'fecha', 'numero', 'numero', 'texto')
                        );
                    }
                } else {
                    $querry = "SELECT C.fecha,Cb.cuenta,Tb.nombretercero banco," .
                        "Td.codigo,Tcc.nombretercero tercerocc,O.nombredpr,D.valor " .
                        "FROM consignaciondetalles as D " .
                        "INNER JOIN consignaciones as C ON(D.oidencab=C.oid) " .
                        "INNER JOIN ctasxcruzar as Cc ON(D.oiddocumento=Cc.oid) " .
                        "INNER JOIN cuentasbancos as Cb ON(C.oidcuenta=Cb.oid) " .
                        "INNER JOIN terceros as Tb ON(Cb.oidbanco=Tb.oid) " .
                        "INNER JOIN tiposdocumentos as Td ON(Cc.tipodocumento=Td.oid) " .
                        "INNER JOIN terceros as Tcc ON(Cc.tercero=Tcc.oid) " .
                        "INNER JOIN oficinas as O ON(Cc.oidoficina=O.oid) " .
                        "WHERE C.fecha BETWEEN '" . $aForm->fechaini . "' AND '" . $aForm->fechafin . "' $cCiudad ";
                    if ($aForm->salida == 'pantalla') {
                        $datos = $db->GetAll($querry);
                        if ($datos) {
                            $aReg = array();
                            foreach ($datos as $reg) {
                                $aReg[] = array(
                                    $reg['fecha'],
                                    $reg['cuenta'],
                                    $reg['banco'],
                                    $reg['codigo'],
                                    $reg['tercerocc'],
                                    $reg['nombredpr'],
                                    $reg['valor']
                                );
                            }
                        }
                    } else {
                        $aReporte['query'] = $querry . ' ORDER BY C.fecha,Tb.nombretercero,Tcc.nombretercero,O.nombredpr';
                        $aReporte['titulo'] = "REPORTE DE ABONOS";
                        $aReporte['columnas'] = array(
                            'titulos' => array('FECHA CRUCE', 'CUENTA', 'BANCO', 'TIPO DOC', 'TERCERO DOCUMENTO', 'OFICINA', 'VALOR CRUCE'),
                            'valores' => array('fecha', 'cuenta', 'banco', 'codigo', 'tercerocc', 'nombredpr', 'valor'),
                            'tipos' => array('fecha', 'texto', 'texto', 'texto', 'texto', 'texto', 'numero')
                        );
                    }
                }
                if ($aForm->salida == 'pantalla') {
                    $response['listaCxP'] = (isset($aReg))? $aReg:[];
                } else {
                    $response['n']=$querry;
                    $_SESSION['rptCxP'] = json_encode($aReporte);
                    $response['nombreSession'] = 'rptCxP';
                }
            }
            break;

        case 'listarDetalle':
            if (isset($_POST['oidCsg'])) {
                $querry = "SELECT D.oid,T.nombretercero,C.fecha,O.nombreofic," .
                    "C.valor,D.valor as vrcruce " .
                    "FROM consignaciondetalles as D " .
                    "LEFT OUTER JOIN Ctasxcruzar as C ON(D.oiddocumento=C.oid) " .
                    "LEFT OUTER JOIN Terceros as T ON(C.tercero=T.oid) " .
                    "LEFT OUTER JOIN Oficinas as O ON(C.oidoficina=O.oid) " .
                    "WHERE D.oidencab=" . $_POST['oidCsg'];
                $datos = $db->GetAll($querry);
                $vrCruce = 0;
                if ($datos) {
                    $aReg = array();
                    foreach ($datos as $reg) {
                        $vrCruce += $reg['vrcruce'];
                        $aReg[] = array(
                            $reg['nombretercero'],
                            $reg['fecha'],
                            $reg['nombreofic'],
                            number_format($reg['valor']),
                            number_format($reg['vrcruce']),
                            ($_POST['accion'] == 'gestion') ? "<button id='btn" . $reg['oid'] . "' type='button' class='btn btn-danger' onclick='ConfirmBorraItem(this)'>Borrar ítem</button>" : ''
                        );
                    }
                    $response['lstRegistros'] = $aReg;
                }
                $response['vrCruce'] = $vrCruce;
            }
            break;

        case 'grabarDetalle':
            if (isset($_POST['oidCsg'], $_POST['aItems'])) {
                $aItems = json_decode($_POST['aItems'], true);
                $querry = "SELECT * FROM consignaciondetalles WHERE oid=0";
                $datDetalle = $db->Execute($querry);
                $msgError = '';
                $mensaje = '';
                $aRealizados = array();
                foreach ($aItems as $item => $valor) {
                    $oidDoc = str_replace('det', '', $item);
                    if ($oidDoc > 0) {
                        $regDet = array(
                            'oidencab' => $_POST['oidCsg'],
                            'oiddocumento' => $oidDoc,
                            'valor' => $valor
                        );
                        $cmdSQL = $db->GetInsertSQL($datDetalle, $regDet);
                        $cmdSQL2 = "UPDATE ctasxcruzar SET valorneto=valorneto-$valor WHERE oid=$oidDoc";
                        $db->StartTrans();
                        $db->Execute($cmdSQL);
                        $msgError .= ($db->ErrorMsg() != '') ? $db->ErrorMsg() . "\n" : '';
                        $db->Execute($cmdSQL2);
                        $msgError .= ($db->ErrorMsg() != '') ? $db->ErrorMsg() . "\n" : '';
                        $db->Execute(generarAccion([
                            'modulo' => 'CONTABILIDAD',
                            'accion' => 'CRUZAR CXP',
                            'documento' => $oidDoc
                        ]));
                        if ($db->HasFailedTrans()) {
                            $mensaje .= "No se pudo grabar el detalle de la consignación </br>";
                        } else {
                            $aRealizados[] = $oidDoc;
                        }
                        $db->CompleteTrans();
                    }
                }
                if ($msgError != '') {
                    $mensaje .= $msgError;
                    $response['msgError'] = $msgError;
                }
                $response['aRealizados'] = $aRealizados;
            }
            break;

        case 'borrarDetalle':
            if (isset($_POST['idBoton'])) {
                $oidRegistro = str_replace('btn', '', $_POST['idBoton']);
                $msgError = '';
                $mensaje = '';
                $db->StartTrans();
                $datValor = $db->Execute("DELETE FROM consignaciondetalles " .
                    "WHERE oid=$oidRegistro RETURNING valor");
                $msgError .= ($db->ErrorMsg() != '') ? $db->ErrorMsg() . "\n" : '';
                $db->Execute(generarAccion([
                    'modulo' => 'CONTABILIDAD',
                    'accion' => 'BORRAR ITEM CONSIGNACION',
                    'documento' => $oidRegistro
                ]));
                if ($db->HasFailedTrans()) {
                    $mensaje .= "No se pudo borrar el ítem de la consignación </br>";
                }
                $db->CompleteTrans();
                if ($msgError == '') {
                    $response['valorItem'] = $datValor->fields[0];
                } else {
                    $mensaje .= $msgError;
                    $response['msgError'] = $msgError;
                }
                if ($mensaje != '') {
                    $response['mensaje'] = $mensaje;
                }
            }
            break;

        case 'cambiarPropiedad':
            if (isset($_POST['datosForm'])) {
                $aForm = json_decode($_POST['datosForm'], true);
                $cmdSQL = "UPDATE consignaciones SET " . $aForm['campo'] . "='" . $aForm['estado'] .
                    "' WHERE oid=" . $aForm['oidReg'];
                $msgError = '';
                $mensaje = '';
                $db->StartTrans();
                $db->Execute($cmdSQL);
                $msgError .= ($db->ErrorMsg() != '') ? $db->ErrorMsg() . "\n" : '';
                $db->Execute(generarAccion([
                    'modulo' => 'CONTABILIDAD',
                    'accion' => 'CAMBIAR PROPIEDAD CONSIGNACION',
                    'cmdsql' => $cmdSQL
                ]));
                if ($db->HasFailedTrans()) {
                    $mensaje .= "No se pudo cambiar el valor del campo </br>";
                }
                $db->CompleteTrans();
                if ($msgError != '') {
                    $mensaje .= $msgError;
                    $response['msgError'] = $msgError;
                }
                if ($mensaje != '') {
                    $response['mensaje'] = $mensaje;
                }
            }
            break;

        case 'anulaReg':
            $oidencab = (isset($_POST['idEncab'])) ? $_POST['idEncab'] : '';
            $tpconsig = (isset($_POST['idTipo'])) ? $_POST['idTipo'] : '';
            $msgError = "";
            $mensaje = "";
            $falla = "NO";
            $guiasanula = null;
            if ($tpconsig == 'MV') {
                $i = 0;
                $cmdSQL = "SELECT numeroguia FROM consigcrr WHERE oidencab=$oidencab";
                $datos = $db->GetAll($cmdSQL);
                if ($datos) {
                    foreach ($datos as $reg) {
                        $i++;
                        $guiasanula = $guiasanula . "" . $reg['numeroguia'] . "|";
                        $querry = "UPDATE guiasmoviles SET estado=0 WHERE numero='" . $reg['numeroguia'] . "'";
                        $db->StartTrans();
                        $db->Execute($querry);
                        if ($db->HasFailedTrans()) {
                            $mensaje .= "El proceso ha fallado para las guias de la consignacion \n";
                            $falla = "SI";
                        }
                        $db->CompleteTrans();
                    }
                }
            }
            if ($falla == 'NO') {
                $cmdSQL = "SELECT oidvlrdifer  FROM consigcrr WHERE oidencab=$oidencab order by oid";
                $datos = $db->GetAll($cmdSQL);
                if ($datos) {
                    foreach ($datos as $reg) {
                        if (filter_var($reg['oidvlrdifer'], FILTER_VALIDATE_INT) !== false) {
                            $querry = "UPDATE consigcrr SET estado=0 WHERE oid=" . $reg['oidvlrdifer'];
                            $db->Execute($querry);
                        } else {
                            $msgerror .="No se pudo cambiar el estado \n";
                        }
                    }
                }
                $cmdSQL = "DELETE FROM consigcrr WHERE oidencab=$oidencab";
                $db->StartTrans();
                $db->Execute($cmdSQL);
                if ($db->HasFailedTrans()) {
                    $mensaje .= "El proceso ha fallado para los Item de la consignacion \n";
                    $falla = "SI";
                }
                $db->CompleteTrans();
            }
            if ($falla == 'NO') {
                $db->StartTrans();
                $db->Execute($cmdSQL);
                if ($db->ErrorMsg() != '') {
                    $msgError .= $db->ErrorMsg() . "\n";
                }
                $db->Execute(generarAccion([
                    "modulo" => "CARTERA",
                    "accion" => 'BORRA DOCUMENTO CONTROL CONSIGNACIONES',
                    'documento' => $oidencab,
                    'cmdsql' => $cmdSQL
                ]));
                if ($db->ErrorMsg() != '') {
                    $msgError .= $db->ErrorMsg() . "\n";
                }
                if ($db->HasFailedTrans()) {
                    $mensaje .= "El proceso ha fallado para la consignacion \n";
                    $falla = "SI";
                }
                $db->CompleteTrans();
                if ($tpconsig == 'MV') {
                    $mensaje .= "Se anulo el documento de la congignación movil $oidencab con $i guia(s). $cmdSQL \n";
                }
            }
            if ($msgError != '') {
                $mensaje .= $msgError . "\n";
            }
            if ($mensaje != '') {
                $response['mensaje'] = $mensaje;
            }
            break;

        case 'BorraItem':
            $oidencab = (isset($_POST['idEncab'])) ? $_POST['idEncab'] : '';
            $moid = (isset($_POST['idMoid'])) ? $_POST['idMoid'] : '';
            $oiddifer = (isset($_POST['idDifer'])) ? $_POST['idDifer'] : '';
            $fecha = time();
            $falla = "NO";
            $msgError = "";
            $mensaje = "";
            $falla = "NO";
            $db->StartTrans();
            $db->Execute($cmdSQL);
            if ($db->ErrorMsg() != '') {
                $msgError .= $db->ErrorMsg() . "\n";
            }
            $db->Execute(generarAccion([
                "modulo" => "CARTERA",
                "accion" => 'BORRA ITEM CONTROL CONSIGNACIONES',
                'documento' => $moid,
                'cmdsql' => $cmdSQL
            ]));
            if ($db->ErrorMsg() != '') {
                $msgError .= $db->ErrorMsg() . "\n";
            }
            if ($db->HasFailedTrans()) {
                $response['mensaje'] = "El proceso ha fallado para los Item de la consignación";
                $falla = "SI";
            }
            $db->CompleteTrans();
            if ($falla == 'NO' && $oiddifer != 0) {
                $querry = "UPDATE consigcrr SET estado=0 WHERE oid=$oiddifer";
                $datos = $db->Execute($querry);
            }
            break;

        case 'armarReporte':
            if (isset($_POST['datosForm'])) {
                $tipo = (isset($_POST['idTipo'])) ? $_POST['idTipo'] : '';
                $aForm = json_decode($_POST['datosForm']);
                $fini = $aForm->fechaini;
                $ffin = $aForm->fechafin;
                $morg = $aForm->E_ciudad;
                $titulo = 'CONTROL CONSIGNACIONES';
                $aEncabezados = array(
                    'SEMANA DEL ' => $aForm->fechaini . ' AL ' . $aForm->fechafin,
                    'CIUDAD:' => $aForm->E_ciudad
                );
                $mdfini = "MD" . str_replace('-', '', $fini);
                $mdffin = "MD" . str_replace('-', '', $ffin);
                $aFilas = array();
                $aFilas[] = array();
                $totFilas = 5;
                /* MUESTRA CADA CONSIGNACION INGRESADA Y SU TOTAL */
                $querry1 = "SELECT distinct oidencab, descrip, e.vlrconsig, vlrvales " .
                    "FROM consigcrr as C " .
                    "LEFT OUTER JOIN oficinas AS F ON(F.oid=C.oidoficina) " .
                    "INNER JOIN Ciudadescorreo as Cc ON(F.oidciudad=Cc.oid) " .
                    "LEFT OUTER JOIN encabconsignacrr AS E ON(E.oid=C.oidencab) " .
                    "WHERE md BETWEEN '$mdfini' AND '$mdffin' AND Cc.ciudad='$morg' and e.tipo='GLOBAL' " .
                    "ORDER BY oidencab";
                $datosCg = $db->GetAll($querry1);
                $totalConsig = 0;
                if ($datosCg) {
                    $filaIni = $totFilas;
                    foreach ($datosCg as $regCg) {
                        $aFilas[] = array(
                            new Cell($regCg['descrip']),
                            new Cell($regCg['vlrconsig'], 'numero')
                        );
                        $totalConsig += $regCg['vlrconsig'];
                        $totFilas++;
                    }
                    $aFilas[] = array(
                        new Cell('TOTAL CONSIGNACIONES', 'texto', 'labelSubtotal'),
                        new Cell("=sum(B$filaIni:B" . ($totFilas - 1) . ")", 'formula', 'subtotales')
                    );
                    $totFilas++;
                }
                $aFilas[] = array();
                $totFilas++;
                $filaIni = $totFilas;
                /* MUESTRA LOS VALORES QUE SE CONSIGNARON AL AGENTE POR PUNTO DE VENTA */
                $querry2 = "SELECT distinct nombredpr, sum(vlrreal) as tvlrreal,C.oidoficina " .
                    "FROM consigcrr as C " .
                    "LEFT OUTER JOIN oficinas AS F ON(F.oid=C.oidoficina) " .
                    "INNER JOIN Ciudadescorreo as Cc ON(F.oidciudad=Cc.oid) " .
                    "LEFT OUTER JOIN encabconsignacrr AS E ON(E.oid=C.oidencab) " .
                    "WHERE md BETWEEN '$mdfini' AND '$mdffin' AND Cc.ciudad='$morg' AND " .
                    "e.tipo='GLOBAL' AND emp='TF' AND oidvlrdifer IS NULL " .
                    "GROUP BY 1,3 ORDER BY nombredpr";
                $datosDev = $db->GetAll($querry2);
                $totalDevo = 0;
                if ($datosDev) {
                    foreach ($datosDev as $regDev) {
                        $aFilas[] = array(
                            new Cell('Devuelve a Trfit venta de ' . $regDev['nombredpr']),
                            new Cell($regDev['tvlrreal'], 'numero')
                        );
                        $totalDevo += $regDev['tvlrreal'];
                        $totFilas++;
                    }
                }
                $aFilas[] = array();
                $totFilas++;
                /* MUESTRA LOS PENDIENTES O SOBRANTES EXISTENTES */
                $querry3 = "SELECT distinct nombredpr, emp, sum(c.vlrconsig)," .
                    "sum(vlrdifer) as vrdifer,tipodifer " .
                    "FROM consigcrr as C " .
                    "LEFT OUTER JOIN oficinas AS F ON(F.oid=C.oidoficina) " .
                    "INNER JOIN Ciudadescorreo as Cc ON(F.oidciudad=Cc.oid) " .
                    "LEFT OUTER JOIN encabconsignacrr AS E ON(E.oid=C.oidencab) " .
                    "WHERE md BETWEEN '$mdfini' AND '$mdffin' AND " .
                    "Cc.ciudad='$morg' and e.tipo='GLOBAL' and c.vlrdifer<>0 and c.estado=0 " .
                    "GROUP BY 1,2,5 ORDER BY nombredpr";
                $datPdsb = $db->GetAll($querry3);
                if ($datPdsb) {
                    foreach ($datPdsb as $regPdsb) {
                        $print = '';
                        if ($regPdsb['tipodifer'] == 'PEN') {
                            $print = 'PENDIENTE ';
                            $valor = $regPdsb['vrdifer'] * (-1);
                        }
                        if ($regPdsb['tipodifer'] == 'SOB') {
                            $print = 'SOBRANTE ';
                            $valor = $regPdsb['vrdifer'];
                        }
                        $aFilas[] = array(
                            new Cell($print . $regPdsb['nombredpr']),
                            new Cell($valor, 'numero')
                        );
                        $totalDevo += $valor;
                        $totFilas++;
                    }
                }
                /* MUESTRA VALOR CORRESPONDIENTE AL AGENTE */
                $querry4 = "SELECT distinct oidencab, nota, vlrvales " .
                    "FROM consigcrr as C " .
                    "LEFT OUTER JOIN oficinas AS O ON(O.oid=C.oidoficina) " .
                    "INNER JOIN Ciudadescorreo as Cc ON(O.oidciudad=Cc.oid) " .
                    "LEFT OUTER JOIN encabconsignacrr AS E ON(E.oid=C.oidencab) " .
                    "WHERE md BETWEEN '$mdfini' AND '$mdffin' AND " .
                    "Cc.ciudad='$morg' and e.tipo='GLOBAL' and vlrvales<>0 " .
                    "ORDER BY oidencab";
                $datVales = $db->GetAll($querry4);
                if ($datVales) {
                    foreach ($datVales as $regVale) {
                        $aFilas[] = array(
                            new Cell('VALE ' . $regVale['nota']),
                            new Cell($regVale['vlrvales'] * (-1))
                        );
                        $totalDevo += $regVale['vlrvales'] * (-1);
                        $totFilas++;
                    }
                }
                /* MUESTRA LA DIFERENCIA QUE EXISTE ENTRE LOS VALORES CONSIGNADOS, TOTAL AV Y TOTAL AGENTE */
                $aFilas[] = array(
                    new Cell('CUADRE DIFERENCIA', 'texto', 'labelSubtotal')
                );
                $totFilas++;
                $aFilas[] = array(
                    new Cell('DEVOLUCION TRIFIT', 'texto', 'labelSubtotal'),
                    new Cell("=sum(B$filaIni:B" . ($totFilas - 1) . ")", 'formula', 'subtotales')
                );
                $filatTF = count($aFilas);
                $totFilas++;
                /* MUESTRA VALOR CORRESPONDIENTE A AVIANCA */
                /* MUESTRA LOS VALORES CORRESPONDIENTES A AV POR PUNTO DE VENTA */
                $querry5 = "SELECT distinct nombredpr, sum(c.vlrconsig) as vrcg " .
                    "FROM consigcrr as C " .
                    "LEFT OUTER JOIN oficinas AS F ON(F.oid=C.oidoficina) " .
                    "INNER JOIN Ciudadescorreo as Cc ON(F.oidciudad=Cc.oid) " .
                    "LEFT OUTER JOIN encabconsignacrr AS E ON(E.oid=C.oidencab) " .
                    "WHERE md BETWEEN '$mdfini' AND '$mdffin' AND " .
                    "Cc.ciudad='$morg' AND e.tipo='GLOBAL' AND emp='AV' " .
                    "GROUP BY nombredpr ORDER BY nombredpr";
                $datAV = $db->GetAll($querry5);
                $totReg = count($datAV);
                $aFilas[] = array(
                    new Cell('VALOR TOTAL A DEVOLVER A AVIANCA', 'texto', 'labelSubtotal'),
                    new Cell("=sum(B" . ($totFilas + 2) . ":B" . ($totFilas + $totReg + 1) . ")", 'formula', 'subtotales')
                );
                $totFilas++;
                $aFilas[] = array();
                $totFilas++;
                $totalav = 0;
                if ($datAV) {
                    foreach ($datAV as $regAV) {
                        $aFilas[] = array(
                            new Cell('Avianca ' . $regAV['nombredpr']),
                            new Cell($regAV['vrcg'], 'numero')
                        );
                        $totalav = $regAV['vrcg'];
                    }
                }
                $xlsCreator = new XlsCreator($titulo, array(), $aFilas, $aEncabezados);
                $response['xls'] = $xlsCreator->printXls();
            }
            break;
        default:
            $response['msgError'] = "Error en nombre de proceso a ejecutar";
    }
} else {
    $response['msgError'] = "Petición inválida";
}
echo json_encode($response);

function fijarFiltrosFormulario()
{
    return json_encode([
        'cmbCuentas' => [
            'tipo' => 'combo',
            'requerido' => 'requerido',
            'noacepta' => ['elegir']
        ],
        'cmbTipoDocumento' => [
            'tipo' => 'combo',
            'requerido' => 'requerido',
            'noacepta' => ['elegir']
        ],
        'fechaConsignacion' => [
            'tipo' => 'texto',
            'requerido' => 'requerido',
            'longmax' => 10
        ],
        'txtVrEfectivo' => [
            'tipo' => 'numero',
            'requerido' => 'requerido',
            'minimo' => 0
        ],
        'txtVrCheque' => [
            'tipo' => 'numero',
            'requerido' => 'requerido',
            'minimo' => 0
        ],
        'txtVrTdebito' => [
            'tipo' => 'numero',
            'requerido' => 'requerido',
            'minimo' => 0
        ],
        'txtVrTcredito' => [
            'tipo' => 'numero',
            'requerido' => 'requerido',
            'minimo' => 0
        ],
        'txtVrTransfer' => [
            'tipo' => 'numero',
            'requerido' => 'requerido',
            'minimo' => 0
        ],
        'txtVrVales' => ['tipo' => 'numero'],
        'txtMayorValor'=>['tipo' => 'numero'],
        'txtMenorValor'=>['tipo' => 'numero'],
        'txtDescripcion' => [
            'tipo' => 'texto',
            'requerido' => 'requerido',
            'longmax' => 80
        ]
        ]);
}