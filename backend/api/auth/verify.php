<?php
require_once '../../utils/CORS.php';
require_once '../../utils/JWT.php';

header('Content-Type: application/json');

$jwt = new JWTHandler();
$token = $jwt->getBearerToken();

if ($token) {
    $decoded = $jwt->validateToken($token);

    if ($decoded) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "Token válido",
            "data" => $decoded
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Token inválido"));
    }
} else {
    http_response_code(401);
    echo json_encode(array("message" => "Token no proporcionado"));
}
?>
