<?php
// Variables esperadas: $titulo
$contador = (new Carrito())->contar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? "Tienda") ?> — AgroVentas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/tienda.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">AgroVentas</a>
        <div class="ms-auto d-flex gap-3 align-items-center">
            <a class="text-white text-decoration-none" href="carrito.php">Carrito
                <span class="badge bg-light text-success"><?= (int) $contador ?></span></a>
            <a class="text-white-50 text-decoration-none small" href="../view/login.php">Personal</a>
        </div>
    </div>
</nav>
<main class="container">
