<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/Cliente.php';
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

$cliente = new Cliente($db);
$incidencia = new Incidencia($db);

// Obtener estadísticas
$totalClientes = $cliente->count();
$statsIncidencias = $incidencia->getStats();

echo json_encode(array(
    "clientes" => array(
        "total" => (int)$totalClientes
    ),
    "incidencias" => array(
        "total" => (int)$statsIncidencias['total'],
        "realizadas" => (int)$statsIncidencias['realizadas'],
        "cobradas" => (int)$statsIncidencias['cobradas']
    )
));
?>
