-- Estructura de la Base de Datos `gastos_db`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Tabla `usuarios`
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla `categorias`
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcado de datos iniciales para `categorias`
INSERT INTO `categorias` (`nombre`) VALUES
('Otros'),
('Compra de Productos OTC y Parafarmacia'),
('Cuidado Personal y Cosmética'),
('Medicamentos con Receta'),
('Insumos Médicos Desechables');

-- Tabla `gastos`
CREATE TABLE `gastos` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT 0,
  `forma_pago` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ejemplo de volcado de datos para `usuarios` (Contraseñas hasheadas: 123456, etc.)
INSERT INTO `usuarios` (`nombre`, `email`, `password`) VALUES
('Diego', 'diegoxks12@gmail.com', '$2y$10$Z.ODcdX9z9lICF6GS78mae7.cxTD9pMYKXP6gZSbGE.ZADoKwOhYK'),
('Andres', 'daytoxks76@gmail.com', '$2y$10$UWVtcFWRyOllk2MActdrX.h74bwmuJmBpph6zd4o00jmi52iRiLVS'),
('yacnely', 'yacnelyrondon14@gmail.com', '$2y$10$r5ceDhoy4x7PXXsBOKmOW.ZAHYoqX2JHvONbyyklHnQIQqqKGbN1G');

-- Ejemplo de volcado de datos para `gastos`
INSERT INTO `gastos` (`usuario_id`, `categoria_id`, `fecha`, `monto`, `pagado`, `forma_pago`, `descripcion`) VALUES
(1, 2, '2025-10-21', 30.00, 1, 'Tarjeta de Credito', 'buen cliente'),
(2, 2, '2025-10-21', 300.00, 1, 'Cheque', 'fffffff'),
(1, 5, '2025-10-07', 0.64, 1, 'Tarjeta de Credito', 'putffffff'),
(3, 4, '2025-10-22', 5.50, 1, 'Transferencia', 'paquetes');

COMMIT;
