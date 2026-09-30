<?php
session_start();
include 'includes/header.php';
$page_title = "Parking SV - Detalles de tu Parqueo";
require_once 'conexion.php';

$parking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conex->prepare("
    SELECT p.*, l.*, pc.general_capacity, pc.reservable_capacity, pc.disability_spaces, pc.pregnant_people_spaces, pc.taxi_spaces, u.business_name,
        (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE parking_id = p.id) AS review_count
    FROM parkings p
    JOIN locations l ON p.location_id = l.id
    JOIN parking_capacities pc ON pc.parking_id = p.id
    JOIN users u ON p.owner_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $parking_id);
$stmt->execute();
$result = $stmt->get_result();
$parking = $result->fetch_assoc();

if (!$parking) {
    echo "<div class='container'><p class='error'>Parqueo no encontrado</p></div>";
    include 'includes/footer.php';
    exit;
}

// Obtener imágenes del parqueo
$images_stmt = $conex->prepare("SELECT * FROM parking_images WHERE parking_id = ? ORDER BY sort_order");
$images_stmt->bind_param("i", $parking_id);
$images_stmt->execute();
$images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener tarifas del parqueo
$fees_stmt = $conex->prepare("
    SELECT pf.*, vt.category_name, vt.icon 
    FROM parking_fees pf
    JOIN vehicle_types vt ON pf.vehicle_type_id = vt.id
    WHERE pf.parking_id = ?
");
$fees_stmt->bind_param("i", $parking_id);
$fees_stmt->execute();
$fees = $fees_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener servicios del parqueo
$services_stmt = $conex->prepare("
    SELECT s.name, s.icon 
    FROM parking_services ps 
    JOIN services s ON ps.service_id = s.id 
    WHERE ps.parking_id = ?
");
$services_stmt->bind_param("i", $parking_id);
$services_stmt->execute();
$services = $services_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener capacidades por tipo de vehículo
$vehicle_capacities_stmt = $conex->prepare("
    SELECT vt.id as vehicle_type_id, vt.category_name, vt.icon, pvc.capacity
    FROM parking_vehicle_capacities pvc
    JOIN vehicle_types vt ON pvc.vehicle_type_id = vt.id
    WHERE pvc.parking_id = ?
");
$vehicle_capacities_stmt->bind_param("i", $parking_id);
$vehicle_capacities_stmt->execute();
$vehicle_capacities = $vehicle_capacities_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Decodificar el horario JSON
$schedule = json_decode($parking['schedule'], true);

// ---- FUNCIONES AUXILIARES PARA HORARIO ----
function convertirHoraAMPM($hora24) {
    if (!$hora24 || strtolower($hora24) === 'cerrado') return ucfirst($hora24);
    $horaObj = DateTime::createFromFormat('H:i', $hora24);
    return $horaObj ? $horaObj->format('g:i A') : $hora24;
}
// Normaliza cualquier slot, sea objeto o array
function obtenerSlotsHorario($horas) {
    if (!$horas) return [];
    // Si es 'cerrado'
    if (isset($horas['apertura']) && strtolower($horas['apertura']) === 'cerrado') {
        return [['apertura' => 'cerrado', 'cierre' => 'cerrado']];
    }
    // Si es un solo horario (objeto), volver array
    if (isset($horas['apertura']) && isset($horas['cierre'])) {
        return [ $horas ];
    }
    // Si es array de slots
    if (is_array($horas) && isset($horas[0]['apertura'])) {
        return $horas;
    }
    // Si es array vacío
    return [];
}

// Obtener nombre de categoría
$category_stmt = $conex->prepare("SELECT name FROM parking_categories WHERE id = ?");
$category_stmt->bind_param("i", $parking['category_id']);
$category_stmt->execute();
$category_result = $category_stmt->get_result();
$category = $category_result->fetch_assoc()['name'];

// Obtener restricciones de comportamiento
$behavioral_restrictions_stmt = $conex->prepare("
    SELECT rt.name 
    FROM parking_restriction_items pri
    JOIN restriction_types rt ON pri.restriction_type_id = rt.id
    WHERE pri.parking_id = ?
");
$behavioral_restrictions_stmt->bind_param("i", $parking_id);
$behavioral_restrictions_stmt->execute();
$behavioral_restrictions = $behavioral_restrictions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener restricciones físicas
$physical_restrictions_stmt = $conex->prepare("
    SELECT max_height, max_speed 
    FROM parking_restrictions 
    WHERE parking_id = ?
");
$physical_restrictions_stmt->bind_param("i", $parking_id);
$physical_restrictions_stmt->execute();
$physical_restrictions = $physical_restrictions_stmt->get_result()->fetch_assoc();

// Obtener reseñas
$reviews_stmt = $conex->prepare("
    SELECT r.*, u.full_name, u.profile_picture
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.parking_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $parking_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Verificar si el usuario ya ha reseñado este parqueo
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $review_check_stmt = $conex->prepare("SELECT id FROM reviews WHERE parking_id = ? AND user_id = ?");
    $review_check_stmt->bind_param("ii", $parking_id, $user_id);
    $review_check_stmt->execute();
    $user_has_reviewed = $review_check_stmt->get_result()->num_rows > 0;
}
// Improved login check
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $review_check_stmt = $conex->prepare("SELECT id FROM reviews WHERE parking_id = ? AND user_id = ?");
    $review_check_stmt->bind_param("ii", $parking_id, $user_id);
    $review_check_stmt->execute();
    $user_has_reviewed = $review_check_stmt->get_result()->num_rows > 0;
}
?>

<link rel="stylesheet" href="assets/css/pages/detalles-parqueo.css">

<div class="parking-container">
    <!-- Sección superior con nombre, rating y botones -->
    <div class="parking-header">
                <!-- Botón de volver universal -->
        <div class="universal-back-container">
            <a href="#" class="universal-back-button" id="universalBackButton">
                <img src="img sources/volver-bg.png" alt="Volver atrás" class="universal-back-icon">
                <span class="universal-back-text">Volver</span>
            </a>
        </div>
        <div class="parking-info">
            <h1 class="parking-title"><?= htmlspecialchars($parking['name']) ?></h1>
            <p class="business-name">@<?= htmlspecialchars($parking['business_name']) ?></p>
            
            <div class="rating-category">
                <div class="rating">
                    <?php if ($parking['review_count'] > 0): ?>
                        <span class="stars"><?= str_repeat('★', round($parking['avg_rating'])) ?></span>
                        <span class="rating-value"><?= number_format($parking['avg_rating'], 1) ?>★</span>
                    <?php else: ?>
                        <span class="new-badge">Nuevo</span>
                    <?php endif; ?>
                </div>
                
                <div class="category category-blue">
                    <?= ucfirst(str_replace('_', ' ', $category)) ?>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="share-btn share-btn-yellow" onclick="openShareModal()">
                <i class="fas fa-share-alt"></i> Compartir
            </button>
        </div>
    </div>
    
    <!-- Galería de imágenes -->
<div class="image-gallery" id="parking-gallery" data-count="<?= min(5, count($images)) ?>">
    <?php if (count($images) > 0): ?>
        <?php for ($i = 0; $i < min(5, count($images)); $i++): ?>
            <?php $image = $images[$i]; ?>
            <div class="gallery-item <?= $i === 0 ? 'featured' : '' ?>" 
                data-index="<?= $i ?>"
                onclick="openFullscreenGallery(<?= $i ?>)">
                <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                    alt="Imagen del parqueo <?= $parking['name'] ?>">
                <?php if ($i === 4 && (count($images) > 5)): ?>
                    <div class="extra-count">+<?= count($images) - 5 ?></div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    <?php else: ?>
        <div class="gallery-item featured">
            <img src="assets/images/parking-default.png" alt="Imagen predeterminada">
        </div>
    <?php endif; ?>
</div>
    
    <!-- Botones de información -->
    <div class="info-buttons">
        <button class="info-btn" onclick="openModal('location')">
            <i class="fas fa-map-marker-alt"></i> Ubicación
        </button>
        <button class="info-btn" onclick="openModal('capacity')">
            <i class="fas fa-car"></i> Capacidad
        </button>
        
        <button class="info-btn" onclick="openModal('google-map')" <?= empty($parking['google_maps_link']) ? 'disabled' : '' ?>>
            <i class="fab fa-google"></i> Ver mapas
        </button>
        
        <button class="info-btn" onclick="openModal('schedule')">
            <i class="far fa-clock"></i> Horario
        </button>
    </div>

<!-- SECCIÓN DE RESERVA SIMPLIFICADA -->
<div class="reservation-section">
    <h3><i class="fas fa-calendar-check"></i> Reservar Espacio</h3>
    
    <?php 
    // Consulta para obtener la capacidad reservable total
    $reservable_stmt = $conex->prepare("
        SELECT reservable_capacity 
        FROM parking_capacities 
        WHERE parking_id = ?
    ");
    $reservable_stmt->bind_param("i", $parking_id);
    $reservable_stmt->execute();
    $reservable_result = $reservable_stmt->get_result();
    
    $reservable_capacity = 0;
    if ($reservable_result->num_rows > 0) {
        $reservable_data = $reservable_result->fetch_assoc();
        $reservable_capacity = $reservable_data['reservable_capacity'] ?? 0;
    }
    
    // Consulta para obtener capacidades reservables por tipo de vehículo
    $vehicle_reservable_stmt = $conex->prepare("
        SELECT vt.category_name, vt.icon, pvc.reservable_vehicle_c
        FROM parking_vehicle_capacities pvc
        JOIN vehicle_types vt ON pvc.vehicle_type_id = vt.id
        WHERE pvc.parking_id = ? AND pvc.reservable_vehicle_c > 0
    ");
    $vehicle_reservable_stmt->bind_param("i", $parking_id);
    $vehicle_reservable_stmt->execute();
    $reservable_by_vehicle = $vehicle_reservable_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $has_reservable_spaces = $reservable_capacity > 0 || count($reservable_by_vehicle) > 0;
    ?>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($has_reservable_spaces): ?>
            <a href="reservar-parqueo.php?id=<?= $parking_id ?>" class="reserve-btn">
                <i class="fas fa-calendar-plus"></i> Reservar Ahora
            </a>
        <?php else: ?>
            <div class="no-reservation-msg">
                <i class="fas fa-info-circle"></i>
                <p>Este parqueo no ofrece reservas en este momento</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="login-required-msg">
            <i class="fas fa-user-lock"></i>
            <p>Inicia sesión para reservar un espacio</p>
        </div>
    <?php endif; ?>
</div>

    <!-- Contacto del parqueo -->
    <div class="parking-contact section-contact">
        <h3><i class="fas fa-address-card"></i> Contacto del Parqueo</h3>
        <div class="contact-info">
            <?php if (!empty($parking['contact_name'])): ?>
                <p><i class="fas fa-user"></i> <strong>Contacto:</strong> <?= htmlspecialchars($parking['contact_name']) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($parking['contact_phone'])): ?>
                <p><i class="fas fa-phone"></i> <strong>Teléfono:</strong> <?= htmlspecialchars($parking['contact_phone']) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($parking['contact_email'])): ?>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($parking['contact_email']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Servicios disponibles -->
    <div class="parking-services section-services">
        <h3><i class="fas fa-concierge-bell"></i> Servicios</h3>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <i class="fas <?= htmlspecialchars($service['icon']) ?>"></i> <?= ucfirst($service['name']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

            <!--Tarifas-->
            <h2><i class="fas fa-money-bill-wave"></i> Tarifas</h2>
<div class="fees-grid">
    <?php foreach ($fees as $fee): ?>
        <div class="fee-item" data-fee-type="<?= $fee['fee_type'] ?>">
            <div class="fee-icon">
                <i class="fas fa-<?= htmlspecialchars($fee['icon']) ?>"></i>
            </div>
            <div class="fee-details">
                <div class="fee-header">
                    <span class="vehicle-type"><?= htmlspecialchars($fee['category_name']) ?></span>
                    <span class="fee-price">
                        <?php 
                        // Format price with dollar sign if numeric
                        if (is_numeric(str_replace(',', '', $fee['price']))) {
                            echo '$' . number_format((float)str_replace(',', '', $fee['price']), 2);
                        } else {
                            echo htmlspecialchars($fee['price']);
                        }
                        ?>
                    </span>
                </div>
                <div class="fee-meta">
                    <span class="fee-type-tag"><?= ucfirst($fee['fee_type']) ?></span>
                    <span class="fee-unit">por <?= $fee['time_unit'] ?></span>
                    <?php if ($fee['applies_to'] !== 'all_week'): ?>
                        <span class="fee-applies">(<?= $fee['applies_to'] === 'weekdays' ? 'Días de semana' : 'Fines de semana' ?>)</span>
                    <?php endif; ?>
                    <?php if ($fee['valid_from'] && $fee['valid_to']): ?>
                        <span class="fee-validity">Válido: <?= date('d/m/Y', strtotime($fee['valid_from'])) ?> - <?= date('d/m/Y', strtotime($fee['valid_to'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    <!-- Descripción del parqueo -->
    <div class="parking-description section-description">
        <h3><i class="fas fa-info-circle"></i> Descripción</h3>
        <p><?= nl2br(htmlspecialchars($parking['description'])) ?></p>
    </div>

    <!-- Restricciones -->
    <div class="parking-restrictions section-restrictions">
        <h3><i class="fas fa-exclamation-triangle"></i> Restricciones</h3>
        <div class="restrictions-container">
            <div class="restrictions-section">
                <h4>Reglas de Comportamiento</h4>
                <div class="restrictions-grid">
                    <?php if (!empty($behavioral_restrictions)): ?>
                        <?php foreach ($behavioral_restrictions as $restriction): ?>
                            <div class="restriction-item">
                                <i class="fas fa-ban"></i> <?= ucfirst(str_replace('_', ' ', $restriction['name'])) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay reglas de comportamiento específicas</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="restrictions-section">
                <h4>Restricciones Físicas</h4>
                <div class="physical-restrictions">
                    <?php if ($physical_restrictions): ?>
                        <?php if (!empty($physical_restrictions['max_height'])): ?>
                            <p><i class="fas fa-arrows-alt-v"></i> <strong>Altura máxima:</strong> <?= $physical_restrictions['max_height'] ?> metros</p>
                        <?php endif; ?>
                        <?php if (!empty($physical_restrictions['max_speed'])): ?>
                            <p><i class="fas fa-tachometer-alt"></i> <strong>Velocidad máxima:</strong> <?= $physical_restrictions['max_speed'] ?> km/h</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No hay restricciones físicas específicas</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ubicación precisa con mapas -->
    <div class="parking-maps">
        <h3><i class="fas fa-map-marked-alt"></i> Ubicación precisa con mapas</h3>
        <div class="maps-container">
            <?php if (!empty($parking['google_maps_link'])): ?>
                <div class="map-option">
                    <button class="map-btn view-map tall-btn" onclick="openModal('google-map')">
                        <i class="fab fa-google"></i> Ver en Google Maps
                    </button>
                    <button class="map-btn copy-link tall-btn" onclick="copyToClipboard('<?= htmlspecialchars($parking['google_maps_link']) ?>', 'Google Maps')">
                        <i class="fas fa-copy"></i> Copiar enlace
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($parking['waze_link'])): ?>
                <div class="map-option">
                    <button class="map-btn view-map tall-btn" onclick="openModal('waze-map')">
                        <i class="fab fa-waze"></i> Ver en Waze
                    </button>
                    <button class="map-btn copy-link tall-btn" onclick="copyToClipboard('<?= htmlspecialchars($parking['waze_link']) ?>', 'Waze')">
                        <i class="fas fa-copy"></i> Copiar enlace
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reseñas y comentarios -->
    <div class="parking-reviews">
        <h3><i class="fas fa-star"></i> Reseñas y Comentarios</h3>
        
        <?php if (isset($_SESSION['user_id']) && !$user_has_reviewed): ?>
            <div class="new-review-form">
                <h4>Deja tu reseña</h4>
                <form id="review-form" method="POST" action="procesar_resena.php">
                    <input type="hidden" name="parking_id" value="<?= $parking_id ?>">
                    
                    <div class="rating-stars">
                        <span class="star" data-value="1">☆</span>
                        <span class="star" data-value="2">☆</span>
                        <span class="star" data-value="3">☆</span>
                        <span class="star" data-value="4">☆</span>
                        <span class="star" data-value="5">☆</span>
                        <input type="hidden" id="rating-value" name="rating" value="0">
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" placeholder="Escribe tu experiencia con este parqueo..." required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Enviar reseña</button>
                </form>
            </div>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <p class="login-message">Inicia sesión para dejar una reseña</p>
        <?php endif; ?>
        
        <div class="reviews-list">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <img src="<?= $review['profile_picture'] ?? 'assets/images/default-avatar.png' ?>" alt="Avatar de <?= htmlspecialchars($review['full_name']) ?>" class="review-avatar">
                            <div class="review-user">
                                <h4><?= htmlspecialchars($review['full_name']) ?></h4>
                                <div class="review-rating">
                                    <?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-content">
                            <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                            <small><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-reviews">No hay reseñas todavía. ¡Sé el primero en opinar!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para ver imágenes -->
<div id="fullscreen-gallery" class="fullscreen-gallery">
    <img src="img sources/x-bg.png" class="close-gallery" alt="Cerrar">
    <div class="gallery-nav">
        <img src="img sources/izquierda-bg.png" class="nav-btn prev-btn" alt="Anterior">
        <img src="img sources/derecha-bg.png" class="nav-btn next-btn" alt="Siguiente">
    </div>
    <div class="gallery-content">
        <img id="fullscreen-image" src="" alt="">
        <div class="image-counter"></div>
    </div>
</div>

<!-- Modal para compartir -->
<div id="share-modal" class="modal">
    <div class="modal-content">
        <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('share-modal')" alt="Cerrar">
        <h3>Compartir este parqueo</h3>
        <div class="share-options">
            <button class="share-option" onclick="shareOnFacebook()">
                <i class="fab fa-facebook"></i> Facebook
            </button>
            <button class="share-option" onclick="shareOnTwitter()">
                <i class="fab fa-twitter"></i> Twitter
            </button>
            <button class="share-option" onclick="shareOnWhatsApp()">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
            <button class="share-option" onclick="copyLink()">
                <i class="fas fa-link"></i> Copiar enlace
            </button>
        </div>
    </div>
</div>

<!-- Modal para ubicación -->
<div id="location-modal" class="modal">
    <div class="modal-content">
    <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('location-modal')" alt="Cerrar">
    <h3>Ubicación</h3>
        <div class="modal-body">
            <p><strong>Departamento:</strong> <?= htmlspecialchars($parking['department']) ?></p>
            <p><strong>Municipio:</strong> <?= htmlspecialchars($parking['municipality']) ?></p>
            <p><strong>Calle:</strong> <?= htmlspecialchars($parking['street_address']) ?></p>
            <p><strong>Referencia:</strong> <?= htmlspecialchars($parking['reference_address']) ?></p>
        </div>
    </div>
</div>

<!-- MODAL DE CAPACIDAD MEJORADO -->
<div id="capacity-modal" class="modal">
    <div class="modal-content">
        <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('capacity-modal')" alt="Cerrar">
        <h3><i class="fas fa-car"></i> Capacidad</h3>
        <div class="modal-body">
            <div class="capacity-summary">
                <p><i class="fas fa-car"></i> <strong>Capacidad general:</strong> <?= $parking['general_capacity'] ?> vehículos</p>
                
                <?php if (!empty($parking['reservable_capacity'])): ?>
                <p><i class="fas fa-calendar-check"></i> <strong>Capacidad reservable general:</strong> <?= $parking['reservable_capacity'] ?> espacios</p>
                <?php endif; ?>
                
                <p><i class="fas fa-wheelchair"></i> <strong>Espacios para discapacitados:</strong> <?= $parking['disability_spaces'] ?></p>
                
                <?php if (isset($parking['pregnant_people_spaces']) && $parking['pregnant_people_spaces'] > 0): ?>
                <p><i class="fas fa-person-pregnant" style="color: #e91e63;"></i> <strong>Espacios para futuras mamás:</strong> <?= $parking['pregnant_people_spaces'] ?></p>
                <?php endif; ?>
                
                <?php if (isset($parking['taxi_spaces']) && $parking['taxi_spaces'] > 0): ?>
                <p><i class="fas fa-taxi" style="color: #FFD700;"></i> <strong>Espacios para taxis:</strong> <?= $parking['taxi_spaces'] ?></p>
                <?php endif; ?>
                
                <?php 
                // Obtener capacidad de bicicletas
                $bike_capacity_stmt = $conex->prepare("
                    SELECT pvc.capacity 
                    FROM parking_vehicle_capacities pvc
                    WHERE pvc.parking_id = ? AND pvc.vehicle_type_id = 9
                ");
                $bike_capacity_stmt->bind_param("i", $parking_id);
                $bike_capacity_stmt->execute();
                $bike_capacity_result = $bike_capacity_stmt->get_result();
                
                if ($bike_capacity_result->num_rows > 0) {
                    $bike_capacity = $bike_capacity_result->fetch_assoc()['capacity'];
                    if ($bike_capacity > 0) {
                        echo '<p><i class="fas fa-bicycle" style="color: #4CAF50;"></i> <strong>Espacios para bicicletas:</strong> ' . $bike_capacity . '</p>';
                    }
                }
                ?>
            </div>
            
            <?php if (!empty($vehicle_capacities)): ?>
                <h4><i class="fas fa-list"></i> Capacidad por tipo de vehículo</h4>
                <div class="vehicle-capacities">
                    <?php foreach ($vehicle_capacities as $capacity): ?>
                        <div class="vehicle-capacity-item">
                            <div class="vehicle-icon">
                                <i class="fas fa-<?= htmlspecialchars($capacity['icon']) ?>"></i>
                            </div>
                            <div class="vehicle-info">
                                <div class="vehicle-name"><?= htmlspecialchars($capacity['category_name']) ?></div>
                                <div class="vehicle-capacity"><?= $capacity['capacity'] ?> espacios</div>
                                <?php 
                                // Mostrar capacidad reservable por tipo de vehículo si está disponible
                                $reservable_stmt = $conex->prepare("
                                    SELECT reservable_vehicle_c 
                                    FROM parking_vehicle_capacities 
                                    WHERE parking_id = ? AND vehicle_type_id = ?
                                ");
                                $reservable_stmt->bind_param("ii", $parking_id, $capacity['vehicle_type_id']);
                                $reservable_stmt->execute();
                                $reservable_result = $reservable_stmt->get_result();
                                
                                if ($reservable_result->num_rows > 0) {
                                    $reservable_data = $reservable_result->fetch_assoc();
                                    if ($reservable_data['reservable_vehicle_c'] > 0) {
                                        echo '<div class="vehicle-reservable">' . $reservable_data['reservable_vehicle_c'] . ' reservables</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay información de capacidad por tipo de vehículo</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para horario -->
<div id="schedule-modal" class="modal">
    <div class="modal-content">
        <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('schedule-modal')" alt="Cerrar">
        <h3>Horario</h3>
        <div class="modal-body">
            <table class="schedule-table">
                <?php
                $dias_orden = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
                $is_24_7 = false;
                $has_schedule_data = false;

                // First check if parking is marked as 24/7 in database
                if ($parking['is_24_7']) {
                    $is_24_7 = true;
                } else {
                    // If not marked as 24/7, check schedule data
                    foreach ($dias_orden as $dia) {
                        $slots = isset($schedule[$dia]) ? obtenerSlotsHorario($schedule[$dia]) : [];
                        
                        if (!empty($slots)) {
                            $has_schedule_data = true;
                            $is_day_24_7 = false;
                            
                            if (count($slots) === 1) {
                                $slot = $slots[0];
                                if (isset($slot['apertura']) && isset($slot['cierre']) &&
                                    (($slot['apertura'] === '00:00' && $slot['cierre'] === '24:00') ||
                                     ($slot['apertura'] === '00:00' && $slot['cierre'] === '00:00') ||
                                     ($slot['apertura'] === '00:00' && $slot['cierre'] === '23:59'))) {
                                    $is_day_24_7 = true;
                                }
                            }
                            
                            // If any day is not 24/7, the whole parking isn't 24/7
                            if (!$is_day_24_7) {
                                $is_24_7 = false;
                                break;
                            } else {
                                $is_24_7 = true;
                            }
                        }
                    }
                }

                if ($is_24_7): ?>
                    <tr>
                        <td colspan="2" style="text-align: center; font-weight: bold;">
                            <i class="fas fa-clock"></i> Abierto 24/7 todos los días
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dias_orden as $dia):
                        $slots = isset($schedule[$dia]) ? obtenerSlotsHorario($schedule[$dia]) : [];
                    ?>
                        <tr>
                            <th><?= ucfirst($dia) ?></th>
                            <td>
                                <?php
                                if (empty($slots)) {
                                    echo "Cerrado";
                                } else {
                                    foreach ($slots as $slot) {
                                        if (isset($slot['apertura']) && strtolower($slot['apertura']) === 'cerrado') {
                                            echo "Cerrado";
                                        } else {
                                            echo convertirHoraAMPM($slot['apertura']) . " - " . convertirHoraAMPM($slot['cierre']);
                                        }
                                        if ($slot !== end($slots)) echo "<br>";
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Google Maps -->
<div id="google-map-modal" class="modal">
    <div class="modal-content large-modal">
        <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('google-map-modal')" alt="Cerrar">
        <h3>Ubicación en Google Maps</h3>
        <div class="modal-body">
            <?php if (!empty($parking['google_maps_link'])): ?>
                <iframe src="<?= htmlspecialchars($parking['google_maps_link']) ?>" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            <?php else: ?>
                <p>No hay mapa disponible para este parqueo.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Waze -->
<div id="waze-map-modal" class="modal">
    <div class="modal-content large-modal">
        <img src="img sources/x-bg.png" class="close-modal-btn" onclick="closeModal('waze-map-modal')" alt="Cerrar">
        <h3>Manejar con Waze</h3>
        <div class="modal-body">
            <?php if (!empty($parking['waze_link'])): ?>
                <iframe src="<?= htmlspecialchars($parking['waze_link']) ?>" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            <?php else: ?>
                <p>No hay ruta disponible para este parqueo.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="ad-card">
  <h3 class="ad-title">Anúnciate Aquí</h3>
  <p class="ad-subtitle">Ejemplos de anunciantes potenciales:</p>
  <ul class="ad-examples">
    <li>Lugares turísticos</li>
    <li>Carwash de autos</li>
    <li>Talleres mecánicos</li>
    <li>Tiendas de accesorios vehiculares</li>
    <li>Restaurantes cercanos</li>
    <li>Servicios de taxi</li>
  </ul>
</div>

<script>
    // Pass PHP data to JavaScript
    window.parkingData = {
        images: <?= json_encode(array_column($images, 'image_url')) ?>,
        parkingId: <?= $parking_id ?>
        // You can add other parking data here if needed
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="assets/js/pages/detalles-parqueo.js"></script>
<?php include 'includes/footer.php'; ?>