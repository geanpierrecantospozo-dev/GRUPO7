<?php
// ===========================
// PedidoModel.php — MODEL (POO)
// Sistemas de Información Avanzada — UAE
// ===========================
// Procesa la compra completa de la tienda:
//   1) Encuentra o crea al cliente (por cédula).
//   2) Dentro de UNA transacción: bloquea los productos, revisa el stock,
//      simula el pago, registra el pedido y descuenta el stock.
// Transacción = "o se hace todo, o no se hace nada".

require_once "Conexion.php";
require_once "Carrito.php";

// Error "esperable" con un mensaje que SÍ puede ver el usuario
// (ej.: "Stock insuficiente"). Los errores técnicos NO se muestran.
class TiendaException extends Exception {}

class PedidoModel {

    private $pdo;

    // Métodos de pago SIMULADOS. No se pide ni se guarda ningún dato de tarjeta.
    const METODOS_PAGO = [
        "simulado_aprobado"  => "Pago simulado: aprobado",
        "simulado_rechazado" => "Pago simulado: rechazado (para probar el error)",
    ];

    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: (new Conexion())->getConexion();
    }

    // "FOR UPDATE" bloquea las filas hasta terminar la transacción: si dos personas
    // compran lo último que queda, la segunda espera y luego ve el stock actualizado.
    private function bloqueo() {
        return ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === "mysql") ? " FOR UPDATE" : "";
    }

    // Simula la pasarela de pago. Aquí NO se mueve dinero real.
    private function simularPago($metodo) {
        return [
            "aprobado"   => ($metodo === "simulado_aprobado"),
            "referencia" => "SIM-" . strtoupper(bin2hex(random_bytes(4))),
        ];
    }

    // ── Paso 1: cliente ──
    // Si la cédula ya existe se reutiliza el cliente (sin modificar sus datos);
    // si no existe, se crea. Devuelve el id del cliente.
    private function obtenerOCrearCliente(array $c) {
        $stmt = $this->pdo->prepare("SELECT id FROM clientes WHERE cedula = :cedula");
        $stmt->execute([":cedula" => $c["cedula"]]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO clientes (nombre, apellido, cedula, telefono, email)
                 VALUES (:nombre, :apellido, :cedula, :telefono, :email)"
            );
            $stmt->execute([
                ":nombre"   => $c["nombre"],
                ":apellido" => $c["apellido"],
                ":cedula"   => $c["cedula"],
                ":telefono" => $c["telefono"],
                ":email"    => $c["email"],
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Dos personas con la misma cédula a la vez: el segundo INSERT falla por UNIQUE.
            $stmt = $this->pdo->prepare("SELECT id FROM clientes WHERE cedula = :cedula");
            $stmt->execute([":cedula" => $c["cedula"]]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
            throw $e;
        }
    }

    // ── Paso 2: la compra ──
    // $carrito = [id_producto => cantidad]
    // $cliente = nombre, apellido, cedula, telefono, email
    // $envio   = direccion, ciudad
    // Devuelve ['codigo' => ..., 'pedido_id' => ..., 'estado' => 'pagado'|'rechazado']
    // Lanza TiendaException si no se pudo comprar (sin stock, producto inexistente...).
    public function procesarCompra(array $carrito, array $cliente, array $envio, $metodo, $aceptaPrivacidad) {

        if (empty($carrito)) {
            throw new TiendaException("Su carrito está vacío.");
        }
        if (!isset(self::METODOS_PAGO[$metodo])) {
            throw new TiendaException("Seleccione un método de pago válido.");
        }
        if (!$aceptaPrivacidad) {
            throw new TiendaException("Debe aceptar el tratamiento de datos personales para comprar.");
        }

        // El cliente se guarda aparte (transacción propia): aunque el pago sea
        // rechazado, el intento queda registrado a nombre de alguien.
        $idCliente = $this->obtenerOCrearCliente($cliente);

        $ids = array_map("intval", array_keys($carrito));
        sort($ids);                          // siempre el mismo orden evita bloqueos cruzados entre compras
        $marcas = implode(",", array_fill(0, count($ids), "?"));

        try {
            $this->pdo->beginTransaction();

            // a) Leer los productos con el stock ACTUAL y bloquearlos
            $stmt = $this->pdo->prepare(
                "SELECT id, nombre, precio, stock FROM productos WHERE id IN ($marcas) ORDER BY id" . $this->bloqueo()
            );
            $stmt->execute($ids);
            $productos = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $productos[(int) $p["id"]] = $p;
            }

            // b) Validar cada línea con datos reales de la base (no con lo que mande el navegador)
            $lineas   = [];
            $subtotal = 0.0;
            foreach ($ids as $id) {
                $cant = (int) $carrito[$id];
                if (!isset($productos[$id])) {
                    throw new TiendaException("Un producto de su carrito ya no está disponible.");
                }
                $p = $productos[$id];
                if ($cant < 1 || $cant > Carrito::MAX_POR_PRODUCTO) {
                    throw new TiendaException("Cantidad no válida para \"" . $p["nombre"] . "\".");
                }
                if ($cant > (int) $p["stock"]) {
                    throw new TiendaException("Stock insuficiente de \"" . $p["nombre"] . "\" (disponible: " . (int) $p["stock"] . ").");
                }
                $linea = round((float) $p["precio"] * $cant, 2);
                $subtotal += $linea;
                $lineas[] = [
                    "id" => $id, "nombre" => $p["nombre"], "precio" => (float) $p["precio"],
                    "cant" => $cant, "sub" => $linea,
                ];
            }
            $subtotal = round($subtotal, 2);
            $iva      = round($subtotal * Carrito::tasaIva(), 2);
            $total    = round($subtotal + $iva, 2);

            // c) Simular el pago
            $pago   = $this->simularPago($metodo);
            $estado = $pago["aprobado"] ? "pagado" : "rechazado";

            // d) Registrar el pedido (también si fue rechazado, para las estadísticas)
            $codigo = bin2hex(random_bytes(8));
            $stmt = $this->pdo->prepare(
                "INSERT INTO pedidos (codigo, id_cliente, estado, subtotal, iva, total, direccion_envio, ciudad, acepta_privacidad)
                 VALUES (:codigo, :cliente, :estado, :subtotal, :iva, :total, :direccion, :ciudad, 1)"
            );
            $stmt->execute([
                ":codigo" => $codigo, ":cliente" => $idCliente, ":estado" => $estado,
                ":subtotal" => $subtotal, ":iva" => $iva, ":total" => $total,
                ":direccion" => $envio["direccion"], ":ciudad" => $envio["ciudad"],
            ]);
            $idPedido = (int) $this->pdo->lastInsertId();

            $insItem = $this->pdo->prepare(
                "INSERT INTO pedido_items (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal)
                 VALUES (:pedido, :producto, :nombre, :cantidad, :precio, :subtotal)"
            );
            foreach ($lineas as $l) {
                $insItem->execute([
                    ":pedido" => $idPedido, ":producto" => $l["id"], ":nombre" => $l["nombre"],
                    ":cantidad" => $l["cant"], ":precio" => $l["precio"], ":subtotal" => $l["sub"],
                ]);
            }

            // e) Solo si el pago fue aprobado: descontar stock y anotar en el kardex
            if ($pago["aprobado"]) {
                // "AND stock >= cantidad" es la segunda defensa: si por cualquier motivo
                // el stock no alcanza, no se actualiza ninguna fila y se cancela todo.
                $descuento = $this->pdo->prepare(
                    "UPDATE productos SET stock = stock - :cantidad WHERE id = :id AND stock >= :minimo"
                );
                $movimiento = $this->pdo->prepare(
                    "INSERT INTO movimientos_stock (id_producto, tipo, cantidad, referencia)
                     VALUES (:producto, 'salida', :cantidad, :referencia)"
                );
                foreach ($lineas as $l) {
                    $descuento->execute([":cantidad" => $l["cant"], ":id" => $l["id"], ":minimo" => $l["cant"]]);
                    if ($descuento->rowCount() !== 1) {
                        throw new TiendaException("Stock insuficiente de \"" . $l["nombre"] . "\". Actualice su carrito.");
                    }
                    $movimiento->execute([
                        ":producto" => $l["id"], ":cantidad" => $l["cant"],
                        ":referencia" => "Pedido #" . $idPedido,
                    ]);
                }
            }

            $this->pdo->prepare(
                "INSERT INTO pagos (id_pedido, metodo, estado, referencia) VALUES (:pedido, :metodo, :estado, :ref)"
            )->execute([
                ":pedido" => $idPedido, ":metodo" => $metodo,
                ":estado" => $pago["aprobado"] ? "aprobado" : "rechazado", ":ref" => $pago["referencia"],
            ]);

            $this->pdo->commit();
            return ["codigo" => $codigo, "pedido_id" => $idPedido, "estado" => $estado];

        } catch (TiendaException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;                         // mensaje claro para el usuario
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("[Tienda] Error al procesar la compra: " . $e->getMessage());
            throw new TiendaException("No se pudo procesar su pedido. Intente nuevamente.");
        }
    }

    // ── Consultas de lectura ──

    // Pedido por su código público (el de la URL de confirmación)
    public function obtenerPorCodigo($codigo) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                    pg.metodo AS pago_metodo, pg.estado AS pago_estado, pg.referencia AS pago_referencia
             FROM pedidos p
             INNER JOIN clientes c ON p.id_cliente = c.id
             LEFT JOIN pagos pg ON pg.id_pedido = p.id
             WHERE p.codigo = :codigo"
        );
        $stmt->execute([":codigo" => $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                    pg.metodo AS pago_metodo, pg.estado AS pago_estado, pg.referencia AS pago_referencia
             FROM pedidos p
             INNER JOIN clientes c ON p.id_cliente = c.id
             LEFT JOIN pagos pg ON pg.id_pedido = p.id
             WHERE p.id = :id"
        );
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function items($idPedido) {
        $stmt = $this->pdo->prepare(
            "SELECT nombre_producto, cantidad, precio_unitario, subtotal
             FROM pedido_items WHERE id_pedido = :id ORDER BY id"
        );
        $stmt->execute([":id" => $idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listado para el personal (vista view/pedidos.php)
    public function obtenerTodos() {
        $stmt = $this->pdo->query(
            "SELECT p.id, p.fecha_pedido, p.estado, p.ciudad, p.total,
                    c.nombre AS cliente_nombre, c.apellido AS cliente_apellido
             FROM pedidos p
             INNER JOIN clientes c ON p.id_cliente = c.id
             ORDER BY p.id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
