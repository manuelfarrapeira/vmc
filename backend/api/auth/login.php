<?php
require_once '../../utils/CORS.php';
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../utils/JWT.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$jwt = new JWTHandler();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->user) && !empty($data->pass)) {
        $user->user = $data->user;
        $user->pass = $data->pass;

        if ($user->login()) {
            $token = $jwt->generateToken($user->id, $user->user);

            http_response_code(200);
            echo json_encode(array(
                "message" => "Login exitoso",
                "token" => $token,
                "user" => array(
                    "id" => $user->id,
                    "user" => $user->user
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Usuario o contraseña incorrectos"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos"));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido"));
}
?>
