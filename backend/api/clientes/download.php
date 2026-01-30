<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/Cliente.php';
require_once '../../utils/JWT.php';

$jwt = new JWTHandler();
$token = $jwt->getBearerToken();

if (!$token || !$jwt->validateToken($token)) {
    http_response_code(401);
    echo "No autorizado";
    exit();
}

if (!isset($_GET['cliente_id'])) {
    http_response_code(400);
    echo "ID requerido";
    exit();
}

$database = new Database();
$db = $database->getConnection();
$cliente = new Cliente($db);
$cliente->id = (int)$_GET['cliente_id'];

if (!$cliente->readOne() || empty($cliente->documentacion)) {
    http_response_code(404);
    echo "Documento no encontrado";
    exit();
}

$filePath = '../../documentos/' . $cliente->documentacion;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit();
}

$fileName = preg_replace('/^cliente_\d+_\d+_/', '', basename($filePath));

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

ob_clean();
flush();
readfile($filePath);
exit();
?>
