<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/Incidencia.php';
require_once '../../utils/JWT.php';

header('Content-Type: application/json');

// Verificar autenticación
$jwt = new JWTHandler();
$token = $jwt->getBearerToken();

if (!$token || !$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(array("message" => "No autorizado"));
    exit();
}

$database = new Database();
$db = $database->getConnection();
$incidencia = new Incidencia($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Subir archivo
        if (!isset($_POST['incidencia_id']) || !isset($_FILES['documento'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Faltan datos requeridos"));
            exit();
        }

        $incidenciaId = (int)$_POST['incidencia_id'];
        $file = $_FILES['documento'];

        // Validar que la incidencia existe
        $incidencia->id = $incidenciaId;
        if (!$incidencia->readOne()) {
            http_response_code(404);
            echo json_encode(array("message" => "Incidencia no encontrada"));
            exit();
        }

        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(array("message" => "Error al subir el archivo"));
            exit();
        }

        // Validar tamaño (2MB máximo)
        $maxSize = 2 * 1024 * 1024; // 2MB en bytes
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(array(
                "message" => "El archivo es demasiado grande. Máximo 2MB",
                "size" => $file['size'],
                "maxSize" => $maxSize
            ));
            exit();
        }

        // Validar tipo de archivo (opcional, puedes añadir más validaciones)
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword',
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'text/plain', 'application/zip'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(array(
                "message" => "Tipo de archivo no permitido",
                "detectedType" => $mimeType
            ));
            exit();
        }

        // Crear directorio si no existe
        $uploadsDir = '../../documentos';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Eliminar archivo anterior si existe
        if (!empty($incidencia->documentacion) && file_exists($uploadsDir . '/' . $incidencia->documentacion)) {
            unlink($uploadsDir . '/' . $incidencia->documentacion);
        }

        // Generar nombre de archivo único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $incidenciaId . '_' . time() . '.' . $extension;
        $filePath = $uploadsDir . '/' . $fileName;

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Actualizar base de datos
            $incidencia->documentacion = $fileName;
            if ($incidencia->updateDocumentacion()) {
                http_response_code(200);
                echo json_encode(array(
                    "message" => "Archivo subido exitosamente",
                    "fileName" => $fileName
                ));
            } else {
                // Si falla la actualización, eliminar el archivo
                unlink($filePath);
                http_response_code(500);
                echo json_encode(array("message" => "Error al actualizar la base de datos"));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al guardar el archivo"));
        }
        break;

    case 'DELETE':
        // Eliminar archivo
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->incidencia_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "ID de incidencia requerido"));
            exit();
        }

        $incidenciaId = (int)$data->incidencia_id;
        $incidencia->id = $incidenciaId;

        if (!$incidencia->readOne()) {
            http_response_code(404);
            echo json_encode(array("message" => "Incidencia no encontrada"));
            exit();
        }

        if (empty($incidencia->documentacion)) {
            http_response_code(404);
            echo json_encode(array("message" => "No hay documento asociado"));
            exit();
        }

        $uploadsDir = '../../documentos';
        $filePath = $uploadsDir . '/' . $incidencia->documentacion;

        // Eliminar archivo físico
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Actualizar base de datos
        $incidencia->documentacion = null;
        if ($incidencia->updateDocumentacion()) {
            http_response_code(200);
            echo json_encode(array("message" => "Documento eliminado exitosamente"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al actualizar la base de datos"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}
?>
