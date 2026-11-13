<?php
require_once "_init.php";
$titulo = "Política de privacidad";
include "_header.php";
?>
<h1 class="h3 mb-3">Política de privacidad y tratamiento de datos personales</h1>
<p class="text-muted small">Conforme a la Ley Orgánica de Protección de Datos Personales (LOPDP) del Ecuador.</p>

<h2 class="h5 mt-4">1. Responsable</h2>
<p>AgroVentas, proyecto académico de la asignatura Sistemas de Información Avanzada, Universidad Agraria del Ecuador.</p>

<h2 class="h5 mt-4">2. Datos que recopilamos (minimización)</h2>
<p>Solo los necesarios para completar su pedido: nombres, apellidos, cédula, teléfono, correo electrónico, dirección y ciudad de envío. <strong>No solicitamos ni guardamos datos de tarjetas</strong>: el pago es una simulación.</p>

<h2 class="h5 mt-4">3. Finalidad</h2>
<p>Procesar y entregar su pedido, registrar la venta y generar estadísticas agregadas de ventas. No usamos sus datos para publicidad ni los vendemos o cedemos a terceros.</p>

<h2 class="h5 mt-4">4. Consentimiento</h2>
<p>Antes de comprar debe aceptar expresamente esta política mediante una casilla. El consentimiento queda registrado en el pedido.</p>

<h2 class="h5 mt-4">5. Confidencialidad y seguridad</h2>
<p>Acceso restringido al personal autorizado con usuario y contraseña (almacenada cifrada), consultas con parámetros preparados, tokens CSRF y conexión a la base de datos con credenciales fuera del repositorio. Los tableros de análisis usan datos agregados, sin cédula, teléfono ni correo.</p>

<h2 class="h5 mt-4">6. Conservación</h2>
<p>Los datos se conservan solo mientras dure el proyecto académico y se eliminan o anonimizan al finalizar.</p>

<h2 class="h5 mt-4">7. Sus derechos</h2>
<p>Puede solicitar acceso, rectificación, actualización, eliminación u oposición al tratamiento de sus datos escribiendo al equipo del proyecto (correo del docente/equipo indicado en el informe).</p>
<a class="btn btn-outline-success" href="index.php">Volver al catálogo</a>
<?php include "_footer.php"; ?>
