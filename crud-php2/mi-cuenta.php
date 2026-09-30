<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

// Incluir archivo de conexión y establecer conexión
require_once "conexion.php";

// Verificar conexión
if (!$conex) {
    die("Error de conexión a la base de datos");
}

$base_url = '/crud-php2/';

// Obtener ID de usuario de la sesión
$user_id = $_SESSION['user_id'];

// Manejar logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    header("Location: /crud-php2/index.php");
    exit();
}

// Manejar subida de foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile_picture') {
    // Directorio para avatares
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/crud-php2/public/uploads/avatars/';
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validar archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $file = $_FILES['profile_picture'];
    
    $response = ['success' => false, 'error' => '', 'newPath' => ''];

    if (!in_array($file['type'], $allowedTypes)) {
        $response['error'] = 'Solo se permiten imágenes (JPEG, PNG, GIF)';
    } elseif ($file['size'] > $maxSize) {
        $response['error'] = 'La imagen debe ser menor a 2MB';
    } else {
        // Generar nombre único
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $relativePath = '/crud-php2/public/uploads/avatars/' . $filename;
            
            // Actualizar en base de datos
            $updateStmt = $conex->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updateStmt->bind_param('si', $relativePath, $user_id);
            
            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['newPath'] = $relativePath;
                $_SESSION['user_profile_picture'] = $relativePath; // ACTUALIZAR SESIÓN
            } else {
                $response['error'] = 'Error al actualizar la base de datos';
            }
            $updateStmt->close();
        } else {
            $response['error'] = 'Error al subir el archivo';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Manejar actualización de vehículos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_vehicles') {
    // Configurar respuesta JSON
    header('Content-Type: application/json');
    
    try {
        // Obtener datos directamente de $_POST (ya no de json_decode)
        $response = ['success' => false, 'error' => '', 'vehicles' => []];
        
        if (!isset($_POST['vehicles'])) {
            throw new Exception('Datos de vehículos no proporcionados');
        }
        
        // Validar que los IDs sean números enteros
        $vehicle_ids = array_filter($_POST['vehicles'], function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (empty($vehicle_ids) && !empty($_POST['vehicles'])) {
            throw new Exception('IDs de vehículos no válidos');
        }
        
        // Resto del código permanece igual...
        $conex->begin_transaction();
        
        // Eliminar vehículos actuales
        $deleteStmt = $conex->prepare("DELETE FROM user_vehicles WHERE user_id = ?");
        if (!$deleteStmt) {
            throw new Exception("Error al preparar consulta de eliminación: " . $conex->error);
        }
        $deleteStmt->bind_param('i', $user_id);
        if (!$deleteStmt->execute()) {
            throw new Exception("Error al eliminar vehículos: " . $deleteStmt->error);
        }
        $deleteStmt->close();
        
        // Insertar nuevos vehículos solo si hay alguno seleccionado
        if (!empty($vehicle_ids)) {
            $insertStmt = $conex->prepare("INSERT INTO user_vehicles (user_id, vehicle_type_id) VALUES (?, ?)");
            if (!$insertStmt) {
                throw new Exception("Error al preparar consulta de inserción: " . $conex->error);
            }
            
            foreach ($vehicle_ids as $vehicle_id) {
                $insertStmt->bind_param('ii', $user_id, $vehicle_id);
                if (!$insertStmt->execute()) {
                    throw new Exception("Error al insertar vehículo: " . $insertStmt->error);
                }
            }
            $insertStmt->close();
        }
        
        // Confirmar transacción
        $conex->commit();
        
        // Obtener los nuevos vehículos para la respuesta
        $vehicles_query = "SELECT vt.id, vt.category_name, vt.icon 
                           FROM user_vehicles uv
                           JOIN vehicle_types vt ON uv.vehicle_type_id = vt.id
                           WHERE uv.user_id = ?";
        $stmt = $conex->prepare($vehicles_query);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta de selección: " . $conex->error);
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al obtener vehículos: " . $stmt->error);
        }
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['vehicles'][] = $row;
        }
        
        $response['success'] = true;
        $stmt->close();
        
        echo json_encode($response);
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (isset($conex) && $conex) {
            $conex->rollback();
        }
        
        $response['error'] = $e->getMessage();
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }
}

// Obtener datos del usuario
try {
    $query = "SELECT id, full_name, email, phone_number, profile_picture, 
                 user_type, latitude, longitude
          FROM users 
          WHERE id = ?";
    $stmt = $conex->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error en preparación de consulta: " . $conex->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("No se encontraron datos del usuario");
    }

    // Guardar la imagen de perfil en la sesión para uso en otras páginas
    $_SESSION['user_profile_picture'] = $user['profile_picture'];

    // Obtener vehículos del usuario
    $vehicles_query = "SELECT vt.id, vt.category_name, vt.icon 
                       FROM user_vehicles uv
                       JOIN vehicle_types vt ON uv.vehicle_type_id = vt.id
                       WHERE uv.user_id = ?";
    $stmt = $conex->prepare($vehicles_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $vehicles_result = $stmt->get_result();
    $user_vehicles = [];
    
    while ($row = $vehicles_result->fetch_assoc()) {
        $user_vehicles[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Error en mi-cuenta.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Ocurrió un error al cargar tus datos. Por favor inicia sesión nuevamente.";
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Convertir ubicación a texto legible
$location_text = "No has compartido tu ubicación aún";
if (!empty($user['location_text']) && is_string($user['location_text'])) {
    if (preg_match('/POINT\(([^ ]+) ([^ ]+)\)/', $user['location_text'], $matches)) {
        $longitude = $matches[1] ?? '';
        $latitude = $matches[2] ?? '';
        $location_text = "Lat: $latitude, Long: $longitude";
    }
}

// Manejar actualización de ubicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_location') {
    $response = ['success' => false, 'error' => ''];
    
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        
        $updateStmt = $conex->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE id = ?");
        $updateStmt->bind_param('ddi', $latitude, $longitude, $user_id);
        
        if ($updateStmt->execute()) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Error al actualizar ubicación';
        }
        $updateStmt->close();
    } else {
        $response['error'] = 'Datos de ubicación faltantes';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Obtener especificaciones del usuario
$user_specs = [];
$specs_query = "
    SELECT ust.id, ust.name, ust.icon, ust.has_value, ust.value_label, 
           ust.description, us.value 
    FROM user_specification_types ust
    LEFT JOIN user_specifications us ON ust.id = us.specification_type_id AND us.user_id = ?
    ORDER BY ust.name
";
$stmt = $conex->prepare($specs_query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $specs_result = $stmt->get_result();
    
    while ($row = $specs_result->fetch_assoc()) {
        $user_specs[] = $row;
    }
    $stmt->close();
}

// Manejar actualización de especificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_specifications') {
    $response = ['success' => false, 'error' => ''];
    
    if (isset($_POST['specifications'])) {
        $specifications = json_decode($_POST['specifications'], true);
        
        if (is_array($specifications)) {
            // Iniciar transacción
            $conex->begin_transaction();
            
            try {
                // Eliminar todas las especificaciones actuales del usuario
                $deleteStmt = $conex->prepare("DELETE FROM user_specifications WHERE user_id = ?");
                $deleteStmt->bind_param('i', $user_id);
                $deleteStmt->execute();
                $deleteStmt->close();
                
                // Insertar las nuevas especificaciones
                if (!empty($specifications)) {
                    $insertStmt = $conex->prepare("INSERT INTO user_specifications (user_id, specification_type_id, value) VALUES (?, ?, ?)");
                    
                    foreach ($specifications as $spec) {
                        $spec_id = $spec['id'];
                        $value = isset($spec['value']) ? $spec['value'] : null;
                        
                        $insertStmt->bind_param('iis', $user_id, $spec_id, $value);
                        $insertStmt->execute();
                    }
                    
                    $insertStmt->close();
                }
                
                $conex->commit();
                $response['success'] = true;
            } catch (Exception $e) {
                $conex->rollback();
                $response['error'] = 'Error al actualizar las especificaciones: ' . $e->getMessage();
            }
        } else {
            $response['error'] = 'Formato de especificaciones no válido';
        }
    } else {
        $response['error'] = 'Datos de especificaciones faltantes';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$page_title = "Parking SV - Mi Cuenta";
include 'includes/header.php';
?>

<link rel="stylesheet" href="/crud-php2/assets/css/pages/mi-cuenta.css">

<div class="account-container">
    <!-- Encabezado -->
    <header class="account-header">
        <h1>Información sobre mi cuenta</h1>
    </header>
    
    <!-- Sección Personal -->
    <section class="personal-card">
        <h2 class="card-title">Personal</h2>
        <div class="personal-content">
            <div class="profile-picture-container">
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/crud-php2/assets/images/pfp default.jpeg'; ?>" 
                    alt="Foto de perfil" 
                    class="profile-picture"
                    id="profilePicture">
                <button class="edit-pfp-btn" id="editPfpBtn"><i class="fas fa-camera"></i></button>
            </div>
            
            <div class="personal-info">
                <div class="info-row">
                    <span class="info-label">Nombre completo:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Número de teléfono:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone_number']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Correo electrónico:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Tipo de usuario:</span>
                    <span class="info-value"><?php echo $user['user_type'] === 'owner' ? 'Propietario' : 'Cliente'; ?></span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Sección Transporte y Ubicación -->
    <section class="transport-card">
        <h2 class="card-title2">Transporte y ubicación</h2>
        <div class="transport-content">
            <div class="vehicles-section">
                <div class="vehicles-header">
                    <h3>Actualmente conduces:</h3>
                    <button class="btn-edit-vehicles" id="editVehiclesBtn">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
                
                <div class="vehicles-list">
                    <?php if (empty($user_vehicles)): ?>
                        <div class="no-vehicles">
                            <i class="fas fa-info-circle"></i>
                            No has seleccionado ningún vehículo
                        </div>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($user_vehicles as $vehicle): ?>
                            <li class="vehicle-item">
                                <i class="fas fa-<?php echo $vehicle['icon']; ?>"></i>
                                <?php echo htmlspecialchars($vehicle['category_name']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
<div class="location-section">
    <h3>Tu ubicación:</h3>
    <p class="location-text">
        <?php 
        if (!empty($user['latitude']) && !empty($user['longitude'])) {
            echo "Lat: " . htmlspecialchars($user['latitude']) . ", Long: " . htmlspecialchars($user['longitude']);
        } else {
            echo "No has compartido tu ubicación aún";
        }
        ?>
    </p>
    <button class="btn-update-location" id="updateLocationBtn">
        <i class="fas fa-map-marker-alt"></i> Actualizar ubicación
    </button>
</div>
        </div>
    </section>

    <!-- Sección de Especificaciones -->
<section class="specs-card">
    <h2 class="card-title2">Mis especificaciones</h2>
    <div class="specs-content">
        <div class="specs-header">
            <h3>Características y preferencias:</h3>
            <button class="btn-edit-specs" id="editSpecsBtn">
                <i class="fas fa-edit"></i> Editar
            </button>
        </div>
        
        <div class="specs-list">
            <?php if (empty($user_specs)): ?>
                <div class="no-specs">
                    <i class="fas fa-info-circle"></i>
                    No has configurado tus especificaciones aún
                </div>
            <?php else: ?>
                <ul>
                    <?php foreach ($user_specs as $spec): 
                        // Mostrar siempre las especificaciones que estén activadas
                        if (!empty($spec['value']) || $spec['value'] === '0'): ?>
                            <li class="spec-item">
                                <i class="fas fa-<?php echo $spec['icon']; ?>"></i>
                                <span class="spec-name"><?php echo htmlspecialchars($spec['name']); ?>:</span>
                                <span class="spec-value">
                                    <?php 
                                    if ($spec['has_value'] && !empty($spec['value'])) {
                                        echo htmlspecialchars($spec['value'] . ' ' . $spec['value_label']);
                                    } else {
                                        echo htmlspecialchars($spec['name']);
                                    }
                                    ?>
                                </span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal para editar especificaciones -->
<div id="specsModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Configura tus especificaciones</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="specs-options">
                <?php foreach ($user_specs as $spec): ?>
                <div class="spec-option-card" data-spec-id="<?php echo $spec['id']; ?>" 
                     data-has-value="<?php echo $spec['has_value'] ? 'true' : 'false'; ?>">
                    <div class="spec-header">
                        <div class="spec-icon">
                            <i class="fas fa-<?php echo $spec['icon']; ?>"></i>
                        </div>
                        <div class="spec-info">
                            <h4><?php echo htmlspecialchars($spec['name']); ?></h4>
                            <p><?php echo htmlspecialchars($spec['description']); ?></p>
                        </div>
                        <label class="spec-toggle">
                            <input type="checkbox" class="spec-checkbox" 
                                <?php echo !empty($spec['value']) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php if ($spec['has_value']): ?>
                    <div class="spec-value-input" 
                         style="<?php echo empty($spec['value']) ? 'display: none;' : ''; ?>">
                        <input type="text" class="spec-input" 
                               placeholder="<?php echo htmlspecialchars($spec['value_label']); ?>"
                               value="<?php echo !empty($spec['value']) ? htmlspecialchars($spec['value']) : ''; ?>">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel">Cancelar</button>
            <button type="button" class="btn-save-specs" id="saveSpecsBtn">Guardar</button>
        </div>
    </div>
</div>
    
    <!-- Sección para dueños de parqueos -->
    <?php if ($user['user_type'] === 'owner'): ?>
    <section class="owner-actions">
        <a href="/crud-php2/mis-parqueos.php" class="owner-btn">
            <i class="fas fa-parking"></i> Mis parqueos
        </a>
        <a href="/crud-php2/publicar-parqueo.php" class="owner-btn">
            <i class="fas fa-plus"></i> Publicar un parqueo
        </a>
    </section>
    <?php endif; ?>
    
    <!-- Botón de cerrar sesión -->
    <button data-logout class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
    </button>
</div>

<!-- Modal para editar vehículos -->
<!-- Modal para editar vehículos - VERSIÓN MEJORADA -->
<div id="vehiclesModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Selecciona los vehículos que manejas</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="vehicle-options-grid" style="grid-template-columns: repeat(3, 1fr);">
                <?php
                $all_vehicles_query = "SELECT id, category_name, icon, description FROM vehicle_types";
                $all_vehicles_result = $conex->query($all_vehicles_query);
                $user_vehicle_ids = array_column($user_vehicles, 'id');
                
                while ($vehicle = $all_vehicles_result->fetch_assoc()):
                ?>
                <div class="vehicle-option-card">
                    <input type="checkbox" 
                        id="vehicle-<?php echo $vehicle['id']; ?>" 
                        value="<?php echo $vehicle['id']; ?>"
                        <?php echo in_array($vehicle['id'], $user_vehicle_ids) ? 'checked' : ''; ?>
                        class="vehicle-checkbox">
                    <label for="vehicle-<?php echo $vehicle['id']; ?>" class="vehicle-label">
                        <div class="vehicle-icon">
                            <i class="fas fa-<?php echo $vehicle['icon']; ?>"></i>
                        </div>
                        <div class="vehicle-info">
                            <span class="vehicle-name"><?php echo htmlspecialchars($vehicle['category_name']); ?></span>
                            <p class="vehicle-description"><?php echo htmlspecialchars($vehicle['description']); ?></p>
                        </div>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel">Cancelar</button>
            <button type="button" class="btn-save-vehicles" id="saveVehiclesBtn">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal para subir foto de perfil -->
<div id="pfpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cambiar foto de perfil</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="pfp-options" id="pfpOptions">
                <div class="pfp-option" id="uploadOption">
                    <i class="fas fa-upload"></i>
                    <span>Subir una foto</span>
                </div>
                <div class="pfp-option" id="cameraOption">
                    <i class="fas fa-camera"></i>
                    <span>Tomar una foto</span>
                </div>
            </div>
            
            <div id="cameraPreview" style="display:none;">
                <video id="cameraFeed" autoplay playsinline style="width:100%;"></video>
                <button id="captureBtn" class="btn-capture">Capturar</button>
            </div>
            
            <div id="imagePreview" style="display:none;">
                <img id="previewImg" src="" alt="Vista previa" style="max-width:100%;">
                <div class="preview-actions">
                    <button id="retakeBtn" class="btn-retake">Volver a tomar</button>
                    <button id="savePfpBtn" class="btn-confirm">Guardar</button>
                </div>
            </div>
            
            <form id="pfpForm" method="post" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="update_profile_picture">
                <input type="file" name="profile_picture" id="pfpFileInput" accept="image/*">
                <input type="hidden" name="image_data" id="imageDataInput">
            </form>
        </div>
    </div>
</div>

<!-- Mejorar el modal de logout -->
<div id="logoutModal" class="modal">
    <div class="modal-content logout-modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-sign-out-alt"></i> Cerrar sesión</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro que deseas cerrar tu sesión?</p>
            <div class="logout-icons">
                <i class="fas fa-door-open"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel">Cancelar</button>
            <button type="button" class="btn-logout" id="confirmLogout">Sí, cerrar sesión</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Datos para JavaScript
const userVehicles = <?php echo json_encode($user_vehicles); ?>;
const baseUrl = '<?php echo $base_url; ?>';
</script>
<script src="<?php echo $base_url; ?>assets/js/pages/mi-cuenta.js"></script>