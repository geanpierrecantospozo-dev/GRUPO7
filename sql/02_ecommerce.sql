-- ===========================================================
-- 02_ecommerce.sql — Tablas del flujo de comercio electrónico
-- Sistemas de Información Avanzada · Entrega 1
-- -----------------------------------------------------------
-- IMPORTAR DESPUÉS de sia_base.sql, en la MISMA base de datos
-- (phpMyAdmin → seleccionar la base → Importar).
-- Solo AGREGA tablas nuevas: no modifica ni borra las existentes.
-- Se puede volver a importar sin problema (usa IF NOT EXISTS).
--
-- Flujo: catálogo → carrito (sesión) → checkout (pago SIMULADO)
--        → pedido registrado + stock descontado + kardex.
-- No se guarda ningún dato de tarjeta: el pago es una simulación.
-- ===========================================================

SET NAMES utf8mb4;

-- Un pedido pertenece a un cliente (tabla clientes, ya existente).
-- Los intentos con pago rechazado también se guardan (estado 'rechazado')
-- para poder medir la tasa de rechazo en el dashboard.
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` char(16) NOT NULL,                       -- código aleatorio para ver el pedido sin adivinar números
  `id_cliente` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,                    -- 'pagado' | 'rechazado'
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `direccion_envio` varchar(200) NOT NULL,
  `ciudad` varchar(80) NOT NULL,
  `acepta_privacidad` tinyint(1) NOT NULL DEFAULT 0, -- consentimiento LOPDP marcado en el checkout
  `fecha_pedido` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `pedidos_cliente_fk` (`id_cliente`),
  KEY `pedidos_fecha_idx` (`fecha_pedido`),
  CONSTRAINT `pedidos_cliente_fk` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Líneas del pedido. Se copia el nombre y el precio del momento de la compra,
-- así el historial no cambia si luego se edita el producto.
CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_items_pedido_fk` (`id_pedido`),
  KEY `pedido_items_producto_fk` (`id_producto`),
  CONSTRAINT `pedido_items_pedido_fk` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_items_producto_fk` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Resultado del pago SIMULADO (sin números de tarjeta).
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `metodo` varchar(40) NOT NULL,
  `estado` varchar(12) NOT NULL,                    -- 'aprobado' | 'rechazado'
  `referencia` varchar(30) NOT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagos_pedido_uq` (`id_pedido`),
  CONSTRAINT `pagos_pedido_fk` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
