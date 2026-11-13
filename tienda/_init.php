<?php
// _init.php — arranque común de la tienda pública (sin login)
// Sesión con cookies endurecidas + modelos + helpers.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,      // JavaScript no puede leer la cookie
        "samesite" => "Lax",     // el navegador no la envía en formularios de otros sitios
        "secure"   => !empty($_SERVER["HTTPS"]),
    ]);
    session_start();
}
require_once __DIR__ . "/../model/Csrf.php";
require_once __DIR__ . "/../model/Validador.php";
require_once __DIR__ . "/../model/CatalogoModel.php";
require_once __DIR__ . "/../model/Carrito.php";
require_once __DIR__ . "/../model/PedidoModel.php";

// Escapa texto antes de imprimirlo en HTML (evita XSS)
function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
function dinero($n) {
    return "$" . number_format((float) $n, 2, ".", ",");
}
