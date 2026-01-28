<?php
// Database connection se pasa desde el archivo principal

class Cliente {
    private $conn;
    private $table_name = "clientes";

    public $id;
    public $nombre;
    public $razon_social;
    public $codigo;
    public $tlf;
    public $observaciones;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read($page = 1, $limit = 20, $search = '', $orderBy = 'nombre', $orderDir = 'ASC', $estadoIncidencias = '') {
        $offset = ($page - 1) * $limit;
        $searchQuery = '';
        $params = [];
        $fromClause = $this->table_name;

        // Filtro por estado de incidencias
        if (!empty($estadoIncidencias)) {
            $fromClause = $this->table_name . " INNER JOIN incidencias ON " . $this->table_name . ".id = incidencias.idcliente";

            switch ($estadoIncidencias) {
                case 'sin_finalizar':
                    $searchQuery = "WHERE incidencias.finalizada = 0";
                    break;
                case 'sin_cobrar':
                    $searchQuery = "WHERE incidencias.cobrado = 0";
                    break;
                case 'sin_realizar':
                    $searchQuery = "WHERE incidencias.realizado = 0";
                    break;
            }
        }

        // Filtro de búsqueda
        if (!empty($search)) {
            if (!empty($searchQuery)) {
                $searchQuery .= " AND (";
            } else {
                $searchQuery = "WHERE (";
            }
            $searchQuery .= $this->table_name . ".nombre LIKE :search1 OR " .
                           $this->table_name . ".razon_social LIKE :search2 OR " .
                           $this->table_name . ".codigo LIKE :search3 OR " .
                           $this->table_name . ".tlf LIKE :search4";
            $searchQuery .= ")"; // Cerrar el paréntesis del grupo de búsqueda
            $searchValue = '%' . $search . '%';
            $params[':search1'] = $searchValue;
            $params[':search2'] = $searchValue;
            $params[':search3'] = $searchValue;
            $params[':search4'] = $searchValue;
        }

        $allowedOrderBy = ['nombre', 'razon_social', 'codigo'];
        $orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'nombre';
        $orderDir = in_array(strtoupper($orderDir), ['ASC', 'DESC']) ? strtoupper($orderDir) : 'ASC';

        // Si hay JOIN, usar DISTINCT y añadir prefijo a la tabla
        $selectClause = !empty($estadoIncidencias) ? "DISTINCT " . $this->table_name . ".*" : "*";
        $orderByClause = !empty($estadoIncidencias) ? $this->table_name . "." . $orderBy : $orderBy;

        $query = "SELECT " . $selectClause . " FROM " . $fromClause . " " . $searchQuery . " ORDER BY " . $orderByClause . " " . $orderDir . " LIMIT :limit OFFSET :offset";


        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function count($search = '', $estadoIncidencias = '') {
        $searchQuery = '';
        $params = [];
        $fromClause = $this->table_name;
        $countClause = "COUNT(*)";

        // Filtro por estado de incidencias
        if (!empty($estadoIncidencias)) {
            $fromClause = $this->table_name . " INNER JOIN incidencias ON " . $this->table_name . ".id = incidencias.idcliente";
            $countClause = "COUNT(DISTINCT " . $this->table_name . ".id)";

            switch ($estadoIncidencias) {
                case 'sin_finalizar':
                    $searchQuery = "WHERE incidencias.finalizada = 0";
                    break;
                case 'sin_cobrar':
                    $searchQuery = "WHERE incidencias.cobrado = 0";
                    break;
                case 'sin_realizar':
                    $searchQuery = "WHERE incidencias.realizado = 0";
                    break;
            }
        }

        // Filtro de búsqueda
        if (!empty($search)) {
            if (!empty($searchQuery)) {
                $searchQuery .= " AND (";
            } else {
                $searchQuery = "WHERE (";
            }
            $searchQuery .= $this->table_name . ".nombre LIKE :search1 OR " .
                           $this->table_name . ".razon_social LIKE :search2 OR " .
                           $this->table_name . ".codigo LIKE :search3 OR " .
                           $this->table_name . ".tlf LIKE :search4";
            $searchQuery .= ")"; // Cerrar el paréntesis del grupo de búsqueda
            $searchValue = '%' . $search . '%';
            $params[':search1'] = $searchValue;
            $params[':search2'] = $searchValue;
            $params[':search3'] = $searchValue;
            $params[':search4'] = $searchValue;
        }

        $query = "SELECT " . $countClause . " as total FROM " . $fromClause . " " . $searchQuery;


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
                  SET nombre=:nombre, razon_social=:razon_social, codigo=:codigo, tlf=:tlf, observaciones=:observaciones";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->razon_social = htmlspecialchars(strip_tags($this->razon_social));
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));
        $this->tlf = htmlspecialchars(strip_tags($this->tlf));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":razon_social", $this->razon_social);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":tlf", $this->tlf);
        $stmt->bindParam(":observaciones", $this->observaciones);

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
            $this->nombre = $row['nombre'];
            $this->razon_social = $row['razon_social'];
            $this->codigo = $row['codigo'];
            $this->tlf = $row['tlf'];
            $this->observaciones = $row['observaciones'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre=:nombre, razon_social=:razon_social, codigo=:codigo, tlf=:tlf, observaciones=:observaciones 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->razon_social = htmlspecialchars(strip_tags($this->razon_social));
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));
        $this->tlf = htmlspecialchars(strip_tags($this->tlf));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":razon_social", $this->razon_social);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":tlf", $this->tlf);
        $stmt->bindParam(":observaciones", $this->observaciones);
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
}
?>
