<?php
require_once "_init.php";
$titulo = "Pedido";
$codigo = (string) ($_GET["c"] ?? "");
$modelo = new PedidoModel();
// El código es un valor aleatorio de 16 caracteres hex: no se puede adivinar el de otra persona
$pedido = preg_match('/^[a-f0-9]{16}$/', $codigo) ? $modelo->obtenerPorCodigo($codigo) : false;
if (!$pedido) {
    http_response_code(404);
}
include "_header.php";
if (!$pedido): ?>
    <div class="alert alert-warning">Pedido no encontrado. <a href="index.php">Volver al catálogo</a></div>
<?php else: $items = $modelo->items($pedido["id"]); $ok = $pedido["estado"] === "pagado"; ?>
    <div class="alert alert-<?= $ok ? "success" : "danger" ?>">
        <?= $ok ? "¡Pago aprobado! Su pedido fue registrado." : "El pago fue rechazado (simulación). No se descontó stock ni se cobró nada. Su carrito se conserva para reintentar." ?>
    </div>
    <h1 class="h4">Pedido #<?= (int) $pedido["id"] ?></h1>
    <p class="text-muted small">
        Cliente: <?= e($pedido["cliente_nombre"] . " " . $pedido["cliente_apellido"]) ?> ·
        Envío: <?= e($pedido["direccion_envio"]) ?>, <?= e($pedido["ciudad"]) ?> ·
        Referencia de pago: <?= e($pedido["pago_referencia"]) ?> ·
        Fecha: <?= e($pedido["fecha_pedido"]) ?>
    </p>
    <table class="table">
        <thead><tr><th>Producto</th><th>Cant.</th><th>P. unit.</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr><td><?= e($it["nombre_producto"]) ?></td><td><?= (int) $it["cantidad"] ?></td><td><?= dinero($it["precio_unitario"]) ?></td><td class="text-end"><?= dinero($it["subtotal"]) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= dinero($pedido["subtotal"]) ?></td></tr>
            <tr><td colspan="3" class="text-end">IVA</td><td class="text-end"><?= dinero($pedido["iva"]) ?></td></tr>
            <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= dinero($pedido["total"]) ?></td></tr>
        </tfoot>
    </table>
    <a class="btn btn-success" href="<?= $ok ? "index.php" : "checkout.php" ?>"><?= $ok ? "Seguir comprando" : "Reintentar pago" ?></a>
<?php endif;
include "_footer.php";
