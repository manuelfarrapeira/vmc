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
    case 'GET':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $idcliente = isset($_GET['idcliente']) ? (int)$_GET['idcliente'] : null;

        // Filtros
        $filters = [];
        if (isset($_GET['realizado']) && $_GET['realizado'] !== '') {
            $filters['realizado'] = (int)$_GET['realizado'];
        }
        if (isset($_GET['cobrado']) && $_GET['cobrado'] !== '') {
            $filters['cobrado'] = (int)$_GET['cobrado'];
        }
        if (isset($_GET['titulo']) && $_GET['titulo'] !== '') {
            $filters['titulo'] = $_GET['titulo'];
        }

        // Validar limit
        $allowedLimits = [20, 30, 50];
        if (!in_array($limit, $allowedLimits)) {
            $limit = 20;
        }

        $stmt = $incidencia->read($page, $limit, $idcliente, $filters);
        $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $incidencia->count($idcliente, $filters);

        echo json_encode(array(
            "data" => $incidencias,
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "pages" => ceil($total / $limit)
        ));
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data->idcliente) && !empty($data->incidencia)) {
                $incidencia->idcliente = $data->idcliente;
                $incidencia->fecha = $data->fecha ?? date('d/m/Y');
                $incidencia->titulo = $data->titulo ?? '';
                $incidencia->incidencia = $data->incidencia;
                $incidencia->realizado = $data->realizado ?? 0;
                $incidencia->respuesta = $data->respuesta ?? '';
                $incidencia->cobrado = $data->cobrado ?? 0;
                $incidencia->documentacion = $data->documentacion ?? '';
                $incidencia->qr = $data->qr ?? '';
                $incidencia->hora_inicio = $data->hora_inicio ?? '';
                $incidencia->hora_fin = $data->hora_fin ?? '';

                $createdId = $incidencia->create();
                if ($createdId) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Incidencia creada exitosamente",
                        "id" => $createdId
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "No se pudo crear la incidencia"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Datos incompletos"));
            }
        } catch (Exception $e) {
            error_log("Error en incidencias/index.php POST: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(array(
                "error" => "Error al crear incidencia",
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id) && !empty($data->idcliente) && !empty($data->incidencia)) {
            $incidencia->id = $data->id;
            $incidencia->idcliente = $data->idcliente;
            $incidencia->fecha = $data->fecha ?? date('d/m/Y');
            $incidencia->titulo = $data->titulo ?? '';
            $incidencia->incidencia = $data->incidencia;
            $incidencia->realizado = $data->realizado ?? 0;
            $incidencia->respuesta = $data->respuesta ?? '';
            $incidencia->cobrado = $data->cobrado ?? 0;
            $incidencia->documentacion = $data->documentacion ?? '';
            $incidencia->qr = $data->qr ?? '';
            $incidencia->hora_inicio = $data->hora_inicio ?? '';
            $incidencia->hora_fin = $data->hora_fin ?? '';

            if ($incidencia->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Incidencia actualizada exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar la incidencia"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            $incidencia->id = $data->id;

            if ($incidencia->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Incidencia eliminada exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar la incidencia"));
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
