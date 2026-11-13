<?php
// ===========================
// Carrito.php — MODEL (POO)
// Sistemas de Información Avanzada — UAE
// ===========================
// El carrito vive en la SESIÓN del visitante: $_SESSION["carrito"] = [id_producto => cantidad].
// IMPORTANTE: el carrito NO guarda precios. Cada vez que se muestra o se paga,
// el precio se lee de la base de datos. Así nadie puede alterar un precio
// modificando el formulario.

require_once "CatalogoModel.php";

class Carrito {

    // Máximo de unidades de un mismo producto en una sola compra
    const MAX_POR_PRODUCTO = 20;

    private $catalogo;

    public function __construct($catalogo = null) {
        $this->catalogo = $catalogo ?: new CatalogoModel();
    }

    public function obtener() {
        return $_SESSION["carrito"] ?? [];
    }

    public function contar() {
        return array_sum($this->obtener());
    }

    public function vaciar() {
        unset($_SESSION["carrito"]);
    }

    public function quitar($idProducto) {
        unset($_SESSION["carrito"][$idProducto]);
    }

    // Suma unidades al carrito validando que el producto exista y alcance el stock.
    // Lanza Exception con un mensaje claro para el usuario si algo no cumple.
    public function agregar($idProducto, $cantidad) {
        if ($cantidad < 1 || $cantidad > self::MAX_POR_PRODUCTO) {
            throw new Exception("La cantidad debe estar entre 1 y " . self::MAX_POR_PRODUCTO . ".");
        }
        $producto = $this->catalogo->obtener($idProducto);
        if (!$producto) {
            throw new Exception("El producto no existe.");
        }
        $nueva = ($_SESSION["carrito"][$idProducto] ?? 0) + $cantidad;
        if ($nueva > (int) $producto["stock"]) {
            throw new Exception("Stock insuficiente de \"" . $producto["nombre"] . "\" (disponible: " . (int) $producto["stock"] . ").");
        }
        if ($nueva > self::MAX_POR_PRODUCTO) {
            throw new Exception("Máximo " . self::MAX_POR_PRODUCTO . " unidades por producto.");
        }
        $_SESSION["carrito"][$idProducto] = $nueva;
        return $producto["nombre"];
    }

    // Cambia la cantidad exacta de un producto (0 = quitarlo)
    public function actualizar($idProducto, $cantidad) {
        if ($cantidad <= 0) {
            $this->quitar($idProducto);
            return;
        }
        $anterior = $_SESSION["carrito"][$idProducto] ?? null;
        unset($_SESSION["carrito"][$idProducto]);
        try {
            $this->agregar($idProducto, $cantidad);
        } catch (Exception $e) {
            if ($anterior !== null) {
                $_SESSION["carrito"][$idProducto] = $anterior;   // si falla, se conserva lo que había
            }
            throw $e;
        }
    }

    // Arma el detalle con precios REALES de la base de datos y los totales
    public function detalle() {
        $carrito   = $this->obtener();
        $productos = $this->catalogo->obtenerVarios(array_keys($carrito));
        $items     = [];
        $subtotal  = 0.0;

        foreach ($carrito as $id => $cantidad) {
            if (!isset($productos[$id])) {
                continue;                                  // producto eliminado de la BD
            }
            $p = $productos[$id];
            $linea = round((float) $p["precio"] * $cantidad, 2);
            $subtotal += $linea;
            $items[] = [
                "id"       => (int) $p["id"],
                "nombre"   => $p["nombre"],
                "unidad"   => $p["unidad"],
                "precio"   => (float) $p["precio"],
                "stock"    => (int) $p["stock"],
                "cantidad" => (int) $cantidad,
                "subtotal" => $linea,
            ];
        }
        $subtotal = round($subtotal, 2);
        $iva      = round($subtotal * self::tasaIva(), 2);
        return [
            "items"    => $items,
            "subtotal" => $subtotal,
            "iva"      => $iva,
            "total"    => round($subtotal + $iva, 2),
        ];
    }

    // Tasa de IVA: se puede cambiar con define("IVA_TASA", 0.15) en config.php
    public static function tasaIva() {
        return defined("IVA_TASA") ? (float) IVA_TASA : 0.15;
    }
}
?>
