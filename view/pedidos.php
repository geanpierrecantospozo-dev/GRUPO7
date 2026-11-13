<?php
require_once "../model/verificar_sesion.php";
require_once "../model/PedidoModel.php";

// Mismo permiso que el módulo de ventas
if (!tienePermiso("ventas")) {
    http_response_code(403);
    exit("No tiene permiso para ver este módulo.");
}

$pedidos = (new PedidoModel())->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Pedidos web — Mi Proyecto</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="../css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <?php
        $rutaBase     = "../";
        $paginaActual = "pedidos.php";
        include "../partials/navbar.php";
        ?>
        <div id="layoutSidenav">
            <?php include "../partials/sidebar.php"; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Pedidos de la tienda en línea</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pedidos web</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-box me-1"></i> Pedidos</div>
                            <div class="card-body">
                                <table id="tablaPedidos" class="table table-striped table-bordered">
                                    <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Ciudad</th><th>Estado</th><th>Total</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($pedidos as $p): ?>
                                        <tr>
                                            <td><?= (int) $p["id"] ?></td>
                                            <td><?= htmlspecialchars($p["fecha_pedido"]) ?></td>
                                            <td><?= htmlspecialchars($p["cliente_nombre"] . " " . $p["cliente_apellido"]) ?></td>
                                            <td><?= htmlspecialchars($p["ciudad"]) ?></td>
                                            <td><span class="badge bg-<?= $p["estado"] === "pagado" ? "success" : "danger" ?>"><?= htmlspecialchars($p["estado"]) ?></span></td>
                                            <td>$<?= number_format($p["total"], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="../js/scripts.js"></script>
        <script>new simpleDatatables.DataTable("#tablaPedidos");</script>
    </body>
</html>
