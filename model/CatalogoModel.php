<?php
// ===========================
// CatalogoModel.php — MODEL (POO)
// Sistemas de Información Avanzada — UAE
// ===========================
// Consultas de SOLO LECTURA para la tienda: listar productos, categorías
// y traer los datos reales (precio, stock) de lo que hay en el carrito.

require_once "Conexion.php";

class CatalogoModel {

    private $pdo;

    // Se puede pasar una conexión ya abierta (útil para pruebas)
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: (new Conexion())->getConexion();
    }

    public function categorias() {
        $stmt = $this->pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lista productos con filtros opcionales: texto ($q) y categoría ($idCategoria)
    public function listar($q = "", $idCategoria = null) {
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.unidad, p.stock,
                       c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c ON p.id_categoria = c.id
                WHERE 1 = 1";
        $params = [];

        if ($q !== "") {
            $sql .= " AND (p.nombre LIKE :q1 OR p.descripcion LIKE :q2)";
            $params[":q1"] = "%" . $q . "%";
            $params[":q2"] = "%" . $q . "%";
        }
        if ($idCategoria !== null) {
            $sql .= " AND p.id_categoria = :cat";
            $params[":cat"] = $idCategoria;
        }
        $sql .= " ORDER BY p.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Un producto por id (o false si no existe)
    public function obtener($id) {
        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.nombre, p.precio, p.unidad, p.stock, c.nombre AS categoria
             FROM productos p
             INNER JOIN categorias c ON p.id_categoria = c.id
             WHERE p.id = :id"
        );
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Varios productos a la vez (los del carrito). Devuelve [id => fila]
    public function obtenerVarios(array $ids) {
        if (empty($ids)) {
            return [];
        }
        $ids = array_values(array_map("intval", $ids));
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.nombre, p.precio, p.unidad, p.stock
             FROM productos p
             WHERE p.id IN ($marcas)"
        );
        $stmt->execute($ids);
        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[(int) $fila["id"]] = $fila;
        }
        return $resultado;
    }
}
?>
