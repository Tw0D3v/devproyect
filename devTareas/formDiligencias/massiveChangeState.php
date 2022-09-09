
<?php

session_start();
include("../recursos/php/funcionesSuweb.php");
require_once '../vendor/autoload.php';
require_once '../recursos/php/xlsCreator/xlsCreator.php';
require_once '../recursos/php/xlsCreator/xlsCell.php';

$response = array();
$available = testAvailable();
if (!$available[0]) {
    $response['FATAL'] = $available[1];
} else {
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
                
            case 'listarDetalles':
            if(isset($_POST['datosForm'])){
                $aForm = json_encode($_POST['datosForm']);
                $querry = "SELECT id ,estado,diligencia,mesnsajero,elegir" 
            }
    }

}