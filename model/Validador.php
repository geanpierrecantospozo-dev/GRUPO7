<?php
// ===========================
// Validador.php — MODEL (helper)
// Sistemas de Información Avanzada — UAE
// ===========================
// Funciones para validar los datos del formulario de compra EN EL SERVIDOR.
// Aunque el HTML tenga "required", el usuario puede saltárselo, por eso
// siempre se vuelve a validar aquí en PHP.

class Validador {

    // Cédula ecuatoriana: 10 dígitos, provincia válida y dígito verificador (módulo 10)
    public static function cedula($cedula) {
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }
        $provincia = (int) substr($cedula, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            return false;
        }
        if ((int) $cedula[2] >= 6) {     // tercer dígito: personas naturales (0 a 5)
            return false;
        }
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i] * (($i % 2 === 0) ? 2 : 1);
            if ($valor > 9) {
                $valor -= 9;
            }
            $suma += $valor;
        }
        $verificador = (10 - ($suma % 10)) % 10;
        return $verificador === (int) $cedula[9];
    }

    // Teléfono ecuatoriano de 10 dígitos que empieza con 0 (la columna es varchar(10))
    public static function telefono($telefono) {
        return (bool) preg_match('/^0\d{9}$/', $telefono);
    }

    // Solo letras (con tildes y ñ), espacios, punto, apóstrofe y guion
    public static function nombre($texto, $min = 2, $max = 50) {
        $largo = mb_strlen($texto);
        return $largo >= $min && $largo <= $max
            && preg_match('/^[\p{L}][\p{L}\s.\'\-]*$/u', $texto);
    }

    public static function email($email) {
        return mb_strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function direccion($texto) {
        $largo = mb_strlen($texto);
        return $largo >= 5 && $largo <= 200;
    }
}
?>
