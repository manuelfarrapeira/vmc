<?php
require_once 'SimpleJWT.php';

class JWTHandler {
    private $secret_key = "your-secret-key-here-change-in-production-vmc-2024";
    private $issuer = "vmc-system";
    private $audience = "vmc-users";
    private $issuedAt;
    private $expire;

    public function __construct() {
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + (60 * 60 * 8); // 8 horas
    }

    public function generateToken($user_id, $username) {
        $payload = array(
            "iss" => $this->issuer,
            "aud" => $this->audience,
            "iat" => $this->issuedAt,
            "exp" => $this->expire,
            "data" => array(
                "user_id" => $user_id,
                "username" => $username
            )
        );

        return SimpleJWT::encode($payload, $this->secret_key, 'HS256');
    }

    public function validateToken($token) {
        try {
            $decoded = SimpleJWT::decode($token, $this->secret_key);
            return (array) $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}
?>
