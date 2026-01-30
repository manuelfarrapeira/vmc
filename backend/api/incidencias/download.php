<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/Incidencia.php';
require_once '../../utils/JWT.php';

// Verificar autenticación
$jwt = new JWTHandler();
$token = $jwt->getBearerToken();

if (!$token || !$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(array("message" => "No autorizado"));
    exit();
}

if (!isset($_GET['incidencia_id'])) {
    http_response_code(400);
    echo "ID de incidencia requerido";
    exit();
}

$database = new Database();
$db = $database->getConnection();
$incidencia = new Incidencia($db);

$incidenciaId = (int)$_GET['incidencia_id'];
$incidencia->id = $incidenciaId;

if (!$incidencia->readOne()) {
    http_response_code(404);
    echo "Incidencia no encontrada";
    exit();
}

if (empty($incidencia->documentacion)) {
    http_response_code(404);
    echo "No hay documento asociado a esta incidencia";
    exit();
}

$uploadsDir = '../../documentos';
$filePath = $uploadsDir . '/' . $incidencia->documentacion;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit();
}

// Obtener información del archivo
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Extraer nombre original del archivo (sin el ID y timestamp)
// Formato: 123_1738281234.pdf -> documento.pdf
$originalFileName = preg_replace('/^\d+_\d+_/', '', $fileName);
if ($originalFileName === $fileName) {
    // Si no tiene el formato esperado, extraer solo la extensión
    $parts = explode('_', $fileName);
    if (count($parts) >= 3) {
        // Formato: 123_1738281234_nombre.pdf
        array_shift($parts); // Quitar ID
        array_shift($parts); // Quitar timestamp
        $originalFileName = implode('_', $parts);
    }
}

// Configurar headers para forzar descarga
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $originalFileName . '"');
header('Content-Length: ' . $fileSize);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Limpiar buffer de salida
ob_clean();
flush();

// Leer y enviar el archivo
readfile($filePath);
exit();
?>
