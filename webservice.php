<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Content-Type: text/html; charset=UTF-8');
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

require_once './config.php';

$conexion = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conexion->connect_error) {
    die('Error de conectando a la base de datos: ' . $conexion->connect_error);
}

$endpointUrl = "http://hub.unisolutions.com.ar/hub/unigis/mapi/soap/gps/service.asmx?wsdl";

$unigisUser = "Sercom";
$unigisPass = "CZW924ncw";
$responseData = array();
$placas = array();
$SqlUpdate = "";
$mensajeUpdate = "";
$devicesCount = 0;
$enableUpdate = true;
$placaTemp = "";
$xmlTracks = "";

$sqlQuery = "SELECT `posicionId`, `vehiculoId`, `velocidad`, `satelites`, `rumbo`, `latitud`, `longitud`, `altitud`, `gpsDateTime`, `statusCode`, `ignition`, `odometro`, `horometro`, `nivelBateria`, `estado` FROM `$dbTable` WHERE `estado`='Nuevo' ORDER BY `vehiculoId`, `posicionId` DESC LIMIT 100;";

$resultado = $conexion->query($sqlQuery);

if ($resultado->num_rows > 0) {

    while ($row = $resultado->fetch_array(MYSQLI_ASSOC)) {

        $rID = utf8_encode($row['posicionId']);
        $dID = utf8_encode($row['vehiculoId']);

        $statusCode     = utf8_encode($row['statusCode']);
        $evento         = 0;
        $sendDateTime     = date("Ymdhis");

        switch ($statusCode) {
            case 61714:    // movimiento
                $evento = 1;
                break;
            case 63553:    // panico
                $evento = 2;
                break;
            case 62476: // motor encendido
                $evento = 3;
                break;
            case 62477: // motor apagado
                $evento = 4;
                break;
            case 61722: // exceso de velocidad
                $evento = 5;
                break;
            case 64787: // energia desconectada
                $evento = 6;
                break;
            case 64789: // energia desconectada
                $evento = 7;
                break;
            default:
                $evento = 0; // Detenido
                break;
        }

        $xmlTracks     .=  "<pEvento>
				<Dominio>" . utf8_encode($row['vehiculoId']) . "</Dominio>
				<NroSerie>-1</NroSerie>
				<Codigo>" . $evento . "</Codigo>
				<Latitud>" . utf8_encode($row['latitud']) . "</Latitud>
				<Longitud>" . utf8_encode($row['longitud']) . "</Longitud>
				<Altitud>" . utf8_encode($row['altitud']) . "</Altitud>
				<Velocidad>" . utf8_encode($row['velocidad']) . "</Velocidad>
				<FechaHoraEvento>" . utf8_encode($row['gpsDateTime']) . "</FechaHoraEvento>
				<FechaHoraRecepcion>" . utf8_encode($row['gpsDateTime']) . "</FechaHoraRecepcion>
			</pEvento>";


        if (strcmp($placaTemp, $dID) != 0) {
            $placaTemp = $dID;
            $placas[] = $dID;
        }

        if ($enableUpdate) {
            $SqlUpdate .= "UPDATE `$dbTable` SET `estado`='Sent' WHERE `posicionId`=$rID AND `vehiculoId`='$dID' LIMIT 1;";
        }

        $devicesCount++;
    }
} else {
    die("No se encontraron unidades sin transmisión dentro del intervalo seleccionado");
}

if ($enableUpdate) {
    if ($conexion->multi_query($SqlUpdate) === TRUE) {
        $mensajeUpdate    = "Registros Insertados!  ";
    } else {
        $mensajeUpdate    = "Error insertando en la tabla " . $conexion->error;
    }
}

mysqli_close($conexion);


$finalXML     =     "<?xml version='1.0' encoding='utf-8'?>";
$finalXML     .=     "<soap:Envelope xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns:soap='http://schemas.xmlsoap.org/soap/envelope/'>";
$finalXML     .=     "<soap:Body>";
$finalXML     .=     "<LoginYInsertarEventos xmlns='http://unisolutions.com.ar/'>";
$finalXML     .=     "<SystemUser>" . $unigisUser . "</SystemUser>";
$finalXML     .=     "<Password>" . $unigisPass . "</Password>";
$finalXML     .=     "<Eventos>";
$finalXML     .=     $xmlTracks;
$finalXML     .=     "</Eventos>";
$finalXML     .=     "</LoginYInsertarEventos>";
$finalXML     .=     "</soap:Body>";
$finalXML     .=     "</soap:Envelope>";

$ch = curl_init($endpointUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "$finalXML");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);

$response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $output);
$xml = new SimpleXMLElement($response);
$body = $xml->xpath('//soapBody')[0];
$array = json_decode(json_encode((array)$body), TRUE);

print_r(json_encode($placas, JSON_PRETTY_PRINT));
print_r(json_encode($array, JSON_PRETTY_PRINT));
