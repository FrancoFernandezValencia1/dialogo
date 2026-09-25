-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-09-2026 a las 17:41:34
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dialogoydesarrollo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autores`
--

CREATE TABLE `autores` (
  `autor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `usar_nickname` tinyint(1) DEFAULT 0,
  `ap_paterno` varchar(150) DEFAULT NULL,
  `ap_materno` varchar(150) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `autores`
--

INSERT INTO `autores` (`autor_id`, `user_id`, `nombre`, `nickname`, `usar_nickname`, `ap_paterno`, `ap_materno`, `biografia`, `foto_url`, `created_at`) VALUES
(1, 1, 'Juan', 'JPG', 0, 'Perez García', '', 'Periodista ', NULL, '2026-09-07 00:40:06'),
(2, NULL, 'franco', 'F', 0, NULL, NULL, 'asddffggffdddassss', NULL, '2026-09-07 04:15:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletines`
--

CREATE TABLE `boletines` (
  `boletin_id` int(11) NOT NULL,
  `numero_boletin` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `resumen` text DEFAULT NULL,
  `foto_portada_url` varchar(500) DEFAULT NULL,
  `archivo_pdf_url` varchar(500) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `boletines`
--

INSERT INTO `boletines` (`boletin_id`, `numero_boletin`, `titulo`, `resumen`, `foto_portada_url`, `archivo_pdf_url`, `fecha_publicacion`, `usuario_id`, `created_at`, `vistas`) VALUES
(3, 1, 'Boletin prueba', 'Boletin de Prueba', 'bol_port_1789100969.png', 'bol_doc_1789097113.pdf', '2026-09-07', 1, '2026-09-07 06:55:34', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

CREATE TABLE `fotos` (
  `foto_id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `url_foto` varchar(500) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fotos`
--

INSERT INTO `fotos` (`foto_id`, `titulo`, `url_foto`, `descripcion`, `fecha_publicacion`, `usuario_id`, `created_at`, `vistas`) VALUES
(1, 'reportaje prueba', 'rep_1788749235_0_806.png', 'Foto del reportaje: reportaje prueba', '2026-09-07', 1, '2026-09-07 07:47:15', 0),
(2, 'reportaje prueba', 'images/fotos/rep_1789097198_0_287.png', 'Foto del reportaje: reportaje prueba', '2026-09-07', 1, '2026-09-11 03:26:38', 0),
(3, 'reportaje prueba', 'images/fotos/rep_1789097666_0_360.png', 'Foto del reportaje: reportaje prueba', '2026-09-07', 1, '2026-09-11 03:34:26', 0),
(4, 'reportaje prueba', 'images/fotos/rep_1789097733_0_220.png', 'Foto del reportaje: reportaje prueba', '2026-09-07', 1, '2026-09-11 03:35:33', 0),
(5, 'Primer Reportaje', 'images/fotos/rep_1789098172_0_763.jpeg', 'Foto del reportaje: Primer Reportaje', '2026-09-06', 1, '2026-09-11 03:42:52', 0),
(6, 'Primer Reportaje', 'images/fotos/rep_1789098626_0_196.jpeg', 'Foto del reportaje: Primer Reportaje', '2026-09-06', 1, '2026-09-11 03:50:26', 0),
(7, 'Primer Reportaje', 'images/fotos/rep_1789099014_0_637.png', 'Foto del reportaje: Primer Reportaje', '2026-09-06', 1, '2026-09-11 03:56:54', 0),
(8, 'reportaje prueba2', 'images/fotos/rep_1789099065_0_808.png', 'Foto del reportaje: reportaje prueba2', '2026-09-11', 1, '2026-09-11 03:57:45', 0),
(9, 'reportaje prueba2', 'images/fotos/rep_1789099674_0_507.png', 'Foto del reportaje: reportaje prueba2', '2026-09-11', 1, '2026-09-11 04:07:54', 0),
(10, 'reportaje prueba 3', 'images/fotos/rep_1789145486_0_810.png', 'Foto del reportaje: reportaje prueba 3', '2026-09-11', 1, '2026-09-11 16:51:26', 0),
(11, 'reportaje prueba 3', 'images/fotos/rep_1789145527_0_131.png', 'Foto del reportaje: reportaje prueba 3', '2026-09-11', 1, '2026-09-11 16:52:07', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `noticia_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `resumen` text DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `link_externo` varchar(500) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `noticias`
--

INSERT INTO `noticias` (`noticia_id`, `titulo`, `resumen`, `foto_url`, `link_externo`, `fecha_publicacion`, `usuario_id`, `created_at`, `vistas`) VALUES
(1, 'noticia prueba', 'El excongresista dijo estar capacitado para este encargo, pues fue agregado militar en Buenos Aires. Asimismo, dijo no haber tenido injerencia en la decisión del Gobierno de Keiko Fujimori.', 'not_1788736540.png', 'https://rpp.pe/politica/gobierno/tubino-tras-ser-designado-embajador-en-argentina-no-hay-ningun-tipo-de-favorecimiento-sino-buscar-lo-mejor-para-el-pais-noticia-1705900', '2026-09-07', 1, '2026-09-07 04:15:40', 0),
(2, 'Titulo prueba 3', 'asdasdasdasdadads', 'not_1789150717.png', '', '2026-09-11', 1, '2026-09-11 18:18:37', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pdfs`
--

CREATE TABLE `pdfs` (
  `pdf_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pdfs`
--

INSERT INTO `pdfs` (`pdf_id`, `titulo`, `url_archivo`, `descripcion`, `usuario_id`, `created_at`, `vistas`) VALUES
(1, 'Boletín N° 1 - Boletin prueba', 'bol_doc_1788746134.pdf', 'Boletin de Prueba', 1, '2026-09-07 06:55:34', 0),
(2, 'Boletín N° 1 - Boletin prueba', 'bol_doc_1789097113.pdf', 'Boletin de Prueba', 1, '2026-09-11 03:25:13', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `podcasts`
--

CREATE TABLE `podcasts` (
  `podcast_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `url_embed` varchar(500) NOT NULL,
  `duracion_segundos` int(11) DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `podcasts`
--

INSERT INTO `podcasts` (`podcast_id`, `titulo`, `descripcion`, `url_embed`, `duracion_segundos`, `imagen_url`, `fecha_publicacion`, `usuario_id`, `created_at`, `vistas`) VALUES
(1, 'Buen dia Prueba', 'Cancion prueba', 'https://open.spotify.com/embed/track/5pmAJubozTgqJslFEiBIgK', NULL, 'uploads/podcasts/1789102643_portada_6aa38a33dbcd8.png', '2026-09-06', 1, '2026-09-07 04:00:16', 0),
(2, 'Podcast 2', 'cancion prueba 2', 'uploads/podcasts/1789103009_audio_6aa38ba1caca3.mp3', NULL, 'uploads/podcasts/1789103009_portada_6aa38ba1caf3b.png', '2026-09-11', 1, '2026-09-11 05:03:29', 0),
(3, 'Podcast subido como video local', 'subido como local', 'uploads/podcasts/1789145383_audio_6aa4312769129.mp3', NULL, 'https://images.genius.com/fe128d84e310c799d66461fe6077b43c.1000x1000x1.png', '2026-09-11', 1, '2026-09-11 16:49:43', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportajes`
--

CREATE TABLE `reportajes` (
  `reportaje_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `resumen_corto` text DEFAULT NULL,
  `desarrollo` text DEFAULT NULL,
  `foto_id` int(11) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `es_destacado` tinyint(1) DEFAULT 0,
  `autor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportajes`
--

INSERT INTO `reportajes` (`reportaje_id`, `titulo`, `resumen_corto`, `desarrollo`, `foto_id`, `fecha_publicacion`, `es_destacado`, `autor_id`, `usuario_id`, `created_at`, `updated_at`, `vistas`) VALUES
(1, 'Primer Reportaje', 'Los espacios verdes en las ciudades son cada vez más escasos. Sin embargo, algunas escuelas han decidido cambiar esta realidad. El colegio San Martín, ubicado en el centro de la ciudad, transformó un patio gris en un huerto escolar productivo. ', 'Los espacios verdes en las ciudades son cada vez más escasos. Sin embargo, algunas escuelas han decidido cambiar esta realidad. El colegio San Martín, ubicado en el centro de la ciudad, transformó un patio gris en un huerto escolar productivo. Este proyecto busca enseñar a los estudiantes el valor de la naturaleza y la importancia de una alimentación saludable.DesarrolloEl programa comenzó hace seis meses. Los profesores de ciencias lideraron la iniciativa con la ayuda de los alumnos de secundaria. Primero, limpiaron el terreno acumulado de escombros. Luego, construyeron bancales de madera y prepararon la tierra con abono orgánico elaborado en la misma institución.Los estudiantes participan activamente en el cuidado diario. Se turnan para regar las plantas, retirar las malas hierbas y medir el crecimiento de hortalizas como lechugas, tomates y zanahorias. María, una alumna de doce años, comenta entusiasmada: «Es increíble ver cómo una semilla pequeña se convierte en un alimento que podemos comer en casa». Además de la botánica, el huerto se integró en las clases de matemáticas y arte, donde los jóvenes calculan el consumo de agua y dibujan los ciclos de vida de los insectos polinizadores.ConclusiónEl éxito del huerto escolar demuestra que pequeños cambios generan grandes impactos educativos. Los niños no solo aprenden sobre ecología, sino también sobre paciencia, trabajo en equipo y responsabilidad. La dirección del colegio planea expandir el área verde el próximo año para incluir plantas medicinales y un estanque artificial.', 5, '2026-09-06', 0, 1, 1, '2026-09-07 00:40:06', '2026-09-11 04:07:09', 0),
(7, 'reportaje prueba', 'adadasdsadad', 'asdasdadasda', 1, '2026-09-07', 1, 2, 1, '2026-09-07 07:47:15', '2026-09-11 04:07:09', 0),
(8, 'reportaje prueba2', 'adsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'adsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaadsavsdaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 8, '2026-09-11', 0, 2, 1, '2026-09-11 03:57:45', '2026-09-11 04:07:09', 0),
(9, 'reportaje prueba 3', 'asdadasdasdasdasdadasd', 'asdddddddddddddddddddasdasdasdasdadasdasdadsadassddasdsa', NULL, '2026-09-11', 0, 1, 1, '2026-09-11 16:51:26', '2026-09-11 16:51:26', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportajes_fotos`
--

CREATE TABLE `reportajes_fotos` (
  `foto_id` int(11) NOT NULL,
  `reportaje_id` int(11) NOT NULL,
  `url_foto` varchar(500) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `descripcion` varchar(255) DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportajes_fotos`
--

INSERT INTO `reportajes_fotos` (`foto_id`, `reportaje_id`, `url_foto`, `orden`, `descripcion`, `es_principal`) VALUES
(5, 7, 'rep_1788749235_0_806.png', 0, NULL, 1),
(6, 7, 'images/fotos/rep_1789097198_0_287.png', 0, NULL, 1),
(7, 7, 'images/fotos/rep_1789097666_0_360.png', 0, NULL, 1),
(8, 7, 'images/fotos/rep_1789097733_0_220.png', 0, NULL, 1),
(9, 1, 'images/fotos/rep_1789098172_0_763.jpeg', 0, NULL, 1),
(10, 1, 'images/fotos/rep_1789098626_0_196.jpeg', 0, NULL, 1),
(11, 1, 'images/fotos/rep_1789099014_0_637.png', 0, NULL, 1),
(12, 8, 'images/fotos/rep_1789099065_0_808.png', 0, NULL, 1),
(13, 8, 'images/fotos/rep_1789099674_0_507.png', 0, NULL, 1),
(14, 9, 'images/fotos/rep_1789145486_0_810.png', 0, NULL, 1),
(15, 9, 'images/fotos/rep_1789145527_0_131.png', 0, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `user_id` int(11) NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `rol` varchar(50) NOT NULL DEFAULT 'periodista',
  `activo` tinyint(1) DEFAULT 1,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`user_id`, `nombre_completo`, `email`, `password_hash`, `telefono`, `avatar_url`, `rol`, `activo`, `token_recuperacion`, `token_expira`, `created_at`, `updated_at`) VALUES
(1, 'Administrador General', 'admin@', '$2y$10$X3zmBEWyc8KFRBYe6VjoWu8om9Wpul3dFDOedz3W0ckZsRXXaLG.S', NULL, NULL, 'admin', 1, NULL, NULL, '2026-09-07 00:40:05', '2026-09-10 21:51:05'),
(2, 'Franco', '022100691k@uandina.edu.pe', '$2y$10$JrVgFE1Fiddc57vSl2DoWeM8xQ0dm4G2pzLUZFlHC4DfPao5MxYE.', '977873734', '1789077916_6aa3299c444aa.png', 'periodista', 1, '61829457dd8dc887424e0316212673f78160583edb8d73819d621c6822174c3e', '2026-09-11 21:17:27', '2026-09-10 21:51:31', '2026-09-11 18:17:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `video_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `url_embed` varchar(500) NOT NULL,
  `duracion_segundos` int(11) DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `videos`
--

INSERT INTO `videos` (`video_id`, `titulo`, `descripcion`, `url_embed`, `duracion_segundos`, `imagen_url`, `fecha_publicacion`, `usuario_id`, `created_at`, `vistas`) VALUES
(1, 'video prueba', 'Video pruea secuestro', 'https://www.youtube.com/embed/ReMfQs8Gs5I', NULL, '', '2026-09-07', 1, '2026-09-07 04:05:34', 0),
(2, 'Prueba video local', 'cancion de prueba para videos subidos en local', 'uploads/videos/1789145313_vid_6aa430e1a6a32.mp4', NULL, 'https://images.genius.com/fe128d84e310c799d66461fe6077b43c.1000x1000x1.png', '2026-09-11', 1, '2026-09-11 16:48:33', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`autor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `boletines`
--
ALTER TABLE `boletines`
  ADD PRIMARY KEY (`boletin_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`foto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`noticia_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pdfs`
--
ALTER TABLE `pdfs`
  ADD PRIMARY KEY (`pdf_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `podcasts`
--
ALTER TABLE `podcasts`
  ADD PRIMARY KEY (`podcast_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reportajes`
--
ALTER TABLE `reportajes`
  ADD PRIMARY KEY (`reportaje_id`),
  ADD KEY `autor_id` (`autor_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  ADD PRIMARY KEY (`foto_id`),
  ADD KEY `reportaje_id` (`reportaje_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`video_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `autores`
--
ALTER TABLE `autores`
  MODIFY `autor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `boletines`
--
ALTER TABLE `boletines`
  MODIFY `boletin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `foto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `noticia_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pdfs`
--
ALTER TABLE `pdfs`
  MODIFY `pdf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `podcasts`
--
ALTER TABLE `podcasts`
  MODIFY `podcast_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reportajes`
--
ALTER TABLE `reportajes`
  MODIFY `reportaje_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  MODIFY `foto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `video_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `autores`
--
ALTER TABLE `autores`
  ADD CONSTRAINT `autores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `boletines`
--
ALTER TABLE `boletines`
  ADD CONSTRAINT `boletines_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD CONSTRAINT `fotos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pdfs`
--
ALTER TABLE `pdfs`
  ADD CONSTRAINT `pdfs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `podcasts`
--
ALTER TABLE `podcasts`
  ADD CONSTRAINT `podcasts_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportajes`
--
ALTER TABLE `reportajes`
  ADD CONSTRAINT `reportajes_ibfk_1` FOREIGN KEY (`autor_id`) REFERENCES `autores` (`autor_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reportajes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  ADD CONSTRAINT `reportajes_fotos_ibfk_1` FOREIGN KEY (`reportaje_id`) REFERENCES `reportajes` (`reportaje_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
