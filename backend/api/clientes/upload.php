<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/Cliente.php';
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
$cliente = new Cliente($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        try {
            // Subir archivo
            if (!isset($_POST['cliente_id']) || !isset($_FILES['documento'])) {
                http_response_code(400);
                echo json_encode(array("message" => "Faltan datos requeridos"));
                exit();
            }

            $clienteId = (int)$_POST['cliente_id'];
            $file = $_FILES['documento'];

            // Validar que el cliente existe
            $cliente->id = $clienteId;
            if (!$cliente->readOne()) {
                http_response_code(404);
                echo json_encode(array("message" => "Cliente no encontrado"));
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

            // Validar tipo de archivo
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

            // Crear directorio si no existe (mismo que incidencias)
            $uploadsDir = '../../documentos';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Eliminar archivo anterior si existe
            if (!empty($cliente->documentacion) && file_exists($uploadsDir . '/' . $cliente->documentacion)) {
                unlink($uploadsDir . '/' . $cliente->documentacion);
            }

            // Generar nombre de archivo único con ID de cliente
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'cliente_' . $clienteId . '_' . time() . '.' . $extension;
            $filePath = $uploadsDir . '/' . $fileName;

            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Actualizar base de datos
                $cliente->documentacion = $fileName;
                if ($cliente->updateDocumentacion()) {
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
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array(
                "message" => "Error al procesar la solicitud",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ));
        }
        break;

    case 'DELETE':
        // Eliminar archivo
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->cliente_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "ID de cliente requerido"));
            exit();
        }

        $clienteId = (int)$data->cliente_id;
        $cliente->id = $clienteId;

        if (!$cliente->readOne()) {
            http_response_code(404);
            echo json_encode(array("message" => "Cliente no encontrado"));
            exit();
        }

        if (empty($cliente->documentacion)) {
            http_response_code(404);
            echo json_encode(array("message" => "No hay documento asociado"));
            exit();
        }

        $uploadsDir = '../../documentos';
        $filePath = $uploadsDir . '/' . $cliente->documentacion;

        // Eliminar archivo físico
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Actualizar base de datos
        $cliente->documentacion = null;
        if ($cliente->updateDocumentacion()) {
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
