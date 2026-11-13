<?php
// ===========================
// Csrf.php — MODEL (helper de seguridad)
// Sistemas de Información Avanzada — UAE
// ===========================
// CSRF = que otro sitio web envíe un formulario en nombre del usuario.
// Defensa: cada formulario lleva un "token" secreto guardado también en la
// sesión. Si el token que llega no coincide, se rechaza la petición.

function csrf_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

// Imprime el campo oculto que va dentro de cada <form method="POST">
function csrf_campo() {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") . '">';
}

// Se llama al inicio de todo script que procese un POST
function csrf_verificar() {
    $esperado = (string) ($_SESSION["csrf"] ?? "");
    $enviado  = (string) ($_POST["csrf"] ?? "");
    // Los dos deben existir: hash_equals('', '') daría true y dejaría pasar un POST sin token
    if ($esperado === "" || $enviado === "" || !hash_equals($esperado, $enviado)) {
        http_response_code(400);
        exit("Solicitud no válida (token de seguridad incorrecto). Vuelva atrás y recargue la página.");
    }
}
?>
