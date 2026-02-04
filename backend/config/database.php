<?php
class Database {
    private $host = "localhost";
    private $db_name = "vmcserve_vmc";
//    private $username = "vmcserve_vmc";
    private $username = "root";
//    private $password = "Oreo6316";
    private $password = "";
    private $charset = "utf8";


    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            // En producción, no mostrar detalles específicos
            // En desarrollo, mostrar el error completo
            if (ini_get('display_errors') == '1') {
                die(json_encode([
                    "error" => "Database Connection Error",
                    "message" => $exception->getMessage(),
                    "code" => $exception->getCode()
                ]));
            } else {
                die(json_encode(["error" => "Database connection failed"]));
            }
        }
        return $this->conn;
    }
}
?>
