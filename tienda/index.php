<?php
require_once "_init.php";
$titulo   = "Catálogo";
$catalogo = new CatalogoModel();

$q   = trim((string) ($_GET["q"] ?? ""));
$q   = mb_substr($q, 0, 60);
$cat = filter_var($_GET["cat"] ?? "", FILTER_VALIDATE_INT);
$cat = ($cat === false || $cat < 1) ? null : $cat;

$categorias = $catalogo->categorias();
$productos  = $catalogo->listar($q, $cat);

$msg = $_SESSION["flash"] ?? "";   // mensaje de una sola vez (ej.: "agregado")
unset($_SESSION["flash"]);
$err = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

include "_header.php";
?>
<h1 class="h3 mb-3">Catálogo de productos</h1>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<form class="row g-2 mb-4" method="GET">
    <div class="col-md-5"><input class="form-control" name="q" maxlength="60" placeholder="Buscar producto" value="<?= e($q) ?>"></div>
    <div class="col-md-4">
        <select class="form-select" name="cat">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c["id"] ?>" <?= ($cat === (int) $c["id"]) ? "selected" : "" ?>><?= e($c["nombre"]) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><button class="btn btn-success w-100">Filtrar</button></div>
</form>

<?php if (!$productos): ?>
    <div class="alert alert-info">No se encontraron productos.</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($productos as $p): $hay = (int) $p["stock"] > 0; ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card producto-card <?= $hay ? "" : "sin-stock" ?>">
            <div class="card-body d-flex flex-column">
                <span class="badge bg-secondary align-self-start mb-2"><?= e($p["categoria"]) ?></span>
                <h2 class="h5"><?= e($p["nombre"]) ?></h2>
                <p class="text-muted small flex-grow-1"><?= e($p["descripcion"]) ?></p>
                <div class="precio"><?= dinero($p["precio"]) ?> <small class="text-muted fs-6">/ <?= e($p["unidad"]) ?></small></div>
                <div class="small mb-2"><?= $hay ? "Disponible: " . (int) $p["stock"] : "<span class='text-danger'>Agotado</span>" ?></div>
                <?php if ($hay): ?>
                <form method="POST" action="carrito.php" class="d-flex gap-2">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                    <input class="form-control" type="number" name="cantidad" value="1" min="1" max="<?= Carrito::MAX_POR_PRODUCTO ?>" aria-label="Cantidad">
                    <button class="btn btn-outline-success">Agregar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php include "_footer.php"; ?>
