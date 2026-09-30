-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2025 at 11:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parking_sv_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `owner_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_users`
--

CREATE TABLE `account_users` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','admin','viewer') DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `parking_id`, `folder_id`, `created_at`) VALUES
(54, 10, 34, NULL, '2025-08-31 17:44:23'),
(56, 9, 34, 5, '2025-09-01 12:16:44'),
(57, 9, 30, NULL, '2025-09-01 12:21:36');

-- --------------------------------------------------------

--
-- Table structure for table `favorite_folders`
--

CREATE TABLE `favorite_folders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `share_token` char(12) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `color` varchar(7) DEFAULT '#0C6FF9' COMMENT 'Color en formato HEX'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorite_folders`
--

INSERT INTO `favorite_folders` (`id`, `user_id`, `name`, `is_public`, `share_token`, `created_at`, `color`) VALUES
(2, 10, 'Parqueos cercanos al trabajo', 0, '0c9c8cb66a46', '2025-08-04 20:54:03', '#9c27b0'),
(3, 9, 'Parqueos para viajes', 0, '4a8af61ff92f', '2025-08-12 20:44:55', '#ff5722'),
(4, 22, 'Park', 0, '6e6d62b25bbb', '2025-08-13 15:11:49', '#9c27b0'),
(5, 9, 'Parqueos para viajes', 0, '7039f6424bf0', '2025-09-01 12:16:44', '#0c6ff9');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `department` varchar(50) NOT NULL,
  `municipality` varchar(50) NOT NULL,
  `street_address` varchar(50) NOT NULL,
  `reference_address` varchar(100) NOT NULL,
  `waze_link` varchar(1000) NOT NULL,
  `google_maps_link` varchar(1000) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `department`, `municipality`, `street_address`, `reference_address`, `waze_link`, `google_maps_link`, `latitude`, `longitude`) VALUES
(30, 'San Salvador', 'Soyapango', 'Calle de Soyapango', 'Frente a Plaza Mundo', 'https://embed.waze.com/iframe?zoom=16&lat=13.73694&lon=-89.15139&pin=1', 'https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d31011.901443473358!2d-89.24306062022205!3d13.688887245601492!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sEstacionamientos!5e0!3m2!1ses-419!2ssv!4v1754886589077!5m2!1ses-419!2ssv\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade', NULL, NULL),
(32, 'San Salvador Norte', 'Ilopango', 'Lotificación, Vista al lago, calle #530', '2 cuadras antes del turicentro', 'https://embed.waze.com/iframe?zoom=16&lat=13.693871&lon=-89.110246&ct=livemap', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d62023.383046366514!2d-89.15247129476393!3d13.690479466061408!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f6349004fb28f0b%3A0x960bbc794833f089!2sParqueo%20privado!5e0!3m2!1ses-419!2ssv!4v1755183741723!5m2!1ses-419!2ssv\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade', NULL, NULL),
(33, 'San Salvador Centro', 'San Salvador Centro', '1a calle poniente', 'Frente a la Plaza Morazán y al lado del teatro nacional', 'https://embed.waze.com/iframe?zoom=16&lat=13.699476&lon=-89.189897&ct=livemap', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3876.315184654749!2d-89.1903605!3d13.6993508!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f6330ea1af5226d%3A0xa709dea0546550dc!2sPlaza%20Moraz%C3%A1n!5e0!3m2!1ses!2ssv!4v1755219457451!5m2!1ses!2ssv', NULL, NULL),
(34, 'San Salvador Centro', 'San Salvador Centro', 'P.º Gral. Escalón 3700, San Salvador', 'En Centro comercial Galerías', 'https://embed.waze.com/iframe?zoom=16&lat=13.702386&lon=-89.229836&ct=livemap', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3876.2657617333275!2d-89.22963081686163!3d13.702347338574203!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f63303fad9ab3bb%3A0x240bbac4a0987ec7!2sCentro%20Comercial%20Galer%C3%ADas!5e0!3m2!1ses-419!2ssv!4v1755224691560!5m2!1ses-419!2ssv', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_type` varchar(20) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `attempt_type`, `attempted_at`) VALUES
(6, '::1', 'register_failed', '2025-10-20 20:46:42'),
(8, '::1', 'register_failed', '2025-10-20 20:53:07'),
(9, '::1', 'register_failed', '2025-10-20 20:53:25'),
(5, '::1', 'register_success', '2025-10-20 20:44:34'),
(7, '::1', 'register_success', '2025-10-20 20:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('review_response','parking_update','price_drop','new_feature','security_alert','saved_parking_news','system_news','reservation_reminder','promotion','owner_specific','admin_alert') NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `notification_data` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_parking_id` int(11) DEFAULT NULL,
  `related_news_id` int(11) DEFAULT NULL,
  `related_reservation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parkings`
--

CREATE TABLE `parkings` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `category_id` tinyint(4) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schedule`)),
  `is_24_7` tinyint(1) DEFAULT 0,
  `contact_name` varchar(50) DEFAULT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `status` enum('activo','inactivo') DEFAULT 'activo',
  `is_private` tinyint(1) DEFAULT 0,
  `access_code` varchar(12) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parkings`
--

INSERT INTO `parkings` (`id`, `owner_id`, `location_id`, `category_id`, `name`, `description`, `schedule`, `is_24_7`, `contact_name`, `contact_phone`, `contact_email`, `status`, `is_private`, `access_code`, `created_at`, `updated_at`) VALUES
(30, 9, 30, 2, 'Parqueo Soyapango', 'El parqueo es un excelente lugar para dejar tu vehículo vigilado y accesible a Plaza Mundo.', '[]', 1, 'Enrique encargado', '50354565644', 'Enrique@gmail.com', 'activo', 0, NULL, '2025-08-11 04:03:12', '2025-08-11 04:03:12'),
(32, 23, 32, 4, 'Parqueo Centro Ilopango', 'Este parqueo provee de varias características que te mantienen cómodo durante tu viaje por Apulo, Ilopango, o en el centro donde puedes optar por servicios comerciales. Por el momento mantendremos nuestra promoción de parqueo gratis para autos medianos los fines de semana que sean incluidos en las fechas: 25/12/25 - 31/12/25 con el objetivo de promover la compra de alimentos en nuestro comedor de pupusas con vista al lago de Apulo.', '{\"lunes\":[{\"apertura\":\"07:00\",\"cierre\":\"09:00\"},{\"apertura\":\"12:00\",\"cierre\":\"05:00\"}],\"martes\":[],\"miercoles\":[],\"jueves\":[],\"viernes\":[],\"sabado\":[],\"domingo\":[]}', 0, 'Enrique encargado', '50354565644', 'Enrique@gmail.com', 'activo', 0, NULL, '2025-08-14 17:19:53', '2025-08-14 17:19:53'),
(33, 23, 33, 4, 'Parqueo Morazán', 'Estacionamiento amplio en el extremo este del Centro Histórico, junto al Monumento Morazán. Permite acceso fácil a la zona comercial y universitaria.', '{\"lunes\":[],\"martes\":[],\"miercoles\":[],\"jueves\":[],\"viernes\":[],\"sabado\":[],\"domingo\":[]}', 1, 'Kevin Administrador', '+503 5456 5644', 'kevin@gmail.com', 'activo', 0, NULL, '2025-08-15 01:25:36', '2025-08-15 01:25:36'),
(34, 23, 34, 2, 'Parqueo Subterráneo – Centro Comercial Galerías', 'Centro comercial moderno de varios pisos con tiendas alrededor de una majestuosa casa emblemática de 1950. Ofrecemos accesibilidad a personas discapacitadas desde espacios reservados hasta en el acceso a sanitarios. ¡Compra ya en Centro Comercial Galerías!', '{\"lunes\":[{\"apertura\":\"06:00\",\"cierre\":\"10:00\"},{\"apertura\":\"14:00\",\"cierre\":\"17:00\"}],\"martes\":[{\"apertura\":\"06:00\",\"cierre\":\"08:00\"}],\"miercoles\":[],\"jueves\":[],\"viernes\":[],\"sabado\":[],\"domingo\":[]}', 0, 'Mauro patrullero', '+503 6142 9731', 'mauro@gmail.com', 'activo', 0, NULL, '2025-08-15 02:38:46', '2025-08-15 02:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `parking_availability`
--

CREATE TABLE `parking_availability` (
  `parking_id` int(11) NOT NULL,
  `available_spaces` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_capacities`
--

CREATE TABLE `parking_capacities` (
  `parking_id` int(11) NOT NULL,
  `general_capacity` int(11) NOT NULL,
  `reservable_capacity` int(11) DEFAULT NULL,
  `disability_spaces` int(11) DEFAULT 0,
  `pregnant_people_spaces` int(11) DEFAULT 0,
  `taxi_spaces` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_capacities`
--

INSERT INTO `parking_capacities` (`parking_id`, `general_capacity`, `reservable_capacity`, `disability_spaces`, `pregnant_people_spaces`, `taxi_spaces`) VALUES
(30, 500, 50, 20, 10, 10),
(32, 100, NULL, 10, 0, 0),
(33, 500, NULL, 30, 0, 0),
(34, 200, NULL, 20, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `parking_categories`
--

CREATE TABLE `parking_categories` (
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_categories`
--

INSERT INTO `parking_categories` (`id`, `name`, `description`) VALUES
(1, 'Normal', 'Parqueo estándar con características comunes.'),
(2, 'Alta demanda', 'Alta rotación de vehículos, usualmente en zonas comerciales.'),
(3, 'Turístico', 'Cercano a atracciones turísticas, con servicios para visitantes.'),
(4, 'Mixto', 'Combina alta demanda y características turísticas.'),
(5, 'Premium', 'Servicios exclusivos como valet, seguridad reforzada y comodidades superiores.');

-- --------------------------------------------------------

--
-- Table structure for table `parking_fees`
--

CREATE TABLE `parking_fees` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `vehicle_type_id` tinyint(4) NOT NULL,
  `fee_type` enum('normal','premium','nocturno','mensual','comercial','evento') DEFAULT 'normal',
  `price` varchar(50) NOT NULL DEFAULT 'Gratis',
  `time_unit` enum('minuto','hora','día','semana','mes','año') DEFAULT 'hora',
  `applies_to` enum('Días laborales','Fines de semana','Toda la semana') DEFAULT 'Toda la semana',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_fees`
--

INSERT INTO `parking_fees` (`id`, `parking_id`, `vehicle_type_id`, `fee_type`, `price`, `time_unit`, `applies_to`, `valid_from`, `valid_to`) VALUES
(15, 30, 1, 'normal', '15', 'hora', 'Toda la semana', NULL, NULL),
(16, 30, 2, 'normal', '00.50', 'minuto', 'Toda la semana', NULL, NULL),
(17, 30, 3, 'normal', '13', 'hora', 'Días laborales', NULL, NULL),
(18, 30, 3, 'evento', 'Gratis', 'día', 'Fines de semana', '2025-12-25', '2025-12-30'),
(20, 32, 1, 'normal', '00.10', 'minuto', '', NULL, NULL),
(21, 32, 2, 'normal', '00.15', 'minuto', '', NULL, NULL),
(22, 32, 2, 'mensual', '35', 'mes', '', NULL, NULL),
(23, 32, 3, 'evento', 'Gratis', 'día', '', '2025-12-25', '2025-12-31'),
(24, 33, 1, 'normal', '1.00', 'hora', '', NULL, NULL),
(25, 33, 2, 'normal', '1.00', 'hora', '', NULL, NULL),
(26, 33, 3, 'normal', '1.00', 'hora', '', NULL, NULL),
(27, 33, 4, 'normal', '1.00', 'hora', '', NULL, NULL),
(28, 33, 3, 'nocturno', '1.00', 'día', '', NULL, NULL),
(29, 34, 7, 'normal', '10.00', 'día', '', NULL, NULL),
(30, 34, 1, 'normal', '0.10', 'minuto', '', NULL, NULL),
(31, 34, 2, 'normal', '1.15', 'hora', '', NULL, NULL),
(32, 34, 2, 'comercial', 'Gratis', 'día', '', NULL, NULL),
(33, 34, 1, 'comercial', 'Gratis', 'día', '', '2025-12-24', '2025-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `parking_images`
--

CREATE TABLE `parking_images` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `image_url` text NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_images`
--

INSERT INTO `parking_images` (`id`, `parking_id`, `image_url`, `sort_order`, `is_primary`, `uploaded_at`) VALUES
(34, 30, 'public/uploads/parkings/parking_30_68996b80bac7b.jpg', 1, 0, '2025-08-11 04:03:12'),
(35, 30, 'public/uploads/parkings/parking_30_68996b80d0974.jpg', 2, 0, '2025-08-11 04:03:12'),
(36, 30, 'public/uploads/parkings/parking_30_68996b80d2b34.jpg', 3, 1, '2025-08-11 04:03:12'),
(37, 30, 'public/uploads/parkings/parking_30_68996b80d4529.jpg', 4, 0, '2025-08-11 04:03:12'),
(38, 30, 'public/uploads/parkings/parking_30_68996b80d5f64.jpg', 5, 0, '2025-08-11 04:03:12'),
(39, 30, 'public/uploads/parkings/parking_30_68996b80d7d5c.jpg', 6, 0, '2025-08-11 04:03:12'),
(40, 32, 'public/uploads/parkings/parking_32_689e1aba02453.jpg', 1, 0, '2025-08-14 17:19:54'),
(41, 32, 'public/uploads/parkings/parking_32_689e1aba0d796.jpg', 2, 0, '2025-08-14 17:19:54'),
(42, 32, 'public/uploads/parkings/parking_32_689e1aba0e98d.jpg', 3, 0, '2025-08-14 17:19:54'),
(43, 32, 'public/uploads/parkings/parking_32_689e1aba0f150.jpg', 4, 0, '2025-08-14 17:19:54'),
(44, 32, 'public/uploads/parkings/parking_32_689e1aba0f97f.webp', 5, 1, '2025-08-14 17:19:54'),
(45, 33, 'public/uploads/parkings/parking_33_689e8c90d98aa.jpg', 1, 0, '2025-08-15 01:25:36'),
(46, 33, 'public/uploads/parkings/parking_33_689e8c90f1fe1.jpg', 2, 0, '2025-08-15 01:25:36'),
(47, 33, 'public/uploads/parkings/parking_33_689e8c90f29ca.jpg', 3, 0, '2025-08-15 01:25:36'),
(48, 33, 'public/uploads/parkings/parking_33_689e8c90f3235.jpg', 4, 0, '2025-08-15 01:25:36'),
(49, 33, 'public/uploads/parkings/parking_33_689e8c90f4173.jpg', 5, 1, '2025-08-15 01:25:37'),
(50, 34, 'public/uploads/parkings/parking_34_689e9db66238d.webp', 1, 0, '2025-08-15 02:38:46'),
(51, 34, 'public/uploads/parkings/parking_34_689e9db663bf9.webp', 2, 0, '2025-08-15 02:38:46'),
(52, 34, 'public/uploads/parkings/parking_34_689e9db664e04.webp', 3, 0, '2025-08-15 02:38:46'),
(53, 34, 'public/uploads/parkings/parking_34_689e9db666116.jpg', 4, 1, '2025-08-15 02:38:46'),
(54, 34, 'public/uploads/parkings/parking_34_689e9db666a52.jpg', 5, 0, '2025-08-15 02:38:46');

--
-- Triggers `parking_images`
--
DELIMITER $$
CREATE TRIGGER `before_parking_image_insert` BEFORE INSERT ON `parking_images` FOR EACH ROW BEGIN
    DECLARE img_count INT;
    SELECT COUNT(*) INTO img_count 
    FROM parking_images 
    WHERE parking_id = NEW.parking_id;
    
    IF img_count >= 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Máximo 8 imágenes por parqueo';
    END IF;
    
    IF NEW.sort_order = 0 THEN
        SET NEW.sort_order = img_count + 1;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `parking_news`
--

CREATE TABLE `parking_news` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `news_type` enum('promocion','evento','mantenimiento','nuevo_servicio','cierre_temporal','alerta_trafico','nuevo_parqueo','consejo_estacionamiento') NOT NULL,
  `severity` enum('info','warning','urgent') DEFAULT 'info',
  `effective_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_private_access`
--

CREATE TABLE `parking_private_access` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_type` enum('qr_invitation','direct_permission') NOT NULL,
  `access_code` varchar(12) DEFAULT NULL,
  `status` enum('pending','approved','revoked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_restrictions`
--

CREATE TABLE `parking_restrictions` (
  `parking_id` int(11) NOT NULL,
  `max_height` decimal(4,2) DEFAULT NULL,
  `max_speed` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Velocidad máxima en km/h (0-255)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_restrictions`
--

INSERT INTO `parking_restrictions` (`parking_id`, `max_height`, `max_speed`) VALUES
(30, 30.00, 20),
(32, NULL, 15),
(33, 2.10, 10),
(34, 4.00, 10);

-- --------------------------------------------------------

--
-- Table structure for table `parking_restriction_items`
--

CREATE TABLE `parking_restriction_items` (
  `parking_id` int(11) NOT NULL,
  `restriction_type_id` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_restriction_items`
--

INSERT INTO `parking_restriction_items` (`parking_id`, `restriction_type_id`) VALUES
(30, 3),
(30, 6),
(30, 7),
(32, 3),
(32, 6),
(33, 6),
(34, 1),
(34, 2);

-- --------------------------------------------------------

--
-- Table structure for table `parking_services`
--

CREATE TABLE `parking_services` (
  `parking_id` int(11) NOT NULL,
  `service_id` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_services`
--

INSERT INTO `parking_services` (`parking_id`, `service_id`) VALUES
(30, 1),
(30, 7),
(30, 9),
(30, 12),
(30, 13),
(30, 14),
(30, 15),
(30, 16),
(32, 4),
(32, 6),
(32, 9),
(32, 10),
(32, 11),
(32, 14),
(32, 17),
(33, 1),
(33, 2),
(33, 3),
(33, 6),
(33, 17),
(34, 1),
(34, 2),
(34, 3),
(34, 4),
(34, 5),
(34, 6),
(34, 9),
(34, 14),
(34, 17);

-- --------------------------------------------------------

--
-- Table structure for table `parking_updates`
--

CREATE TABLE `parking_updates` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `update_type` enum('tarifa','servicio','horario','general','imagen','capacidad') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `parking_updates`
--
DELIMITER $$
CREATE TRIGGER `after_parking_update` AFTER INSERT ON `parking_updates` FOR EACH ROW BEGIN
    -- Notificar a CLIENTES con este parqueo en favoritos
    INSERT INTO notifications (user_id, notification_type, title, content, related_parking_id)
    SELECT 
        f.user_id,
        'parking_update',
        CONCAT('Actualización en ', p.name),
        NEW.description,
        NEW.parking_id
    FROM favorites f
    JOIN parkings p ON p.id = f.parking_id
    WHERE f.parking_id = NEW.parking_id;
    
    -- Notificar a CLIENTES que reseñaron este parqueo
    INSERT INTO notifications (user_id, notification_type, title, content, related_parking_id)
    SELECT 
        r.user_id,
        'parking_update',
        CONCAT('Cambios en ', p.name),
        CONCAT('El parqueo que calificaste actualizó: ', NEW.description),
        NEW.parking_id
    FROM reviews r
    JOIN parkings p ON p.id = r.parking_id
    WHERE r.parking_id = NEW.parking_id;
    
    -- Notificar al PROPIETARIO del parqueo
    INSERT INTO notifications (user_id, notification_type, title, content, related_parking_id)
    SELECT 
        u.id,
        'owner_specific',
        'Actualización registrada',
        CONCAT('Se aplicó un cambio a tu parqueo: ', NEW.description),
        NEW.parking_id
    FROM parkings p
    JOIN users u ON u.id = p.owner_id
    WHERE p.id = NEW.parking_id AND u.user_type = 'owner';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `parking_vehicle_capacities`
--

CREATE TABLE `parking_vehicle_capacities` (
  `parking_id` int(11) NOT NULL,
  `vehicle_type_id` tinyint(4) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0 COMMENT 'Espacios disponibles',
  `reservable_vehicle_c` int(11) DEFAULT 0 COMMENT 'Espacios reservables'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_vehicle_capacities`
--

INSERT INTO `parking_vehicle_capacities` (`parking_id`, `vehicle_type_id`, `capacity`, `reservable_vehicle_c`) VALUES
(30, 1, 100, 15),
(30, 2, 300, 20),
(30, 3, 80, 15),
(32, 1, 30, 0),
(32, 2, 50, 0),
(32, 3, 20, 0),
(33, 1, 100, 0),
(33, 2, 100, 0),
(33, 3, 200, 0),
(33, 4, 100, 0),
(34, 1, 50, 0),
(34, 2, 100, 0),
(34, 7, 50, 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `vehicle_type_id` tinyint(4) NOT NULL,
  `fechaHoraInicio` datetime NOT NULL,
  `fechaHoraFin` datetime NOT NULL,
  `codigo_qr` varchar(255) NOT NULL,
  `status` enum('reservado','usado','sancionado','cancelado') DEFAULT 'reservado',
  `montoSancion` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `parking_id`, `vehicle_type_id`, `fechaHoraInicio`, `fechaHoraFin`, `codigo_qr`, `status`, `montoSancion`, `created_at`, `updated_at`) VALUES
(1, 10, 33, 2, '2026-05-02 07:00:00', '2026-05-02 12:00:00', '215292be044f9c24bf9e3e12f848f438', 'reservado', 0.00, '2025-09-12 21:14:53', '2025-09-12 21:14:53'),
(2, 10, 30, 3, '2025-10-07 11:00:00', '2025-12-09 13:50:00', '5c87a3225609b0ca53bb8134e6ed3fbe', 'reservado', 0.00, '2025-09-13 02:29:25', '2025-09-13 02:29:25'),
(3, 10, 30, 1, '2026-07-07 11:00:00', '2026-09-08 13:00:00', '114b8119ec8157a8dc9afd6703c193fa', 'reservado', 0.00, '2025-09-13 19:43:26', '2025-09-13 19:43:26'),
(4, 10, 30, 1, '2025-09-24 22:54:00', '2025-09-25 14:54:00', '869ab3fbd9a961c399f7fb857b3c8a7b', 'reservado', 0.00, '2025-09-24 20:49:18', '2025-09-24 20:49:18');

--
-- Triggers `reservations`
--
DELIMITER $$
CREATE TRIGGER `check_reservation_status` BEFORE UPDATE ON `reservations` FOR EACH ROW BEGIN
  IF NEW.status = 'reservado' AND NOW() > NEW.fechaHoraFin THEN
    SET NEW.status = 'sancionado';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `restriction_types`
--

CREATE TABLE `restriction_types` (
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restriction_types`
--

INSERT INTO `restriction_types` (`id`, `name`) VALUES
(7, 'No armas cortopunzantes'),
(6, 'No armas de fuego'),
(5, 'No celulares'),
(3, 'No comida'),
(1, 'No fumar'),
(8, 'No mascotas'),
(2, 'No tirar basura'),
(4, 'Sólo mayores 18+'),
(9, '¡Parquearse en posición de salida!');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'fa-check-circle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `icon`) VALUES
(1, 'Cámaras', 'fa-video'),
(2, 'Vigilancia', 'fa-shield-alt'),
(3, 'Techo', 'fa-people-roof'),
(4, 'Sanitarios', 'fa-restroom'),
(5, 'Wi-Fi', 'fa-wifi'),
(6, 'Discapacitados', 'fa-wheelchair'),
(7, 'Carwash', 'fa-car'),
(8, 'Gasolinera', 'fa-gas-pump'),
(9, 'Comedor', 'fa-utensils'),
(10, 'Valet', 'fa-user-tie'),
(11, 'Mascotas permitidas', 'fa-paw'),
(12, 'Afiliado con Vivepass', 'fa-credit-card'),
(13, 'Afiliado con BAC Compass', 'fa-credit-card'),
(14, 'Cajero automático (ATM)', 'fa-money-bill-wave'),
(15, 'Carga para autos eléctricos', 'fa-charging-station'),
(16, 'Taller mecánico', 'fa-tools'),
(17, 'iluminación', 'fa-lightbulb'),
(18, 'Futuras mamás', 'fa-person-pregnant'),
(19, 'Espacios para Taxis', 'fa-taxi'),
(20, 'Para vehículos de transporte público', 'fa-bus-side'),
(21, 'Para bicicletas', 'fa-bycicle'),
(22, 'Centro Comercial', 'fa-cart-shopping'),
(23, 'Cargar baterías', 'fa-car-battery'),
(24, 'Inflar llantas', 'fa-circle'),
(25, 'Limpieza de parabrisas', 'fa-soap'),
(26, 'Hotel', 'fa-hotel'),
(27, 'Auto-servicio', 'truck-fast');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` text DEFAULT NULL COMMENT 'URL completa de la imagen',
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `user_type` enum('customer','owner') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_permission` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitud del usuario',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitud del usuario',
  `penalty_count` int(11) DEFAULT 0,
  `is_penalized` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `profile_picture`, `phone_number`, `date_of_birth`, `business_name`, `user_type`, `created_at`, `location_permission`, `latitude`, `longitude`, `penalty_count`, `is_penalized`, `email_verified`, `email_verified_at`, `last_login`) VALUES
(9, 'Jeremy Torres', 'jeremy.torres2027@adoc.superate.org.sv', '$2y$10$U3oMQFMUXAoXTAIQdwqOAetlxss9s5GyDSXZDZbLbfBsnct8oEkGe', '/crud-php2/public/uploads/avatars/avatar_9_1754921459.jpg', '6142 9731', NULL, 'Parqueos SV', 'owner', '2025-07-30 22:35:19', 0, 13.72213166, -89.09308573, 0, 0, 0, NULL, NULL),
(10, 'Jeremy Torres2', 'jeremy.torres2026@adoc.superate.org.sv', '$2y$10$btmsoIim.zWiU7ILcNoSDeNZhd8/owRanykuGNnLiltb7AsuD9AWW', '/crud-php2/public/uploads/avatars/avatar_10_1757711227.jpg', '6142 9731', '2008-09-08', NULL, 'customer', '2025-08-02 10:53:45', 0, 13.72212847, -89.09308252, 0, 0, 0, NULL, NULL),
(11, 'Jeremy Torres3', 'jeremy.torres2025@adoc.superate.org.sv', '$2y$10$omaoC.j.nNoDJcsOu25aRuSyzt7DqNNpI1HDBuIl4z5pJ2zHjcea.', NULL, '6142 9731', NULL, 'Parqueos SV 2', 'owner', '2025-08-04 04:52:33', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(12, 'Dylan Torres', 'dylan.torres2025@adoc.superate.org.sv', '$2y$10$RcUO6NnC7j7mpecvOVPcfeptxJjFt1IrrCKZQ3OYRNlxoTUOVq2Mu', NULL, '7758 7602', '2017-05-24', NULL, 'customer', '2025-08-04 10:40:15', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(14, 'Jeremy Torres4', 'jeremy.torres2028@adoc.superate.org.sv', '$2y$10$s1/.pXaGOuffqhlUyTTGh.5S87I5qTgg..U5BKmvRdsjaR3dpj8mS', NULL, '7758 7602', NULL, 'Parqueos SV 3', 'owner', '2025-08-09 01:49:52', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(15, 'Erika Leal', 'mikeyE50@gmail.com', '$2y$10$A2fJvAiGXXVk5uMl2H75FuBdDw9sO/RocqvAvHGKGco1DU2EGs5MS', '/crud-php2/public/uploads/avatars/avatar_15_1754921349.jpg', '7835 2492', NULL, 'Mikey parqueo', 'owner', '2025-08-11 19:30:45', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(16, 'Ben Zepeda', 'benhur.zepeda@adoc.superate.org.sv', '$2y$10$ZnEI.LOh/FDYnq58St86k.VHU61tu7TbjIcTFStf4j.NNJKYd.Pdy', NULL, '70399202', '1972-02-26', NULL, 'customer', '2025-08-11 22:38:47', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(18, 'steven cortez', 'seven@gmail.com', '$2y$10$E5cIx8EtQ82nOleY5KriMOjqrKHn.A5ibUx5cKCl38/1JBdF1a8UW', '/crud-php2/public/uploads/avatars/avatar_18_1754949673.jpg', '74333786', '1999-07-03', NULL, 'customer', '2025-08-12 05:59:37', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(19, 'Fredy Ayala', 'fredy@gmail.com', '$2y$10$ZALYZBgMIgxKBvNs.pFCXucVfiXSyIn7EpUZMZCiOihwBZ3viXOka', NULL, '70399202', NULL, 'Fredy parqueo', 'owner', '2025-08-12 08:01:48', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(21, 'Kevin Hernandez', 'kevin@mail.com', '$2y$10$fMnKn3JzucOTqQz0FfWNO.8Q8O6TpEosgCR5e34PuUmPehCN5BAXa', '/crud-php2/public/uploads/avatars/avatar_21_1755009998.jpg', '77779999', '2000-02-16', NULL, 'customer', '2025-08-12 22:41:58', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(22, 'Adrián Torres', 'adrian@gmail.com', '$2y$10$enQ7LTuy8xUd4OkeJf.Mi.az4oXFpZiSoPY3IQGC6xmeldg2p46AS', '/crud-php2/public/uploads/avatars/avatar_22_1755097853.jpg', '75839287', '2025-01-17', NULL, 'customer', '2025-08-13 23:10:02', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(23, 'Enrique Torres', 'enrique.torres@gmail.com', '$2y$10$OM0XzndkTxlw13LbfVxAquz.Je1IGXjsLjs4OUWuGqzPk889gzpja', NULL, '6142 9731', NULL, 'Parqueos SV', 'owner', '2025-08-14 22:24:39', 0, 13.72206487, -89.09311373, 0, 0, 0, NULL, NULL),
(26, 'Jeremy Torres', 'jeremy@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VlNHb3Y2TTg4RlpUVncxNA$tLFIsR1rRraLCFh4F/xeYRYBhJzHNc611lrSWxcIo90', NULL, NULL, NULL, NULL, 'customer', '2025-10-15 02:43:14', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(27, 'Jeremy Torres', 'elalienjeremy@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cnprT0NOQzRGMzF2S3VYYw$Ov9nrYToct/zA0fOdeL9VN5YkkG6X+KNDs7lj1nm8wk', NULL, NULL, NULL, NULL, 'customer', '2025-10-15 02:44:34', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(28, 'Jeremy Torres', 'jeremyensuprime@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SGpFcWswcWc4YzRaeHhTaA$LxnXw3P0tsfaPgh6HxISX9QWmlG1hqH1Z2qFgccUnJo', NULL, NULL, NULL, NULL, 'customer', '2025-10-15 03:06:38', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(29, 'Fredy Ano', 'holanigger@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$YTlBbUZUc3k1aHJtMHhzNg$iYCIuo65dcIYcyGgUsRijbV1rAGfh6+dLK7g6K22G38', NULL, NULL, NULL, NULL, 'customer', '2025-10-20 20:44:29', 0, NULL, NULL, 0, 0, 0, NULL, NULL),
(30, 'Fredy Bitch', '4659243@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$UWtRVFRvNHdTM01YYnFpVA$mUnwYJzh9Wy3LL4CU1IJAn+Ctl5yn5+SGMGIuox0aTA', NULL, NULL, NULL, NULL, 'customer', '2025-10-20 20:47:22', 0, NULL, NULL, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_penalties`
--

CREATE TABLE `user_penalties` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_specifications`
--

CREATE TABLE `user_specifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specification_type_id` int(11) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_specifications`
--

INSERT INTO `user_specifications` (`id`, `user_id`, `specification_type_id`, `value`, `created_at`) VALUES
(13, 9, 6, 15, '2025-09-09 01:27:32'),
(14, 9, 2, NULL, '2025-09-09 01:27:32'),
(15, 9, 1, NULL, '2025-09-09 01:27:32'),
(16, 9, 3, NULL, '2025-09-09 01:27:32'),
(17, 9, 4, NULL, '2025-09-09 01:27:32'),
(18, 9, 5, NULL, '2025-09-09 01:27:32'),
(19, 10, 6, 3, '2025-09-10 01:42:03'),
(20, 10, 2, NULL, '2025-09-10 01:42:03'),
(21, 10, 1, NULL, '2025-09-10 01:42:03'),
(22, 10, 3, NULL, '2025-09-10 01:42:03'),
(23, 10, 4, NULL, '2025-09-10 01:42:03'),
(24, 10, 5, NULL, '2025-09-10 01:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_specification_types`
--

CREATE TABLE `user_specification_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `has_value` tinyint(1) DEFAULT 0,
  `value_label` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_specification_types`
--

INSERT INTO `user_specification_types` (`id`, `name`, `icon`, `description`, `has_value`, `value_label`, `created_at`) VALUES
(1, 'Discapacitad@ a bordo', 'wheelchair', 'Personas con movilidad reducida', 0, NULL, '2025-09-08 23:49:44'),
(2, 'Conductor/a de Taxi', 'taxi', 'Conductor de servicio de taxi', 0, NULL, '2025-09-08 23:49:44'),
(3, 'Futura mamá a bordo', 'person-pregnant', 'Mujeres embarazadas', 0, NULL, '2025-09-08 23:49:44'),
(4, 'Mascotas a bordo', 'paw', 'Transporta mascotas regularmente', 0, NULL, '2025-09-08 23:49:44'),
(5, 'Vehículo eléctrico', 'charging-station', 'Vehículo con motorización eléctrica', 0, NULL, '2025-09-08 23:49:44'),
(6, 'Altura del vehículo', 'ruler-vertical', 'Altura máxima del vehículo', 1, 'metros', '2025-09-08 23:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `type` enum('verify_email','password_reset','remember') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attempts` int(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `user_id` int(11) NOT NULL,
  `vehicle_type_id` tinyint(4) NOT NULL,
  `vehicle_length` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_vehicles`
--

INSERT INTO `user_vehicles` (`user_id`, `vehicle_type_id`, `vehicle_length`, `created_at`) VALUES
(9, 8, NULL, '2025-08-10 16:16:53'),
(10, 1, NULL, '2025-09-13 18:34:57'),
(10, 2, NULL, '2025-09-13 18:34:57'),
(10, 3, NULL, '2025-09-13 18:34:57'),
(10, 9, NULL, '2025-09-13 18:34:57'),
(15, 8, NULL, '2025-08-11 14:09:25'),
(16, 3, NULL, '2025-08-11 14:53:55'),
(21, 1, NULL, '2025-08-12 14:47:29'),
(21, 2, NULL, '2025-08-12 14:47:29'),
(21, 5, NULL, '2025-08-12 14:47:29'),
(22, 2, NULL, '2025-08-13 15:11:11'),
(23, 2, NULL, '2025-08-15 12:33:17'),
(23, 7, NULL, '2025-08-15 12:33:17'),
(23, 8, NULL, '2025-08-15 12:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` tinyint(4) NOT NULL,
  `category_key` varchar(30) NOT NULL COMMENT 'Identificador único para programación',
  `category_name` varchar(50) NOT NULL COMMENT 'Nombre para mostrar en la interfaz',
  `icon` varchar(30) NOT NULL COMMENT 'Clase de icono FontAwesome',
  `description` text NOT NULL COMMENT 'Ejemplos de vehículos incluidos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `category_key`, `category_name`, `icon`, `description`) VALUES
(1, 'moto', 'Motocicletas', 'motorcycle', 'Scooters, motocicletas estándar y grandes'),
(2, 'auto_pequeno', 'Autos Pequeños', 'car', 'Sedanes compactos, hatchbacks, subcompactos'),
(3, 'auto_mediano', 'Autos Medianos', 'car-side', 'Sedanes familiares, crossovers pequeños'),
(4, 'auto_grande', 'Autos Grandes', 'car-alt', 'SUVs medianas, minivans, sedanes ejecutivos'),
(5, 'pickup', 'Pickups/Furgonetas', 'truck-pickup', 'Pickups pequeñas/grandes, furgonetas'),
(6, 'comercial', 'Vehículos Comerciales', 'bus', 'Microbuses, buses pequeños (15-20 pasajeros)'),
(7, 'pesado', 'Vehículos Pesados', 'truck', 'Camiones de carga liviana, volquetas'),
(8, 'trailer', 'Trailers/Remolques', 'trailer', 'Remolques pequeños, semirremolques'),
(9, 'bici', 'Bicicletas', 'bicycle', 'Bicis, triciclos y uniciclos se incluyen');

-- --------------------------------------------------------

--
-- Table structure for table `verifications`
--

CREATE TABLE `verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `code` varchar(6) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verifications`
--

INSERT INTO `verifications` (`id`, `email`, `code`, `created_at`, `attempts`) VALUES
(3, 'jeremy@gmail.com', '413703', '2025-10-14 20:43:14', 0),
(4, 'elalienjeremy@gmail.com', '665919', '2025-10-14 20:44:34', 0),
(5, 'jeremyensuprime@gmail.com', '150368', '2025-10-14 21:06:38', 0),
(6, 'holanigger@gmail.com', '424099', '2025-10-20 14:44:29', 0),
(7, '4659243@gmail.com', '353530', '2025-10-20 14:47:22', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_user_id` (`owner_user_id`);

--
-- Indexes for table `account_users`
--
ALTER TABLE `account_users`
  ADD PRIMARY KEY (`account_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`parking_id`,`folder_id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `favorite_folders`
--
ALTER TABLE `favorite_folders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_token` (`share_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department` (`department`,`municipality`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_type_time` (`ip_address`,`attempt_type`,`attempted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parkings`
--
ALTER TABLE `parkings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `status` (`status`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `parking_availability`
--
ALTER TABLE `parking_availability`
  ADD PRIMARY KEY (`parking_id`);

--
-- Indexes for table `parking_capacities`
--
ALTER TABLE `parking_capacities`
  ADD PRIMARY KEY (`parking_id`);

--
-- Indexes for table `parking_categories`
--
ALTER TABLE `parking_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `parking_fees`
--
ALTER TABLE `parking_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `fee_type` (`fee_type`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`);

--
-- Indexes for table `parking_images`
--
ALTER TABLE `parking_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`,`sort_order`);

--
-- Indexes for table `parking_news`
--
ALTER TABLE `parking_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Indexes for table `parking_private_access`
--
ALTER TABLE `parking_private_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parking_restrictions`
--
ALTER TABLE `parking_restrictions`
  ADD PRIMARY KEY (`parking_id`);

--
-- Indexes for table `parking_restriction_items`
--
ALTER TABLE `parking_restriction_items`
  ADD PRIMARY KEY (`parking_id`,`restriction_type_id`),
  ADD KEY `restriction_type_id` (`restriction_type_id`);

--
-- Indexes for table `parking_services`
--
ALTER TABLE `parking_services`
  ADD PRIMARY KEY (`parking_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `parking_updates`
--
ALTER TABLE `parking_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Indexes for table `parking_vehicle_capacities`
--
ALTER TABLE `parking_vehicle_capacities`
  ADD PRIMARY KEY (`parking_id`,`vehicle_type_id`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_qr_code` (`codigo_qr`);

--
-- Indexes for table `restriction_types`
--
ALTER TABLE `restriction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_penalties`
--
ALTER TABLE `user_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `user_specifications`
--
ALTER TABLE `user_specifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `specification_type_id` (`specification_type_id`);

--
-- Indexes for table `user_specification_types`
--
ALTER TABLE `user_specification_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`user_id`,`vehicle_type_id`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_key` (`category_key`);

--
-- Indexes for table `verifications`
--
ALTER TABLE `verifications`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `favorite_folders`
--
ALTER TABLE `favorite_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parkings`
--
ALTER TABLE `parkings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `parking_categories`
--
ALTER TABLE `parking_categories`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `parking_fees`
--
ALTER TABLE `parking_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `parking_images`
--
ALTER TABLE `parking_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `parking_news`
--
ALTER TABLE `parking_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_private_access`
--
ALTER TABLE `parking_private_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_updates`
--
ALTER TABLE `parking_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `restriction_types`
--
ALTER TABLE `restriction_types`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_penalties`
--
ALTER TABLE `user_penalties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_specifications`
--
ALTER TABLE `user_specifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_specification_types`
--
ALTER TABLE `user_specification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `verifications`
--
ALTER TABLE `verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_users`
--
ALTER TABLE `account_users`
  ADD CONSTRAINT `account_users_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_3` FOREIGN KEY (`folder_id`) REFERENCES `favorite_folders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `favorite_folders`
--
ALTER TABLE `favorite_folders`
  ADD CONSTRAINT `favorite_folders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parkings`
--
ALTER TABLE `parkings`
  ADD CONSTRAINT `parkings_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parkings_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `parkings_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `parking_categories` (`id`);

--
-- Constraints for table `parking_availability`
--
ALTER TABLE `parking_availability`
  ADD CONSTRAINT `parking_availability_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_capacities`
--
ALTER TABLE `parking_capacities`
  ADD CONSTRAINT `parking_capacities_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_fees`
--
ALTER TABLE `parking_fees`
  ADD CONSTRAINT `parking_fees_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_fees_ibfk_2` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`);

--
-- Constraints for table `parking_images`
--
ALTER TABLE `parking_images`
  ADD CONSTRAINT `parking_images_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_news`
--
ALTER TABLE `parking_news`
  ADD CONSTRAINT `parking_news_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_private_access`
--
ALTER TABLE `parking_private_access`
  ADD CONSTRAINT `parking_private_access_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_private_access_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_restrictions`
--
ALTER TABLE `parking_restrictions`
  ADD CONSTRAINT `parking_restrictions_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_restriction_items`
--
ALTER TABLE `parking_restriction_items`
  ADD CONSTRAINT `parking_restriction_items_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_restriction_items_ibfk_2` FOREIGN KEY (`restriction_type_id`) REFERENCES `restriction_types` (`id`);

--
-- Constraints for table `parking_services`
--
ALTER TABLE `parking_services`
  ADD CONSTRAINT `parking_services_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_updates`
--
ALTER TABLE `parking_updates`
  ADD CONSTRAINT `parking_updates_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_vehicle_capacities`
--
ALTER TABLE `parking_vehicle_capacities`
  ADD CONSTRAINT `parking_vehicle_capacities_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_vehicle_capacities_ibfk_2` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_penalties`
--
ALTER TABLE `user_penalties`
  ADD CONSTRAINT `user_penalties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_penalties_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_specifications`
--
ALTER TABLE `user_specifications`
  ADD CONSTRAINT `user_specifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_specifications_ibfk_2` FOREIGN KEY (`specification_type_id`) REFERENCES `user_specification_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD CONSTRAINT `user_vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_vehicles_ibfk_2` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
