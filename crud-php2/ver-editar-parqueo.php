<?php
session_start();
include 'includes/header.php';
$page_title = "Parking SV - Editar tu Parqueo";
require_once 'conexion.php';

// Check if user is logged in and is the owner
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$parking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify ownership
$stmt = $conex->prepare("SELECT owner_id FROM parkings WHERE id = ?");
$stmt->bind_param("i", $parking_id);
$stmt->execute();
$result = $stmt->get_result();
$parking_owner = $result->fetch_assoc();

if (!$parking_owner || $parking_owner['owner_id'] != $_SESSION['user_id']) {
    echo "<div class='container'><p class='error'>No tienes permiso para editar este parqueo</p></div>";
    include 'includes/footer.php';
    exit;
}

// Fetch parking data
$stmt = $conex->prepare("
    SELECT p.*, l.*, pc.general_capacity, pc.disability_spaces, u.business_name,
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
    SELECT vt.category_name, vt.icon, pvc.capacity
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['success_message'] = "¡Las mejoras se han publicado exitosamente!";
    header("Location: editar-parqueo.php?id=$parking_id");
    exit();
}
?>

<link rel="stylesheet" href="assets/css/pages/ver-editar-parqueo.css">

<!-- Campo oculto para almacenar el ID del parqueo -->
<input type="hidden" id="parking_id" value="<?= $parking_id ?>">


<div class="container">
    <!-- Header -->
    <div class="edit-header">
        <div>
            <h1 class="edit-title">Editar Parqueo: <?= htmlspecialchars($parking['name']) ?></h1>
            <p class="business-name">@<?= htmlspecialchars($parking['business_name']) ?></p>
        </div>
        <div>
            <a href="detalles-parqueo.php?id=<?= $parking_id ?>" class="btn btn-primary">
                <i class="fas fa-eye"></i> Ver Página Pública
            </a>
        </div>
    </div>
    
    <!-- Information Sections -->
    
    <!-- 1. Basic Information -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-info-circle"></i> Información Básica</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('basic')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="form-group">
                <label>Nombre del Parqueo</label>
                <p class="form-control-static"><?= htmlspecialchars($parking['name']) ?></p>
            </div>
            
            <div class="form-group">
                <label>Categoría</label>
                <p class="form-control-static"><?= ucfirst(str_replace('_', ' ', $category)) ?></p>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <p><?= nl2br(htmlspecialchars($parking['description'])) ?></p>
            </div>
        </div>
    </div>
    
    <!-- 2. Images -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-images"></i> Imágenes del Parqueo</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('images')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
                <button class="edit-btn" onclick="openEditModal('images')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="image-grid">
                <?php if (count($images) > 0): ?>
                    <?php foreach ($images as $index => $image): ?>
                        <div class="image-item">
                            <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Parking image <?= $index ?>">
                            <div class="image-actions">
                                <button class="image-btn" onclick="setFeaturedImage(<?= $image['id'] ?>)">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button class="image-btn" onclick="deleteImage(<?= $image['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="image-item add-image">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 3. Capacity -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-car"></i> Capacidad</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('capacity')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="form-group">
                <label>Capacidad General</label>
                <p class="form-control-static"><?= $parking['general_capacity'] ?> vehículos</p>
            </div>
            
            <div class="form-group">
                <label>Espacios para Discapacitados</label>
                <p class="form-control-static"><?= $parking['disability_spaces'] ?> espacios</p>
            </div>
            
            <div class="form-group">
                <label>Capacidad por Tipo de Vehículo</label>
                <ul>
                    <?php foreach ($vehicle_capacities as $capacity): ?>
                        <li><?= $capacity['category_name'] ?>: <?= $capacity['capacity'] ?> espacios</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- 4. Fees -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-dollar-sign"></i> Tarifas</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('fees')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
                <button class="edit-btn" onclick="openEditModal('fees')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="fees-grid">
                <?php foreach ($fees as $fee): ?>
                    <div class="fee-item">
                        <div class="fee-icon">
                            <i class="fas fa-<?= htmlspecialchars($fee['icon']) ?>"></i>
                        </div>
                        <div class="fee-details">
                            <div class="fee-header">
                                <span class="vehicle-type"><?= htmlspecialchars($fee['category_name']) ?></span>
                                <span class="fee-price">
                                    $<?= number_format((float)str_replace(',', '', $fee['price']), 2) ?>
                                </span>
                            </div>
                            <div class="fee-meta">
                                <span class="fee-type-tag"><?= ucfirst($fee['fee_type']) ?></span>
                                <span class="fee-unit">por <?= $fee['time_unit'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- 5. Services -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-concierge-bell"></i> Servicios</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('services')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
                <button class="edit-btn" onclick="openEditModal('services')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <i class="fas <?= htmlspecialchars($service['icon']) ?>"></i> <?= ucfirst($service['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- 6. Schedule -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-clock"></i> Horario</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('schedule')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <table class="schedule-table">
                <?php if ($parking['is_24_7']): ?>
                    <tr>
                        <td colspan="2" style="text-align: center; font-weight: bold;">
                            <i class="fas fa-clock"></i> Abierto 24/7 todos los días
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dias_orden as $dia): ?>
                        <tr>
                            <th><?= ucfirst($dia) ?></th>
                            <td>
                                <?php if (!empty($schedule[$dia])): ?>
                                    <?= convertirHoraAMPM($schedule[$dia]['apertura']) ?> - <?= convertirHoraAMPM($schedule[$dia]['cierre']) ?>
                                <?php else: ?>
                                    Cerrado
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <!-- 7. Restrictions -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-ban"></i> Restricciones</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('restrictions')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
                <button class="edit-btn" onclick="openEditModal('restrictions')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <h4>Reglas de Comportamiento</h4>
            <ul>
                <?php foreach ($behavioral_restrictions as $restriction): ?>
                    <li><?= ucfirst(str_replace('_', ' ', $restriction['name'])) ?></li>
                <?php endforeach; ?>
            </ul>
            
            <h4>Restricciones Físicas</h4>
            <ul>
                <?php if ($physical_restrictions['max_height']): ?>
                    <li>Altura máxima: <?= $physical_restrictions['max_height'] ?> metros</li>
                <?php endif; ?>
                <?php if ($physical_restrictions['max_speed']): ?>
                    <li>Velocidad máxima: <?= $physical_restrictions['max_speed'] ?> km/h</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- 8. Location -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Ubicación</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('location')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="form-group">
                <label>Dirección</label>
                <p><?= htmlspecialchars($parking['street_address']) ?>, <?= htmlspecialchars($parking['municipality']) ?></p>
            </div>
            
            <div class="form-group">
                <label>Referencia</label>
                <p><?= htmlspecialchars($parking['reference_address']) ?></p>
            </div>
            
            <div class="form-group">
                <label>Enlaces</label>
                <div>
                    <?php if ($parking['google_maps_link']): ?>
                        <a href="<?= htmlspecialchars($parking['google_maps_link']) ?>" class="btn btn-primary" target="_blank">
                            <i class="fab fa-google"></i> Google Maps
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($parking['waze_link']): ?>
                        <a href="<?= htmlspecialchars($parking['waze_link']) ?>" class="btn btn-primary" target="_blank">
                            <i class="fab fa-waze"></i> Waze
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 9. Contact -->
    <div class="edit-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-address-book"></i> Contacto</h2>
            <div class="edit-actions">
                <button class="edit-btn" onclick="openEditModal('contact')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="section-content">
            <div class="form-group">
                <label>Nombre de Contacto</label>
                <p><?= htmlspecialchars($parking['contact_name']) ?></p>
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <p><?= htmlspecialchars($parking['contact_phone']) ?></p>
            </div>
            
            <div class="form-group">
                <label>Correo Electrónico</label>
                <p><?= htmlspecialchars($parking['contact_email']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Footer Actions -->
    <div class="footer-actions">
        <button class="btn btn-danger btn-lg" onclick="confirmDelete()">
            <i class="fas fa-trash-alt"></i> Eliminar Parqueo
        </button>
        <button type="submit" class="btn btn-warning btn-lg" onclick="submitChanges()">
            <i class="fas fa-paper-plane"></i> Publicar Mejoras
        </button>
    </div>
</div>

<!-- Edit Modals -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Editar Información</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script src="assets/js/pages/ver-editar-parqueo.js"></script>
<?php include 'includes/footer.php'; ?>