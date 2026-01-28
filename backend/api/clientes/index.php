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
    case 'GET':
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'nombre';
            $orderDir = isset($_GET['orderDir']) ? $_GET['orderDir'] : 'ASC';
            $estadoIncidencias = isset($_GET['estadoIncidencias']) ? $_GET['estadoIncidencias'] : '';

            // Validar limit
            $allowedLimits = [20, 30, 50];
            if (!in_array($limit, $allowedLimits)) {
                $limit = 20;
            }

            // Validar estadoIncidencias
            $allowedEstados = ['', 'sin_cobrar', 'sin_realizar'];
            if (!in_array($estadoIncidencias, $allowedEstados)) {
                $estadoIncidencias = '';
            }

            $stmt = $cliente->read($page, $limit, $search, $orderBy, $orderDir, $estadoIncidencias);
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = $cliente->count($search, $estadoIncidencias);

            echo json_encode(array(
                "data" => $clientes,
                "total" => $total,
                "page" => $page,
                "limit" => $limit,
                "pages" => ceil($total / $limit)
            ));
        } catch (Exception $e) {
            error_log("Error en clientes/index.php GET: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(array(
                "error" => "Error al obtener clientes",
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nombre)) {
            $cliente->nombre = $data->nombre;
            $cliente->razon_social = $data->razon_social ?? '';
            $cliente->dni = $data->dni ?? '';
            $cliente->tlf = $data->tlf ?? null;
            $cliente->observaciones = $data->observaciones ?? '';

            if ($cliente->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Cliente creado exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el cliente"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id) && !empty($data->nombre)) {
            $cliente->id = $data->id;
            $cliente->nombre = $data->nombre;
            $cliente->razon_social = $data->razon_social ?? '';
            $cliente->dni = $data->dni ?? '';
            $cliente->tlf = $data->tlf ?? null;
            $cliente->observaciones = $data->observaciones ?? '';

            if ($cliente->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Cliente actualizado exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el cliente"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            $cliente->id = $data->id;

            if ($cliente->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Cliente eliminado exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el cliente"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID requerido"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}
?>
