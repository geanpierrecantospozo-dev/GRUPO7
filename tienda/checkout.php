<?php
require_once "_init.php";
$titulo  = "Finalizar compra";
$carrito = new Carrito();
$d       = $carrito->detalle();

if (!$d["items"]) {
    header("Location: carrito.php");
    exit;
}

$v      = ["nombre"=>"","apellido"=>"","cedula"=>"","telefono"=>"","email"=>"","direccion"=>"","ciudad"=>"","metodo"=>"simulado_aprobado"];
$errores = [];
$errorGeneral = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verificar();
    foreach ($v as $k => $_) {
        $v[$k] = trim((string) ($_POST[$k] ?? ""));
    }
    $acepta = !empty($_POST["acepta"]);

    // Validación en el SERVIDOR (el HTML "required" se puede saltar)
    if (!Validador::nombre($v["nombre"]))    { $errores["nombre"]    = "Nombre no válido (solo letras, 2 a 50)."; }
    if (!Validador::nombre($v["apellido"]))  { $errores["apellido"]  = "Apellido no válido (solo letras, 2 a 50)."; }
    if (!Validador::cedula($v["cedula"]))    { $errores["cedula"]    = "Cédula ecuatoriana no válida."; }
    if (!Validador::telefono($v["telefono"])){ $errores["telefono"]  = "Teléfono de 10 dígitos que empiece con 0."; }
    if (!Validador::email($v["email"]))      { $errores["email"]     = "Correo electrónico no válido."; }
    if (!Validador::direccion($v["direccion"])) { $errores["direccion"] = "Dirección de 5 a 200 caracteres."; }
    if (!Validador::nombre($v["ciudad"], 2, 80)) { $errores["ciudad"] = "Ciudad no válida."; }
    if (!isset(PedidoModel::METODOS_PAGO[$v["metodo"]])) { $errores["metodo"] = "Seleccione un método de pago."; }
    if (!$acepta) { $errores["acepta"] = "Debe aceptar el tratamiento de sus datos personales."; }

    if (!$errores) {
        try {
            $res = (new PedidoModel())->procesarCompra(
                $carrito->obtener(),
                ["nombre"=>$v["nombre"],"apellido"=>$v["apellido"],"cedula"=>$v["cedula"],"telefono"=>$v["telefono"],"email"=>$v["email"]],
                ["direccion"=>$v["direccion"],"ciudad"=>$v["ciudad"]],
                $v["metodo"],
                $acepta
            );
            // Se vacía el carrito solo si el pago fue aprobado; si fue rechazado puede reintentar
            if ($res["estado"] === "pagado") {
                $carrito->vaciar();
            }
            header("Location: pedido.php?c=" . urlencode($res["codigo"]));
            exit;
        } catch (TiendaException $ex) {
            $errorGeneral = $ex->getMessage();
        }
    }
}

function campo($nombre, $etiqueta, $v, $errores, $extra = "") {
    $inv = isset($errores[$nombre]) ? " is-invalid" : "";
    echo '<div class="col-md-6"><label class="form-label">' . e($etiqueta) . '</label>';
    echo '<input class="form-control' . $inv . '" name="' . e($nombre) . '" value="' . e($v[$nombre]) . '" required ' . $extra . '>';
    if ($inv) { echo '<div class="invalid-feedback">' . e($errores[$nombre]) . '</div>'; }
    echo '</div>';
}

include "_header.php";
?>
<h1 class="h3 mb-3">Finalizar compra</h1>
<?php if ($errorGeneral): ?><div class="alert alert-danger"><?= e($errorGeneral) ?> <a href="carrito.php">Revisar carrito</a></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-8">
<form method="POST" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <?php
        campo("nombre", "Nombres", $v, $errores, 'maxlength="50"');
        campo("apellido", "Apellidos", $v, $errores, 'maxlength="50"');
        campo("cedula", "Cédula", $v, $errores, 'maxlength="10" inputmode="numeric"');
        campo("telefono", "Teléfono", $v, $errores, 'maxlength="10" inputmode="numeric"');
        campo("email", "Correo electrónico", $v, $errores, 'type="email" maxlength="150"');
        campo("ciudad", "Ciudad", $v, $errores, 'maxlength="80"');
        ?>
        <div class="col-12">
            <?php campo("direccion", "Dirección de envío", $v, $errores, 'maxlength="200"'); ?>
        </div>
        <div class="col-12">
            <label class="form-label">Método de pago (simulado)</label>
            <?php foreach (PedidoModel::METODOS_PAGO as $clave => $texto): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metodo" id="m_<?= e($clave) ?>" value="<?= e($clave) ?>" <?= $v["metodo"] === $clave ? "checked" : "" ?>>
                    <label class="form-check-label" for="m_<?= e($clave) ?>"><?= e($texto) ?></label>
                </div>
            <?php endforeach; ?>
            <div class="form-text">No ingrese datos de tarjeta: el sistema no los solicita ni los guarda.</div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input <?= isset($errores["acepta"]) ? "is-invalid" : "" ?>" type="checkbox" name="acepta" id="acepta" value="1">
                <label class="form-check-label" for="acepta">Acepto el tratamiento de mis datos personales según la <a href="privacidad.php" target="_blank">política de privacidad</a>.</label>
                <?php if (isset($errores["acepta"])): ?><div class="invalid-feedback"><?= e($errores["acepta"]) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="col-12"><button class="btn btn-success">Pagar <?= dinero($d["total"]) ?></button>
            <a class="btn btn-link" href="carrito.php">Volver al carrito</a></div>
    </div>
</form>
</div>
<div class="col-lg-4">
    <div class="card"><div class="card-body">
        <h2 class="h6">Resumen</h2>
        <?php foreach ($d["items"] as $it): ?>
            <div class="d-flex justify-content-between small"><span><?= (int) $it["cantidad"] ?> × <?= e($it["nombre"]) ?></span><span><?= dinero($it["subtotal"]) ?></span></div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between small"><span>Subtotal</span><span><?= dinero($d["subtotal"]) ?></span></div>
        <div class="d-flex justify-content-between small"><span>IVA</span><span><?= dinero($d["iva"]) ?></span></div>
        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= dinero($d["total"]) ?></span></div>
    </div></div>
</div>
</div>
<?php include "_footer.php"; ?>
