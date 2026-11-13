# Módulo de comercio electrónico (Entrega 1)

## Instalación en el hosting
1. phpMyAdmin → su base → Importar `sql/02_ecommerce.sql` (después de `sia_base.sql`; solo agrega 3 tablas).
2. Subir a `htdocs/` la carpeta `tienda/`, `model/`, `view/`, `partials/`, `api/`, `css/` (sobrescribir). **No** sobrescribir su `config.php`.
3. Abrir `https://SU-DOMINIO/tienda/index.php`.

## Flujo
catálogo (`tienda/index.php`) → carrito (`carrito.php`, en sesión) → checkout (`checkout.php`) → pedido (`pedido.php`).
Personal: `view/pedidos.php`. Datos para BI: `api/datos.php?token=...&recurso=pedidos`.

## Requisitos y dónde se cumplen
| Requisito | Dónde |
|---|---|
| Catálogo con búsqueda/filtro | `CatalogoModel::listar`, `tienda/index.php` |
| Carrito (precio siempre desde la BD) | `model/Carrito.php` |
| Pedido con cliente, ítems y totales + IVA | `PedidoModel::procesarCompra`, tablas `pedidos`, `pedido_items` |
| Pago simulado (aprobado/rechazado) | `PedidoModel::simularPago`, tabla `pagos` |
| Stock seguro | transacción + `SELECT … FOR UPDATE` + `UPDATE … WHERE stock >= n` + rollback |
| Kardex | `movimientos_stock` ('salida', "Pedido #id") |
| Validaciones servidor | `model/Validador.php` (cédula mod-10, teléfono, correo, nombres) |
| Seguridad | CSRF (`Csrf.php`), consultas preparadas, `e()` contra XSS, cookie HttpOnly/SameSite, código de pedido aleatorio, errores técnicos solo al log |
| LOPDP | `tienda/privacidad.php`, casilla de consentimiento obligatoria (guardada en `pedidos.acepta_privacidad`), minimización (sin datos de tarjeta), API sin datos personales |

## Pruebas manuales sugeridas (para el video)
1. Comprar con pago aprobado → baja el stock y aparece en Pedidos web y en el kardex.
2. Pago rechazado → pedido "rechazado", stock intacto, carrito conservado.
3. Pedir más que el stock → mensaje "Stock insuficiente".
4. Cédula inválida / sin marcar consentimiento → errores en el formulario.
5. Enviar un formulario sin token CSRF → "Solicitud no válida".

## Lo que el equipo debe entregar aparte
BPMN del proceso de compra, matriz de riesgos, tabla de KPI (meta, frecuencia, decisión), dashboard publicado, política LOPDP en el informe. Deben poder explicar cada archivo línea por línea.
