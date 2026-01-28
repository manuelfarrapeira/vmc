<?php
// Database connection se pasa desde el archivo principal

class Incidencia {
    private $conn;
    private $table_name = "incidencias";

    public $id;
    public $idcliente;
    public $fecha;
    public $incidencia;
    public $realizado;
    public $respuesta;
    public $cobrado;
    public $finalizada;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read($page = 1, $limit = 20, $idcliente = null, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = [];
        $params = [];

        if ($idcliente) {
            $whereClause[] = "idcliente = :idcliente";
            $params[':idcliente'] = $idcliente;
        }

        if (isset($filters['realizado']) && $filters['realizado'] !== '') {
            $whereClause[] = "realizado = :realizado";
            $params[':realizado'] = $filters['realizado'];
        }

        if (isset($filters['cobrado']) && $filters['cobrado'] !== '') {
            $whereClause[] = "cobrado = :cobrado";
            $params[':cobrado'] = $filters['cobrado'];
        }

        if (isset($filters['finalizada']) && $filters['finalizada'] !== '') {
            $whereClause[] = "finalizada = :finalizada";
            $params[':finalizada'] = $filters['finalizada'];
        }

        $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

        $query = "SELECT i.*, c.nombre as cliente_nombre 
                  FROM " . $this->table_name . " i 
                  LEFT JOIN clientes c ON i.idcliente = c.id 
                  " . $whereSQL . " 
                  ORDER BY i.id DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function count($idcliente = null, $filters = []) {
        $whereClause = [];
        $params = [];

        if ($idcliente) {
            $whereClause[] = "idcliente = :idcliente";
            $params[':idcliente'] = $idcliente;
        }

        if (isset($filters['realizado']) && $filters['realizado'] !== '') {
            $whereClause[] = "realizado = :realizado";
            $params[':realizado'] = $filters['realizado'];
        }

        if (isset($filters['cobrado']) && $filters['cobrado'] !== '') {
            $whereClause[] = "cobrado = :cobrado";
            $params[':cobrado'] = $filters['cobrado'];
        }

        if (isset($filters['finalizada']) && $filters['finalizada'] !== '') {
            $whereClause[] = "finalizada = :finalizada";
            $params[':finalizada'] = $filters['finalizada'];
        }

        $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $whereSQL;
        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET idcliente=:idcliente, fecha=:fecha, incidencia=:incidencia, 
                      realizado=:realizado, respuesta=:respuesta, cobrado=:cobrado, finalizada=:finalizada";

        $stmt = $this->conn->prepare($query);

        $this->incidencia = htmlspecialchars(strip_tags($this->incidencia));
        $this->respuesta = htmlspecialchars(strip_tags($this->respuesta));

        $stmt->bindParam(":idcliente", $this->idcliente);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":incidencia", $this->incidencia);
        $stmt->bindParam(":realizado", $this->realizado);
        $stmt->bindParam(":respuesta", $this->respuesta);
        $stmt->bindParam(":cobrado", $this->cobrado);
        $stmt->bindParam(":finalizada", $this->finalizada);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->idcliente = $row['idcliente'];
            $this->fecha = $row['fecha'];
            $this->incidencia = $row['incidencia'];
            $this->realizado = $row['realizado'];
            $this->respuesta = $row['respuesta'];
            $this->cobrado = $row['cobrado'];
            $this->finalizada = $row['finalizada'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET idcliente=:idcliente, fecha=:fecha, incidencia=:incidencia, 
                      realizado=:realizado, respuesta=:respuesta, cobrado=:cobrado, finalizada=:finalizada 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->incidencia = htmlspecialchars(strip_tags($this->incidencia));
        $this->respuesta = htmlspecialchars(strip_tags($this->respuesta));

        $stmt->bindParam(":idcliente", $this->idcliente);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":incidencia", $this->incidencia);
        $stmt->bindParam(":realizado", $this->realizado);
        $stmt->bindParam(":respuesta", $this->respuesta);
        $stmt->bindParam(":cobrado", $this->cobrado);
        $stmt->bindParam(":finalizada", $this->finalizada);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN realizado = 1 THEN 1 ELSE 0 END) as realizadas,
                    SUM(CASE WHEN cobrado = 1 THEN 1 ELSE 0 END) as cobradas,
                    SUM(CASE WHEN finalizada = 1 THEN 1 ELSE 0 END) as finalizadas,
                    SUM(CASE WHEN finalizada = 0 OR finalizada IS NULL THEN 1 ELSE 0 END) as pendientes
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
