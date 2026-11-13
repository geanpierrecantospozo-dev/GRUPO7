<?php
require_once "_init.php";
$titulo  = "Carrito";
$carrito = new Carrito();
$error   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verificar();
    $accion = $_POST["accion"] ?? "";
    $id     = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
    $cant   = filter_var($_POST["cantidad"] ?? "1", FILTER_VALIDATE_INT);
    try {
        if ($id === false || $id < 1) {
            throw new Exception("Producto no válido.");
        }
        if ($accion === "agregar") {
            if ($cant === false) { throw new Exception("Cantidad no válida."); }
            $nombre = $carrito->agregar($id, $cant);
            $_SESSION["flash"] = "\"" . $nombre . "\" agregado al carrito.";
            header("Location: index.php");
            exit;
        } elseif ($accion === "actualizar") {
            if ($cant === false) { throw new Exception("Cantidad no válida."); }
            $carrito->actualizar($id, $cant);
        } elseif ($accion === "quitar") {
            $carrito->quitar($id);
        }
    } catch (Exception $ex) {
        if ($accion === "agregar") {
            $_SESSION["flash_error"] = $ex->getMessage();
            header("Location: index.php");
            exit;
        }
        $error = $ex->getMessage();
    }
}

$d = $carrito->detalle();
include "_header.php";
?>
<h1 class="h3 mb-3">Su carrito</h1>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (!$d["items"]): ?>
    <div class="alert alert-info">El carrito está vacío. <a href="index.php">Ver catálogo</a></div>
<?php else: ?>
<div class="table-responsive">
<table class="table align-middle">
    <thead><tr><th>Producto</th><th>Precio</th><th style="width:160px">Cantidad</th><th class="text-end">Subtotal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($d["items"] as $it): ?>
        <tr>
            <td><?= e($it["nombre"]) ?></td>
            <td><?= dinero($it["precio"]) ?></td>
            <td>
                <form method="POST" class="d-flex gap-1">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" value="<?= (int) $it["id"] ?>">
                    <input class="form-control form-control-sm" type="number" name="cantidad" value="<?= (int) $it["cantidad"] ?>" min="0" max="<?= Carrito::MAX_POR_PRODUCTO ?>">
                    <button class="btn btn-sm btn-outline-secondary">OK</button>
                </form>
            </td>
            <td class="text-end"><?= dinero($it["subtotal"]) ?></td>
            <td>
                <form method="POST">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="quitar">
                    <input type="hidden" name="id" value="<?= (int) $it["id"] ?>">
                    <button class="btn btn-sm btn-outline-danger">Quitar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= dinero($d["subtotal"]) ?></td><td></td></tr>
        <tr><td colspan="3" class="text-end">IVA (<?= round(Carrito::tasaIva() * 100) ?>%)</td><td class="text-end"><?= dinero($d["iva"]) ?></td><td></td></tr>
        <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= dinero($d["total"]) ?></td><td></td></tr>
    </tfoot>
</table>
</div>
<a class="btn btn-outline-secondary" href="index.php">Seguir comprando</a>
<a class="btn btn-success" href="checkout.php">Continuar al pago</a>
<?php endif; ?>
<?php include "_footer.php"; ?>
