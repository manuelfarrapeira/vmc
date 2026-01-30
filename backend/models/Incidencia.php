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
    public $documentacion;
    public $qr;

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
                      realizado=:realizado, respuesta=:respuesta, cobrado=:cobrado, 
                      documentacion=:documentacion, qr=:qr";

        $stmt = $this->conn->prepare($query);

        $this->incidencia = htmlspecialchars(strip_tags($this->incidencia));
        $this->respuesta = htmlspecialchars(strip_tags($this->respuesta));
        $this->documentacion = htmlspecialchars(strip_tags($this->documentacion));
        $this->qr = htmlspecialchars(strip_tags($this->qr));

        $stmt->bindParam(":idcliente", $this->idcliente);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":incidencia", $this->incidencia);
        $stmt->bindParam(":realizado", $this->realizado);
        $stmt->bindParam(":respuesta", $this->respuesta);
        $stmt->bindParam(":cobrado", $this->cobrado);
        $stmt->bindParam(":documentacion", $this->documentacion);
        $stmt->bindParam(":qr", $this->qr);

        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return $this->id;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error en Incidencia->create(): " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: idcliente={$this->idcliente}, fecha={$this->fecha}, realizado={$this->realizado}, cobrado={$this->cobrado}");
            throw $e;
        }
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
            $this->documentacion = $row['documentacion'];
            $this->qr = $row['qr'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET idcliente=:idcliente, fecha=:fecha, incidencia=:incidencia, 
                      realizado=:realizado, respuesta=:respuesta, cobrado=:cobrado, 
                      documentacion=:documentacion, qr=:qr 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->incidencia = htmlspecialchars(strip_tags($this->incidencia));
        $this->respuesta = htmlspecialchars(strip_tags($this->respuesta));
        $this->documentacion = htmlspecialchars(strip_tags($this->documentacion));
        $this->qr = htmlspecialchars(strip_tags($this->qr));

        $stmt->bindParam(":idcliente", $this->idcliente);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":incidencia", $this->incidencia);
        $stmt->bindParam(":realizado", $this->realizado);
        $stmt->bindParam(":respuesta", $this->respuesta);
        $stmt->bindParam(":cobrado", $this->cobrado);
        $stmt->bindParam(":documentacion", $this->documentacion);
        $stmt->bindParam(":qr", $this->qr);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        // First, get the documentacion filename before deleting the record
        $queryGet = "SELECT documentacion FROM " . $this->table_name . " WHERE id = ?";
        $stmtGet = $this->conn->prepare($queryGet);
        $stmtGet->bindParam(1, $this->id);
        $stmtGet->execute();
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

        // If there's a document, delete the physical file
        if ($row && !empty($row['documentacion'])) {
            $uploadsDir = __DIR__ . '/../documentos';
            $filePath = $uploadsDir . '/' . $row['documentacion'];

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Now delete the database record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update only documentacion field
     */
    public function updateDocumentacion() {
        $query = "UPDATE " . $this->table_name . " 
                  SET documentacion = :documentacion 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Bind NULL explícitamente si documentacion es null
        if ($this->documentacion === null) {
            $stmt->bindValue(":documentacion", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":documentacion", $this->documentacion);
        }

        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN realizado = 1 THEN 1 ELSE 0 END) as realizadas,
                    SUM(CASE WHEN cobrado = 1 THEN 1 ELSE 0 END) as cobradas
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
