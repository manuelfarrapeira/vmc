<?php
// No necesitamos incluir database.php aquí, se incluye desde el archivo que usa el modelo

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $user;
    public $pass;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login() {
        $query = "SELECT id, user, pass FROM " . $this->table_name . " WHERE user = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if(password_verify($this->pass, $row['pass'])) {
                $this->id = $row['id'];
                $this->user = $row['user'];
                return true;
            }
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user=:user, pass=:pass";
        $stmt = $this->conn->prepare($query);

        $this->user = htmlspecialchars(strip_tags($this->user));
        $this->pass = password_hash($this->pass, PASSWORD_DEFAULT);

        $stmt->bindParam(":user", $this->user);
        $stmt->bindParam(":pass", $this->pass);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function userExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE user = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
