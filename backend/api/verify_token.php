<?php
require_once '../utils/CORS.php';
require_once '../utils/JWT.php';

header('Content-Type: application/json');

try {
    $jwt = new JWTHandler();

    // Obtener token del header Authorization
    $token = $jwt->getBearerToken();

    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'No token provided'
        ]);
        exit();
    }

    // Validar token
    $validation_result = $jwt->validateToken($token);

    if ($validation_result !== false) {
        http_response_code(200);
        echo json_encode([
            'valid' => true,
            'message' => 'Token is valid',
            'user_data' => $validation_result
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'Invalid or expired token'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'Error validating token: ' . $e->getMessage()
    ]);
}
?>
