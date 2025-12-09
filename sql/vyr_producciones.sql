--
-- Base de datos: `vyr_producciones`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_bloquear_admin` (IN `p_email` VARCHAR(150))   BEGIN
    UPDATE administradores
    SET estado = 'inactivo'
    WHERE email = p_email;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_bloquear_familia` (IN `p_email` VARCHAR(150))   BEGIN
    UPDATE familias
    SET estado = 'inactivo'
    WHERE email = p_email;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_buscar_usuario_por_email` (IN `p_email` VARCHAR(150))   BEGIN
    -- Buscar en administradores
    SELECT 'admin' AS tipo_usuario, id, estado
    FROM administradores
    WHERE email = p_email
    LIMIT 1;

    -- Limpiar resultados múltiples
    DO SLEEP(0);

    -- Buscar en familias
    SELECT 'familia' AS tipo_usuario, id, estado
    FROM familias
    WHERE email = p_email
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eventos_actualizar` (IN `p_id` INT, IN `p_nombre` VARCHAR(100), IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_lugar` VARCHAR(150), IN `p_estado` ENUM('activo','inactivo'))   BEGIN
    UPDATE eventos
    SET nombre = p_nombre,
        fecha_inicio = p_fecha_inicio,
        fecha_fin = p_fecha_fin,
        lugar = p_lugar,
        estado = p_estado
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eventos_eliminar` (IN `p_id` INT)   BEGIN
    DELETE FROM eventos WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eventos_insertar` (IN `p_nombre` VARCHAR(100), IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_lugar` VARCHAR(150), IN `p_estado` ENUM('activo','inactivo'))   BEGIN
    INSERT INTO eventos(nombre, fecha_inicio, fecha_fin, lugar, estado)
    VALUES(p_nombre, p_fecha_inicio, p_fecha_fin, p_lugar, p_estado);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eventos_listar` ()   BEGIN
    SELECT 
        id,
        nombre,
        fecha_inicio,
        fecha_fin,
        lugar,
        estado
    FROM eventos
    ORDER BY fecha_inicio DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_evento_actualizar` (IN `p_id` INT, IN `p_nombre` VARCHAR(200), IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_lugar` VARCHAR(200), IN `p_estado` ENUM('activo','inactivo','finalizado'))   BEGIN
    UPDATE eventos
    SET nombre = p_nombre,
        fecha_inicio = p_fecha_inicio,
        fecha_fin = p_fecha_fin,
        lugar = p_lugar,
        estado = p_estado
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_evento_crear` (IN `p_nombre` VARCHAR(200), IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_lugar` VARCHAR(200))   BEGIN
    DECLARE v_estado ENUM('activo','inactivo');

    IF p_fecha_fin < CURRENT_DATE() THEN
        SET v_estado = 'inactivo';
    ELSE
        SET v_estado = 'activo';
    END IF;

    INSERT INTO eventos(nombre, fecha_inicio, fecha_fin, lugar, estado)
    VALUES (p_nombre, p_fecha_inicio, p_fecha_fin, p_lugar, v_estado);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_evento_eliminar` (IN `p_id` INT)   BEGIN
    DELETE FROM eventos
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_familia_actualizar` (IN `p_id` INT, IN `p_tipo_documento` ENUM('dni','ce'), IN `p_numero_documento` VARCHAR(20), IN `p_nombres` VARCHAR(100), IN `p_apellidos` VARCHAR(100), IN `p_email` VARCHAR(150), IN `p_password` VARCHAR(255), IN `p_evento_id` INT, IN `p_nombre_familiar` VARCHAR(100), IN `p_apellidos_familiar` VARCHAR(150), IN `p_estado` ENUM('activo','inactivo'))   BEGIN
    IF p_password IS NOT NULL AND p_password <> '' THEN
        UPDATE familias
        SET tipo_documento = p_tipo_documento,
            numero_documento = p_numero_documento,
            nombres = p_nombres,
            apellidos = p_apellidos,
            email = p_email,
            password = p_password,
            evento_id = p_evento_id,
            nombre_familiar = p_nombre_familiar,
            apellidos_Familiar = p_apellidos_familiar,
            estado = p_estado
        WHERE id = p_id;
    ELSE
        UPDATE familias
        SET tipo_documento = p_tipo_documento,
            numero_documento = p_numero_documento,
            nombres = p_nombres,
            apellidos = p_apellidos,
            email = p_email,
            evento_id = p_evento_id,
            nombre_familiar = p_nombre_familiar,
            apellidos_Familiar = p_apellidos_familiar,
            estado = p_estado
        WHERE id = p_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_familia_eliminar` (IN `p_id` INT)   BEGIN
    DELETE FROM familias WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_familia_insertar` (IN `p_tipo_documento` ENUM('dni','ce'), IN `p_numero_documento` VARCHAR(20), IN `p_nombres` VARCHAR(100), IN `p_apellidos` VARCHAR(100), IN `p_email` VARCHAR(150), IN `p_password` VARCHAR(255), IN `p_evento_id` INT, IN `p_nombre_familiar` VARCHAR(100), IN `p_apellidos_familiar` VARCHAR(150))   BEGIN
    INSERT INTO familias (
        tipo_documento, numero_documento, nombres, apellidos,
        email, password, evento_id, nombre_familiar, apellidos_Familiar,
        estado, intentos
    ) VALUES (
        p_tipo_documento, p_numero_documento, p_nombres, p_apellidos,
        p_email, p_password, p_evento_id, p_nombre_familiar, p_apellidos_familiar,
        'activo', 0
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_events` ()   BEGIN
  SELECT id AS ID_correlativo, nombre, fecha_inicio, fecha_fin, lugar, estado
  FROM eventos
  ORDER BY fecha_inicio DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_family_by_email` (IN `p_correo` VARCHAR(150))   BEGIN
  SELECT *
  FROM familias
  WHERE correo = p_correo
  LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_incrementar_intentos` (IN `p_email` VARCHAR(150), IN `p_es_admin` TINYINT)   BEGIN
    IF p_es_admin = 1 THEN
        UPDATE administradores
        SET intentos = intentos + 1
        WHERE email = p_email;
    ELSE
        UPDATE familias
        SET intentos = intentos + 1
        WHERE email = p_email;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_listar_eventos` ()   BEGIN
  SELECT id, nombre, fecha_inicio, fecha_fin, lugar, estado
  FROM eventos
  ORDER BY fecha_inicio DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_listar_eventos_activos` ()   BEGIN
    SELECT id, nombre, fecha_inicio, fecha_fin
    FROM eventos
    WHERE estado = 'activo' AND fecha_fin >= CURDATE()
    ORDER BY fecha_inicio DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_listar_familias` (IN `p_evento_id` INT, IN `p_texto` VARCHAR(200))   BEGIN
    SELECT
        f.id,
        f.tipo_documento,
        f.numero_documento,
        f.nombres,
        f.apellidos,
        CONCAT(f.nombres,' ',f.apellidos) AS nombre_completo,
        f.email,
        f.evento_id,
        e.nombre AS evento_nombre,
        f.nombre_familiar,
        f.apellidos_Familiar,
        f.estado
    FROM familias f
    LEFT JOIN eventos e ON e.id = f.evento_id
    WHERE (p_evento_id = 0 OR f.evento_id = p_evento_id)
      AND (p_texto = '' OR CONCAT(f.nombres,' ',f.apellidos) LIKE CONCAT('%', p_texto, '%'))
    ORDER BY f.fecha_creacion DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_admin` (IN `p_email` VARCHAR(150))   BEGIN
    SELECT 
        id,
        nombre,
        email,
        password,
        estado,
        intentos
    FROM administradores
    WHERE email = p_email
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_admin_estado` (IN `p_email` VARCHAR(150))   BEGIN
    SELECT estado
    FROM administradores
    WHERE email = p_email
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_familia` (IN `p_email` VARCHAR(150))   BEGIN
    SELECT 
        id,
        tipo_documento,
        numero_documento,
        nombres,
        apellidos,
        email,
        password,
        evento_id,
        nombre_familiar,
        apellidos_familiar,
        estado,
        intentos
    FROM familias
    WHERE email = p_email
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_evento` (IN `p_id` INT)   BEGIN
    SELECT 
        id,
        nombre,
        fecha_inicio,
        fecha_fin,
        lugar,
        estado,
        descripcion,
        fecha_creacion,
        fecha_actualizacion
    FROM eventos
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_familia` (IN `p_id` INT)   BEGIN
    SELECT
        id, tipo_documento, numero_documento,
        nombres, apellidos, email, password,
        evento_id, nombre_familiar, apellidos_Familiar, estado
    FROM familias
    WHERE id = p_id
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_register_family` (IN `p_tipo_documento` VARCHAR(50), IN `p_num_documento` VARCHAR(50), IN `p_nombre` VARCHAR(100), IN `p_apellidos` VARCHAR(100), IN `p_correo` VARCHAR(150), IN `p_password_hash` VARCHAR(255), IN `p_evento_id` INT, IN `p_nombre_familiar` VARCHAR(150))   BEGIN
  INSERT INTO familias (tipo_documento, num_documento, nombre, apellidos, correo, password_hash, evento_id, nombre_familiar, creado_en)
  VALUES (p_tipo_documento, p_num_documento, p_nombre, p_apellidos, p_correo, p_password_hash, p_evento_id, p_nombre_familiar, NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_resetear_password_familia` (IN `p_email` VARCHAR(150), IN `p_newpass` VARCHAR(255))   BEGIN
    -- Solo actualizar si existe y está activo
    UPDATE familias
    SET password = p_newpass, intentos = 0
    WHERE email = p_email AND estado = 'activo';
    SELECT ROW_COUNT() AS actualizado;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reset_intentos` (IN `p_email` VARCHAR(150), IN `p_es_admin` TINYINT)   BEGIN
    IF p_es_admin = 1 THEN
        UPDATE administradores
        SET intentos = 0
        WHERE email = p_email;
    ELSE
        UPDATE familias
        SET intentos = 0
        WHERE email = p_email;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reset_password` (IN `p_email` VARCHAR(150), IN `p_newpass` VARCHAR(255), IN `p_tipo` ENUM('admin','familia'))   BEGIN
    IF p_tipo = 'admin' THEN
        UPDATE administradores
        SET password = p_newpass,
            intentos = 0
        WHERE email = p_email;
    ELSE
        UPDATE familias
        SET password = p_newpass,
            intentos = 0
        WHERE email = p_email;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_password_by_email` (IN `p_correo` VARCHAR(150), IN `p_password_hash` VARCHAR(255))   BEGIN
  UPDATE familias
  SET password_hash = p_password_hash, actualizado_en = NOW()
  WHERE correo = p_correo;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','editor') DEFAULT 'admin',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `nombre`, `email`, `password`, `rol`, `estado`, `intentos`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Victor Admin', 'admin@vyrproducciones.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo', 0, '2025-11-09 01:59:18', '2025-11-16 05:18:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `lugar` varchar(200) NOT NULL,
  `estado` enum('activo','finalizado') DEFAULT 'activo',
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `lugar`, `estado`, `descripcion`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Primera comunion I', '2025-11-16', '2025-11-22', 'Iglesia Concepcion Lima', 'activo', NULL, '2025-11-15 05:01:29', '2025-11-15 06:09:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familias`
--

CREATE TABLE `familias` (
  `id` int(11) NOT NULL,
  `tipo_documento` enum('dni','ce') NOT NULL,
  `numero_documento` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `evento_id` int(11) NOT NULL,
  `nombre_familiar` varchar(100) NOT NULL,
  `apellidos_Familiar` varchar(150) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `intentos` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `familias`
--

INSERT INTO `familias` (`id`, `tipo_documento`, `numero_documento`, `nombres`, `apellidos`, `email`, `password`, `evento_id`, `nombre_familiar`, `apellidos_Familiar`, `estado`, `fecha_creacion`, `fecha_actualizacion`, `intentos`) VALUES
(1, 'dni', '09940846', 'Marco Antonio', 'Quintana Camarena', 'marcoquintan@hotmail.com', '$2y$10$7Qd/DDANF1oK2QfgwAEjnex6pLx59pOtqvFma3omDQY4Eb668Tqyq', 1, 'Carlo Marco', 'Quintana Saavedra', 'activo', '2025-11-15 19:46:13', '2025-12-05 03:58:09', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

CREATE TABLE `fotos` (
  `id` int(11) NOT NULL,
  `usuario_familia_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tamaño` int(11) NOT NULL,
  `tipo_mime` varchar(50) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lienzos`
--

CREATE TABLE `lienzos` (
  `id` int(11) NOT NULL,
  `usuario_familia_id` int(11) NOT NULL,
  `plantilla_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `datos_lienzo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`datos_lienzo`)),
  `estado` enum('borrador','finalizado') DEFAULT 'borrador',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas_lienzos`
--

CREATE TABLE `plantillas_lienzos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estructura_grid` varchar(50) NOT NULL,
  `columnas` int(11) NOT NULL,
  `filas` int(11) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_usuario` enum('admin','familia') NOT NULL,
  `token_sesion` varchar(255) NOT NULL,
  `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 day),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `usuario_id`, `tipo_usuario`, `token_sesion`, `fecha_inicio`, `fecha_expiracion`, `ip_address`, `user_agent`, `activa`) VALUES
(1, 1, 'admin', '22a55551002157f7df5ebc25689380d86d8e8402f7fecb5b1cea119cceb286ca', '2025-11-09 02:00:02', '2025-12-09 02:00:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1),
(2, 1, 'admin', '115101c97768dc3b52b35b248ef57eae882c59d3ccb65d5e387a73dd4e65105d', '2025-11-09 21:40:17', '2025-12-09 21:40:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `familias`
--
ALTER TABLE `familias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_documento` (`numero_documento`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `evento_id` (`evento_id`);

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_familia_id` (`usuario_familia_id`);

--
-- Indices de la tabla `lienzos`
--
ALTER TABLE `lienzos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_familia_id` (`usuario_familia_id`),
  ADD KEY `plantilla_id` (`plantilla_id`);

--
-- Indices de la tabla `plantillas_lienzos`
--
ALTER TABLE `plantillas_lienzos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_sesion` (`token_sesion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `familias`
--
ALTER TABLE `familias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lienzos`
--
ALTER TABLE `lienzos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plantillas_lienzos`
--
ALTER TABLE `plantillas_lienzos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `familias`
--
ALTER TABLE `familias`
  ADD CONSTRAINT `familias_ibfk_1` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD CONSTRAINT `fotos_ibfk_1` FOREIGN KEY (`usuario_familia_id`) REFERENCES `familias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lienzos`
--
ALTER TABLE `lienzos`
  ADD CONSTRAINT `lienzos_ibfk_1` FOREIGN KEY (`usuario_familia_id`) REFERENCES `familias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lienzos_ibfk_2` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas_lienzos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
