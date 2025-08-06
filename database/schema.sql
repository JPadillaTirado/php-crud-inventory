-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 05-08-2025 a las 21:07:36
-- Versión del servidor: 10.6.22-MariaDB-cll-lve-log
-- Versión de PHP: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `padikxwr_bd_test`
--

DELIMITER $$
--
-- Procedimientos
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `categoria_id` int(7) NOT NULL,
  `categoria_nombre` varchar(50) NOT NULL,
  `categoria_ubicacion` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`categoria_id`, `categoria_nombre`, `categoria_ubicacion`) VALUES
(2, 'Juguetes', 'Pasillo 7, Estante G'),
(3, 'Libros', 'Pasillo 8, Estante H'),
(4, 'Jardinería', 'Pasillo 9, Estante I'),
(6, 'Granos', 'Pasillo 4, Estante H'),
(7, 'Fruver', 'Pasillo 4, Estante F');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `compra_id` int(15) NOT NULL,
  `compra_codigo` varchar(20) NOT NULL,
  `proveedor_id` int(10) NOT NULL,
  `usuario_id` int(10) NOT NULL,
  `compra_fecha` date NOT NULL,
  `compra_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compra_estado` enum('pendiente','recibida','cancelada') NOT NULL DEFAULT 'pendiente',
  `compra_observaciones` text DEFAULT NULL,
  `compra_fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `compra_fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`compra_id`, `compra_codigo`, `proveedor_id`, `usuario_id`, `compra_fecha`, `compra_total`, `compra_estado`, `compra_observaciones`, `compra_fecha_registro`, `compra_fecha_actualizacion`) VALUES
(1, 'ORD-2025-001', 1, 1, '2025-07-23', 450000.00, 'recibida', NULL, '2025-07-27 02:29:25', '2025-07-29 00:25:19'),
(2, 'ORD-2025-002', 3, 1, '0000-00-00', 15400000.00, 'recibida', '', '2025-07-29 00:15:28', '2025-07-29 00:17:36'),
(3, 'ORD-2025-003', 3, 1, '0000-00-00', 1000000.00, 'recibida', '', '2025-07-29 00:26:50', '2025-07-29 00:26:58'),
(4, 'ORD-2025-004', 3, 1, '0000-00-00', 5000000.00, 'recibida', '', '2025-07-29 00:38:53', '2025-07-29 00:39:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

CREATE TABLE `detalle_compra` (
  `detalle_id` int(15) NOT NULL,
  `compra_id` int(15) NOT NULL,
  `producto_id` int(20) NOT NULL,
  `detalle_cantidad` int(10) NOT NULL,
  `detalle_precio_compra` decimal(10,2) NOT NULL,
  `detalle_subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `detalle_compra`
--

INSERT INTO `detalle_compra` (`detalle_id`, `compra_id`, `producto_id`, `detalle_cantidad`, `detalle_precio_compra`, `detalle_subtotal`) VALUES
(1, 1, 5, 10, 15000.00, 150000.00),
(2, 1, 9, 5, 40000.00, 200000.00),
(3, 1, 7, 20, 4000.00, 80000.00),
(4, 2, 1, 500, 20000.00, 10000000.00),
(5, 2, 5, 300, 18000.00, 5400000.00),
(6, 3, 7, 1000, 1000.00, 1000000.00),
(7, 4, 7, 1000, 5000.00, 5000000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_factura`
--

CREATE TABLE `detalle_factura` (
  `id` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `impuesto` decimal(5,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_factura`
--

INSERT INTO `detalle_factura` (`id`, `id_factura`, `id_producto`, `cantidad`, `precio_unitario`, `impuesto`) VALUES
(1, 1, 5, 1, 18000.00, 19.00),
(2, 2, 7, 2, 5000.00, 19.00),
(3, 3, 5, 1, 18000.00, 19.00),
(4, 4, 5, 1, 18000.00, 19.00),
(5, 5, 9, 5, 45000.00, 19.00),
(6, 6, 1, 1, 25789.00, 19.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `identificacion_cliente` varchar(50) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `identificacion_cliente`, `fecha`, `total`) VALUES
(1, '1003309265', '2025-07-09 04:04:13', 21420.00),
(2, '1233340483', '2025-07-20 16:07:03', 11900.00),
(3, '1233340483', '2025-07-20 18:29:29', 21420.00),
(4, '65468412', '2025-07-22 00:18:39', 21420.00),
(5, '1003309265', '2025-07-24 00:22:36', 267750.00),
(6, '1033002545', '2025-07-31 16:08:05', 30688.91);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_attempts`
--

CREATE TABLE `password_reset_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(70) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_reset_attempts`
--

INSERT INTO `password_reset_attempts` (`id`, `email`, `ip_address`, `attempt_time`, `success`, `user_agent`) VALUES
(19, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-21 03:59:00', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(20, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-21 04:00:15', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(21, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-21 04:01:28', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0'),
(22, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-21 04:04:14', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(23, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-22 00:12:51', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(24, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-24 00:11:49', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'),
(25, 'jorgepadilla7796@gmail.com', '206.84.81.209', '2025-07-27 02:20:26', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `expira_en`, `usado`, `ip_address`, `user_agent`, `created_at`, `used_at`) VALUES
(16, 1, '5f389e06216e70524aec1f97ba3477345ac7ec15e4e9bb05828c7c1bc5460ee5', '2025-07-20 23:09:00', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 03:59:00', '2025-07-21 03:59:48'),
(17, 1, '5db3045fd2a6a8508f8ff892bd4e5ebfe1dadc4ea43aa6523c39fdd17a1a5f13', '2025-07-20 23:10:15', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 04:00:15', '2025-07-21 04:01:28'),
(18, 1, 'd31abdc76769154f15751b71865f5856854b292c431ccc07014145e31b434575', '2025-07-20 23:11:28', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-21 04:01:28', '2025-07-21 04:04:14'),
(19, 1, '2f0789b96a83a5ea2c99c4a0ccb722ce0900d4a3f4602559c16a528d176ef883', '2025-07-20 23:14:14', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 04:04:14', '2025-07-22 00:12:51'),
(20, 1, 'f62bc44b86af2918a3470ea37bd7ed56b89a885a69fac835d509622e453f0841', '2025-07-21 19:22:51', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-22 00:12:51', '2025-07-22 00:14:00'),
(21, 1, 'b59b114262671ba1653bcd6add913f4ee07ee3b29d249109158c615798528c2e', '2025-07-23 19:21:49', 1, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 00:11:49', '2025-07-24 00:12:37'),
(22, 1, '5271092a5d91258e7ac5964bf763ca0e21b73b59a27dbf45bd5244c5805c29e8', '2025-07-26 21:30:26', 0, '206.84.81.209', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 02:20:26', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `producto_id` int(20) NOT NULL,
  `producto_codigo` varchar(70) NOT NULL,
  `producto_nombre` varchar(70) NOT NULL,
  `producto_precio` decimal(30,2) NOT NULL,
  `producto_precio_compra` decimal(10,2) DEFAULT 0.00,
  `producto_stock` int(25) NOT NULL,
  `producto_stock_minimo` int(10) DEFAULT 5,
  `producto_foto` varchar(500) NOT NULL,
  `categoria_id` int(7) NOT NULL,
  `usuario_id` int(10) NOT NULL,
  `producto_impuesto` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`producto_id`, `producto_codigo`, `producto_nombre`, `producto_precio`, `producto_precio_compra`, `producto_stock`, `producto_stock_minimo`, `producto_foto`, `categoria_id`, `usuario_id`, `producto_impuesto`) VALUES
(1, 'test*', 'Arroz Diana 5k', 25789.00, 0.00, 1000, 5, 'prod_6839177f844f7.jpg', 7, 1, 0.00),
(5, '654', 'Aceite Clarita', 18000.00, 0.00, 653, 5, 'prod_6839176f9cc82.jpg', 6, 1, 0.00),
(7, '516', 'Papaya', 5000.00, 0.00, 3272, 5, 'prod_685c7b5bcc8d5.jpg', 6, 1, 0.00),
(8, '658', 'Garbanzo', 4000.00, 0.00, 312, 5, 'prod_685c7b51d41af.jpg', 2, 1, 0.00),
(9, '854', 'Azucar Manuelita', 45000.00, 0.00, 30, 5, 'prod_686ab707a902c.jpg', 6, 1, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `proveedor_id` int(10) NOT NULL,
  `proveedor_nombre` varchar(100) NOT NULL,
  `proveedor_rut_nit` varchar(20) NOT NULL,
  `proveedor_telefono` varchar(20) DEFAULT NULL,
  `proveedor_email` varchar(70) DEFAULT NULL,
  `proveedor_direccion` varchar(200) DEFAULT NULL,
  `proveedor_ciudad` varchar(50) DEFAULT NULL,
  `proveedor_contacto` varchar(100) DEFAULT NULL,
  `proveedor_estado` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`proveedor_id`, `proveedor_nombre`, `proveedor_rut_nit`, `proveedor_telefono`, `proveedor_email`, `proveedor_direccion`, `proveedor_ciudad`, `proveedor_contacto`, `proveedor_estado`, `proveedor_fecha_registro`) VALUES
(1, 'Distribuidora Los Andes S.A.S', '900.123.456-7', '+57 301 234 5678', 'ventas@losandes.com', 'Calle 45 #23-67', 'Bogotá', 'María González', 1, '2025-07-27 02:29:25'),
(2, 'Alimentos del Valle', '800.987.654-3', '+57 318 765 4321', 'pedidos@alimentosvalle.com', 'Carrera 15 #89-12', 'Cali', 'Carlos Rodríguez', 1, '2025-07-27 02:29:25'),
(3, 'Productos Agrícolas Ltda', '700.555.888-9', '+57 300 987 6543', 'info@agricolas.com', 'Avenida 30 #15-89', 'Medellín', 'Ana Torres', 1, '2025-07-27 02:29:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `usuario_id` int(10) NOT NULL,
  `usuario_nombre` varchar(40) NOT NULL,
  `usuario_apellido` varchar(40) NOT NULL,
  `usuario_usuario` varchar(20) NOT NULL,
  `usuario_clave` varchar(200) NOT NULL,
  `usuario_email` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`usuario_id`, `usuario_nombre`, `usuario_apellido`, `usuario_usuario`, `usuario_clave`, `usuario_email`) VALUES
(1, 'Jorge', 'Padilla', 'JPadilla', '$2y$10$PTdUCkf6fR4Zdba4e9NZuOSSimA3bJ5tHA9vw8eE41gJx46Kn6hzS', 'jorgepadilla7796@gmail.com'),
(2, 'Erick', 'Padilla', 'EPadilla', '$2y$10$BXUeM.xKu1MMashfEMCYA.SdXfnKb3wg7cmg482sFdiYfvy0m7mqq', 'erickjpm2012zte@gmail.com');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`categoria_id`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`compra_id`),
  ADD UNIQUE KEY `compra_codigo` (`compra_codigo`),
  ADD KEY `fk_compra_proveedor` (`proveedor_id`),
  ADD KEY `fk_compra_usuario` (`usuario_id`),
  ADD KEY `compra_fecha` (`compra_fecha`),
  ADD KEY `compra_estado` (`compra_estado`),
  ADD KEY `idx_compra_proveedor_fecha` (`proveedor_id`,`compra_fecha`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`detalle_id`),
  ADD KEY `fk_detalle_compra` (`compra_id`),
  ADD KEY `fk_detalle_producto` (`producto_id`),
  ADD KEY `idx_detalle_compra_producto` (`compra_id`,`producto_id`);

--
-- Indices de la tabla `detalle_factura`
--
ALTER TABLE `detalle_factura`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_ip` (`email`,`ip_address`),
  ADD KEY `idx_attempt_time` (`attempt_time`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_expira_en` (`expira_en`),
  ADD KEY `idx_usado` (`usado`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`producto_id`),
  ADD KEY `producto_codigo` (`producto_codigo`),
  ADD KEY `fk_producto_categoria` (`categoria_id`),
  ADD KEY `fk_producto_usuario` (`usuario_id`),
  ADD KEY `idx_producto_stock_minimo` (`producto_stock`,`producto_stock_minimo`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`proveedor_id`),
  ADD UNIQUE KEY `proveedor_rut_nit` (`proveedor_rut_nit`),
  ADD KEY `proveedor_nombre` (`proveedor_nombre`),
  ADD KEY `proveedor_estado` (`proveedor_estado`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`usuario_id`),
  ADD KEY `usuario_usuario` (`usuario_usuario`),
  ADD KEY `usuario_email` (`usuario_email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `categoria_id` int(7) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `compra_id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `detalle_id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `detalle_factura`
--
ALTER TABLE `detalle_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `producto_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `proveedor_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `usuario_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`proveedor_id`),
  ADD CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`usuario_id`);

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`compra_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`);

--
-- Filtros para la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`usuario_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
