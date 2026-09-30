<?php
session_start();
include 'includes/header.php';
$page_title = "Reservar Parqueo - Parking SV";
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$parking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información completa del parqueo
$stmt = $conex->prepare("
    SELECT p.*, l.*, u.business_name, u.email as owner_email,
           (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE parking_id = p.id) AS review_count
    FROM parkings p
    JOIN locations l ON p.location_id = l.id
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

// Obtener imagen principal del parqueo
$images_stmt = $conex->prepare("SELECT * FROM parking_images WHERE parking_id = ? ORDER BY sort_order LIMIT 1");
$images_stmt->bind_param("i", $parking_id);
$images_stmt->execute();
$main_image = $images_stmt->get_result()->fetch_assoc();

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

// Obtener vehículos del usuario que tengan capacidad reservable
$user_id = $_SESSION['user_id'];
$vehicles_stmt = $conex->prepare("
    SELECT uv.vehicle_type_id, vt.category_name, vt.icon,
           pvc.capacity, pvc.reservable_vehicle_c
    FROM user_vehicles uv 
    JOIN vehicle_types vt ON uv.vehicle_type_id = vt.id 
    LEFT JOIN parking_vehicle_capacities pvc ON (pvc.vehicle_type_id = uv.vehicle_type_id AND pvc.parking_id = ?)
    WHERE uv.user_id = ?
    AND (pvc.reservable_vehicle_c > 0 OR pvc.capacity > 0)
");
$vehicles_stmt->bind_param("ii", $parking_id, $user_id);
$vehicles_stmt->execute();
$user_vehicles = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Verificar si hay vehículos disponibles para reservar
if (count($user_vehicles) == 0) {
    echo "<div class='container'><p class='error'>No tienes vehículos que puedan reservar en este parqueo o no hay capacidad reservable.</p></div>";
    include 'includes/footer.php';
    exit;
}

// Obtener información del usuario actual
$user_stmt = $conex->prepare("SELECT full_name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
?>

<link rel="stylesheet" href="assets/css/pages/reservar-parqueo.css">

<div class="reservation-container">
    <!-- Header con progreso -->
    <div class="reservation-progress">
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <span>Información</span>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <span>Fechas</span>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <span>Confirmar</span>
            </div>
        </div>
    </div>

    <div class="reservation-header">
        <a href="detalles-parqueo.php?id=<?= $parking_id ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver al parqueo
        </a>
        <h1>Reservar Parqueo</h1>
    </div>

    <div class="reservation-content">
        <!-- Información del parqueo -->
        <div class="parking-info-section">
            <div class="parking-card">
                <?php if ($main_image): ?>
                    <img src="<?= htmlspecialchars($main_image['image_url']) ?>" alt="<?= htmlspecialchars($parking['name']) ?>" class="parking-image">
                <?php else: ?>
                    <img src="assets/images/parking-default.png" alt="Imagen predeterminada" class="parking-image">
                <?php endif; ?>
                
                <div class="parking-details">
                    <h2><?= htmlspecialchars($parking['name']) ?></h2>
                    <p class="business-name">@<?= htmlspecialchars($parking['business_name']) ?></p>
                    
                    <div class="parking-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($parking['street_address']) ?>, <?= htmlspecialchars($parking['municipality']) ?></span>
                    </div>
                    
                    <div class="parking-meta">
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($parking['contact_phone']) ?></span>
                        </div>
                        <?php if ($parking['contact_email']): ?>
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($parking['contact_email']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="parking-rating">
                        <?php if ($parking['review_count'] > 0): ?>
                            <span class="stars"><?= str_repeat('★', round($parking['avg_rating'])) ?></span>
                            <span class="rating-value"><?= number_format($parking['avg_rating'], 1) ?> (<?= $parking['review_count'] ?> reseñas)</span>
                        <?php else: ?>
                            <span class="new-badge">Nuevo</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Información del usuario -->
            <div class="user-card">
                <h3><i class="fas fa-user"></i> Tu Información</h3>
                <div class="user-details">
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($current_user['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($current_user['email']) ?></p>
                </div>
            </div>
        </div>

        <!-- Formulario de reserva -->
        <div class="reservation-form-section">
            <div class="form-step active" data-step="1">
                <h3><i class="fas fa-car"></i> Selecciona tu vehículo</h3>
                
                <div class="vehicle-selection">
                    <?php foreach ($user_vehicles as $vehicle): ?>
                        <div class="vehicle-option" data-vehicle-id="<?= $vehicle['vehicle_type_id'] ?>">
                            <div class="vehicle-icon">
                                <i class="fas fa-<?= htmlspecialchars($vehicle['icon']) ?>"></i>
                            </div>
                            <div class="vehicle-info">
                                <h4><?= htmlspecialchars($vehicle['category_name']) ?></h4>
                                <div class="vehicle-capacity">
                                    <?php if ($vehicle['reservable_vehicle_c'] > 0): ?>
                                        <span class="capacity-badge"><?= $vehicle['reservable_vehicle_c'] ?> espacios reservables</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="vehicle-select">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-navigation">
                    <button type="button" class="btn-next" onclick="nextStep(2)">Continuar <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="form-step" data-step="2">
                <h3><i class="fas fa-calendar-alt"></i> Selecciona fecha y hora</h3>
                
                <form id="reservation-form">
                    <input type="hidden" name="parking_id" value="<?= $parking_id ?>">
                    <input type="hidden" name="vehicle_type_id" id="selected_vehicle_id">
                    
                    <div class="datetime-selection">
                        <div class="date-time-group">
                            <div class="form-group">
                                <label for="start_date">
                                    <i class="fas fa-play-circle"></i> Fecha de inicio
                                </label>
                                <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="form-group">
                                <label for="start_time">
                                    <i class="fas fa-clock"></i> Hora de inicio
                                </label>
                                <select id="start_time" name="start_time" required>
                                    <option value="">-- Selecciona hora --</option>
                                </select>
                            </div>
                        </div>

                        <div class="date-time-group">
                            <div class="form-group">
                                <label for="end_date">
                                    <i class="fas fa-stop-circle"></i> Fecha de fin
                                </label>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>

                            <div class="form-group">
                                <label for="end_time">
                                    <i class="fas fa-clock"></i> Hora de fin
                                </label>
                                <select id="end_time" name="end_time" required>
                                    <option value="">-- Selecciona hora --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Información de tarifas -->
                    <div class="fees-preview" id="fees-preview">
                        <div class="fees-header">
                            <h4><i class="fas fa-receipt"></i> Resumen de reserva</h4>
                        </div>
                        <div class="fees-content" id="fees-content">
                            <div class="loading-fees">
                                <i class="fas fa-spinner fa-spin"></i> Calculando tarifa...
                            </div>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <button type="button" class="btn-prev" onclick="prevStep(1)"><i class="fas fa-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn-next" onclick="nextStep(3)">Continuar <i class="fas fa-arrow-right"></i></button>
                    </div>
                </form>
            </div>

            <div class="form-step" data-step="3">
                <h3><i class="fas fa-check-circle"></i> Confirma tu reserva</h3>
                
                <div class="confirmation-summary" id="confirmation-summary">
                    <!-- Se llena con JavaScript -->
                </div>

                <div class="form-navigation">
                    <button type="button" class="btn-prev" onclick="prevStep(2)"><i class="fas fa-arrow-left"></i> Anterior</button>
                    <button type="button" class="btn-confirm" onclick="showPasswordModal()">
                        <i class="fas fa-lock"></i> Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de verificación de contraseña -->
<div id="password-modal" class="modal">
    <div class="modal-content password-modal">
        <div class="modal-header">
            <h3><i class="fas fa-shield-alt"></i> Verificación de Identidad</h3>
            <p>Por favor ingresa tu contraseña para confirmar la reserva</p>
        </div>
        
        <form id="password-form">
            <div class="form-group">
                <label for="user_password">Contraseña:</label>
                <input type="password" id="user_password" name="password" required 
                       placeholder="Ingresa tu contraseña">
                <div class="password-toggle">
                    <i class="fas fa-eye" id="toggle-password"></i>
                </div>
            </div>
            
            <div class="attempts-info">
                <span id="attempts-count">Intentos restantes: 3</span>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closePasswordModal()">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Verificar y Reservar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de éxito -->
<div id="success-modal" class="modal">
    <div class="modal-content success-modal">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>¡Reserva Confirmada!</h3>
        <p>Tu reserva ha sido procesada exitosamente</p>
        <div class="success-actions">
            <button type="button" class="btn-primary" onclick="redirectToConfirmation()">
                <i class="fas fa-qrcode"></i> Ver Código QR
            </button>
        </div>
    </div>
</div>

<script>
    // Pasar datos PHP a JavaScript
    window.reservationData = {
        parkingId: <?= $parking_id ?>,
        userId: <?= $user_id ?>,
        parkingName: "<?= htmlspecialchars($parking['name']) ?>",
        userEmail: "<?= htmlspecialchars($current_user['email']) ?>",
        attempts: 3
    };
</script>

<script src="assets/js/pages/reservar-parqueo.js"></script>
<?php include 'includes/footer.php'; ?>