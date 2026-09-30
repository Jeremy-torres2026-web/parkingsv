<?php
session_start();
include 'conexion.php';
// Inicializar variables
$errors = [];
$success = false;
$formData = [];

// Obtener datos para selects y opciones
$categorias = $conex->query("SELECT * FROM parking_categories");
$servicios = $conex->query("SELECT * FROM services");
$restricciones = $conex->query("SELECT * FROM restriction_types");
$vehiculos = $conex->query("SELECT * FROM vehicle_types");
$tiposTarifas = ['normal', 'premium', 'nocturno', 'mensual', 'comercial', 'evento'];
$unidadesTiempo = ['minuto', 'hora', 'dia', 'semana', 'mes'];
$diasSemana = ['Días laborales', 'Fines de semana', 'Toda la semana'];

// Departamentos y municipios
$departamentos = [
    "San Salvador Norte" => [
        "Aguilares", "Apopa", "Ayutuxtepeque", "Cuscatancingo", 
        "Delgado", "El Paisnal", "Guazapa", "Ilopango", 
        "Mejicanos", "Nejapa", "Panchimalco", "Rosario de Mora", 
        "San Marcos", "San Martín", "Santiago Texacuangos", 
        "Santo Tomás", "Soyapango", "Tonacatepeque"
    ],
    "San Salvador Oeste" => [
        "Antiguo Cuscatlán", "Huizúcar", "San Juan Opico", "Nuevo Cuscatlán",
        "Quezaltepeque", "Santa Tecla", "Talnique"
    ],
    "San Salvador Este" => [
        "Ciudad Arce", "Colón", "Comasagua", "Jayaque", "Nahuizalco",
        "Sacacoyo", "San José Villanueva", "San Pablo Tacachico",
        "Tamanique", "Teotepeque", "Tepecoyo", "Zaragoza"
    ],
    "San Salvador Sur" => [
        "Chiltiupán", "Jicalapa", "La Libertad", "Tamanique",
        "Teotepeque", "Santa Cruz Michapa", "San Juan Opico",
        "Quezaltepeque", "Sacacoyo", "San Pablo Tacachico"
    ],
    "San Salvador Centro" => ["San Salvador Centro"]
];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $formData = $_POST;

    // Validación básica
    if (empty($_POST['nombre'])) {
        $errors[] = "El nombre del parqueo es requerido";
    }

    // Validar imágenes
    if (empty($_FILES['imagenes']['name'][0])) {
        $errors[] = "Debe subir al menos una imagen del parqueo";
    } else {
        // Validar cada imagen
        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
            $file_type = $_FILES['imagenes']['type'][$key];
            $valid_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($file_type, $valid_types)) {
                $errors[] = "El archivo " . $_FILES['imagenes']['name'][$key] . " no es una imagen válida";
            }

            if ($_FILES['imagenes']['size'][$key] > 5 * 1024 * 1024) {
                $errors[] = "La imagen " . $_FILES['imagenes']['name'][$key] . " es demasiado grande (máximo 5MB)";
            }
        }
    }

    if (empty($errors)) {
        try {
            $conex->begin_transaction();

            // 1. Guardar ubicación
            $departamento = $_POST['departamento'];
            $municipio = $_POST['municipio'];
            $direccion = $_POST['direccion'] ?? '';
            $referencia = $_POST['referencia'] ?? '';
            $waze_link = $_POST['waze_link'] ?? '';
            $google_maps_link = $_POST['google_maps_link'] ?? '';

            $stmt = $conex->prepare("INSERT INTO locations (department, municipality, street_address, reference_address, waze_link, google_maps_link) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $departamento, $municipio, $direccion, $referencia, $waze_link, $google_maps_link);
            $stmt->execute();
            $location_id = $conex->insert_id;
            $stmt->close();

            // 2. Guardar parqueo
            $is_24_7 = isset($_POST['is_24_7']) ? 1 : 0;
            $owner_id = $_SESSION['user_id'];
            $category_id = $_POST['categoria_id'];
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'] ?? '';
            $contact_phone = $_POST['contacto_telefono'];
            $contact_email = $_POST['contacto_email'] ?? '';
            $contact_name = $_POST['contacto_nombre'] ?? '';

            // Convertir horario a JSON
            $schedule = [];
            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            foreach ($dias as $dia) {
                $schedule[$dia] = [];
                if (isset($_POST["apertura_$dia"]) && is_array($_POST["apertura_$dia"])) {
                    foreach ($_POST["apertura_$dia"] as $index => $apertura) {
                        $cierre = $_POST["cierre_$dia"][$index] ?? '';
                        if (!empty($apertura) && !empty($cierre)) {
                            $schedule[$dia][] = [
                                'apertura' => $apertura,
                                'cierre' => $cierre
                            ];
                        }
                    }
                }
            }
            $schedule_json = json_encode($schedule);

            $stmt = $conex->prepare("INSERT INTO parkings (owner_id, location_id, category_id, name, description, 
                                     contact_phone, contact_email, schedule, contact_name, is_24_7) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissssssi", $owner_id, $location_id, $category_id, $nombre, 
                              $descripcion, $contact_phone, $contact_email, $schedule_json, $contact_name, $is_24_7);
            $stmt->execute();
            $parking_id = $conex->insert_id;
            $stmt->close();

            // 3. Guardar capacidad
            $stmt = $conex->prepare("INSERT INTO parking_capacities (parking_id, general_capacity, disability_spaces) 
                                     VALUES (?, ?, ?)");
            $capacidad_general = $_POST['capacidad_general'];
            $espacios_discapacitados = $_POST['espacios_discapacitados'] ?? 0;
            $stmt->bind_param("iii", $parking_id, $capacidad_general, $espacios_discapacitados);
            $stmt->execute();
            $stmt->close();

            // Capacidad por tipo de vehículo
            if (!empty($_POST['capacidad_vehiculo']) && is_array($_POST['capacidad_vehiculo'])) {
                foreach ($_POST['capacidad_vehiculo'] as $vehicle_type_id => $capacidad) {
                    if (is_numeric($vehicle_type_id) && $capacidad > 0) {
                        $stmt = $conex->prepare("INSERT INTO parking_vehicle_capacities (parking_id, vehicle_type_id, capacity) 
                                                 VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $parking_id, $vehicle_type_id, $capacidad);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            // 4. Guardar tarifas
            if (!empty($_POST['tarifa_vehicle_type'])) {
                foreach ($_POST['tarifa_vehicle_type'] as $index => $vehicle_type_id) {
                    if (!is_numeric($vehicle_type_id) || $vehicle_type_id < 1) continue;
                    $fee_type = $_POST['tarifa_tipo'][$index] ?? 'normal';
                    $price = $_POST['tarifa_precio'][$index] ?? 'Gratis';
                    $time_unit = $_POST['tarifa_unidad'][$index] ?? 'hora';
                    $applies_to = $_POST['tarifa_dias'][$index] ?? 'Toda la semana';
                    $valid_from = !empty($_POST['tarifa_validez_inicio'][$index]) ? $_POST['tarifa_validez_inicio'][$index] : null;
                    $valid_to = !empty($_POST['tarifa_validez_fin'][$index]) ? $_POST['tarifa_validez_fin'][$index] : null;
                    
                    $stmt = $conex->prepare("INSERT INTO parking_fees (parking_id, vehicle_type_id, fee_type, price, 
                                            time_unit, applies_to, valid_from, valid_to) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissssss", $parking_id, $vehicle_type_id, $fee_type, $price, 
                                     $time_unit, $applies_to, $valid_from, $valid_to);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 5. Guardar servicios
            if (!empty($_POST['servicios'])) {
                $servicios_ids = explode(',', $_POST['servicios']);
                foreach ($servicios_ids as $service_id) {
                    if (!is_numeric($service_id) || $service_id < 1) continue;
                    $stmt = $conex->prepare("INSERT INTO parking_services (parking_id, service_id) 
                                             VALUES (?, ?)");
                    $stmt->bind_param("ii", $parking_id, $service_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 6. Guardar restricciones
            if (!empty($_POST['restricciones'])) {
                $restricciones_ids = explode(',', $_POST['restricciones']);
                foreach ($restricciones_ids as $restriction_type_id) {
                    if (!is_numeric($restriction_type_id) || $restriction_type_id < 1) continue;
                    $stmt = $conex->prepare("INSERT INTO parking_restriction_items (parking_id, restriction_type_id) 
                                             VALUES (?, ?)");
                    $stmt->bind_param("ii", $parking_id, $restriction_type_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 7. Guardar restricciones físicas
            $max_height = !empty($_POST['altura_maxima']) ? $_POST['altura_maxima'] : null;
            $max_speed = !empty($_POST['velocidad_maxima']) ? $_POST['velocidad_maxima'] : null;

            $stmt = $conex->prepare("INSERT INTO parking_restrictions (parking_id, max_height, max_speed) 
                                     VALUES (?, ?, ?)");
            $stmt->bind_param("idd", $parking_id, $max_height, $max_speed);
            $stmt->execute();
            $stmt->close();

            // 8. Guardar imágenes
            if (!empty($_FILES['imagenes'])) {
                $upload_dir = 'public/uploads/parkings/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $primary_image_index = $_POST['imagen_principal'] ?? 0;
                $uploaded_images = 0;

                foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_ext = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);
                        $file_name = 'parking_' . $parking_id . '_' . uniqid() . '.' . $file_ext;
                        $target_path = $upload_dir . $file_name;

                        if (move_uploaded_file($tmp_name, $target_path)) {
                            $is_primary = ($key == $primary_image_index) ? 1 : 0;
                            $sort_order = $uploaded_images + 1;

                            $stmt = $conex->prepare("INSERT INTO parking_images (parking_id, image_url, is_primary, sort_order) 
                                                     VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isii", $parking_id, $target_path, $is_primary, $sort_order);
                            $stmt->execute();
                            $stmt->close();

                            $uploaded_images++;
                        }
                    }
                }

                if ($uploaded_images === 0) {
                    throw new Exception("No se pudo subir ninguna imagen");
                }
            }

            $conex->commit();
            $_SESSION['success_message'] = "¡Parqueo publicado exitosamente!";
            header("Location: publicar-parqueo.php");
            exit();

        } catch (Exception $e) {
            $conex->rollback();
            $errors[] = "Error al guardar: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

include 'includes/header.php';
$page_title = "Parking SV - Publica tu parqueo";
?>

<!-- CSS específico -->
<link rel="stylesheet" href="/crud-php2/assets/css/pages/publicar-parqueo.css">

<main class="publicar-parqueo-container">
    <h1 class="center">¡Publicá Tu Parqueo!</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <p><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <form action="publicar-parqueo.php" method="POST" enctype="multipart/form-data" id="parkingForm">
        <!-- SECCIÓN 1: Información Básica -->
        <div class="form-section">
            <div class="semi-square yellow">
                <h3 class="center">Información Básica</h3>
                <div class="input-with-icon">
                    <i class="fas fa-parking"></i>
                    <input type="text" name="nombre" required placeholder="Nombre del parqueo" value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>">
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="button" class="btn-action blue" id="categoryBtn">
                    <i class="fas fa-tag"></i> Categoría
                </button>
                <button type="button" class="btn-action blue" id="locationBtn">
                    <i class="fas fa-map-marker-alt"></i> Ubicación
                </button>
                <button type="button" class="btn-action blue" id="scheduleBtn">
                    <i class="fas fa-clock"></i> Horario
                </button>
            </div>
        </div>

        <!-- Modal para Categoría -->
        <div id="categoryModal" class="modal">
            <div class="modal-content better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-tag"></i> Seleccione una categoría</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="categories-list">
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <div class="category-item" data-id="<?= $cat['id'] ?>">
                                <div class="category-icon">
                                    <?php 
                                    $icon = '';
                                    switch($cat['name']) {
                                        case 'normal': $icon = 'fas fa-car'; break;
                                        case 'alta_demanda': $icon = 'fas fa-car-alt'; break;
                                        case 'turistico': $icon = 'fas fa-map-marker-alt'; break;
                                        case 'mixto': $icon = 'fas fa-th-large'; break;
                                        case 'premium': $icon = 'fas fa-crown'; break;
                                        default: $icon = 'fas fa-car';
                                    }
                                    ?>
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h4><?= htmlspecialchars($cat['name']) ?></h4>
                                    <p><?= htmlspecialchars($cat['description']) ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="categoria_id" id="selectedCategory" value="<?= $formData['categoria_id'] ?? '' ?>">
                    <button type="button" class="btn-save">Confirmar</button>
                </div>
            </div>
        </div>

        <!-- Modal para Ubicación -->
        <div id="locationModal" class="modal">
            <div class="modal-content better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Ubicación</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="location-group">
                        <label><i class="fas fa-map"></i> Departamento:</label>
                        <div class="select-container">
                            <select name="departamento" id="departamentoSelect" required>
                                <option value="">Seleccione un departamento</option>
                                <?php foreach ($departamentos as $depto => $muns): ?>
                                    <option value="<?= htmlspecialchars($depto) ?>" 
                                        <?= isset($formData['departamento']) && $formData['departamento'] == $depto ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($depto) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="location-group">
                        <label><i class="fas fa-city"></i> Municipio:</label>
                        <div class="select-container">
                            <select name="municipio" id="municipioSelect" required>
                                <option value="">Primero seleccione un departamento</option>
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="location-group">
                        <label><i class="fas fa-road"></i> Dirección exacta:</label>
                        <div class="input-with-icon">
                            <i class="fas fa-map-pin"></i>
                            <input type="text" name="direccion" required placeholder="Ej: Calle Principal #123" value="<?= htmlspecialchars($formData['direccion'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="location-group">
                        <label><i class="fas fa-info-circle"></i> Referencia (opcional):</label>
                        <div class="input-with-icon">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" name="referencia" placeholder="Ej: Frente a Centro Comercial" value="<?= htmlspecialchars($formData['referencia'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="location-group">
                        <label><i class="fab fa-waze"></i> Enlace de Waze (opcional):</label>
                        <div class="input-with-icon">
                            <i class="fas fa-link"></i>
                            <input type="url" name="waze_link" placeholder="https://www.waze.com/..." value="<?= htmlspecialchars($formData['waze_link'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="location-group">
                        <label><i class="fab fa-google"></i> Enlace de Google Maps (opcional):</label>
                        <div class="input-with-icon">
                            <i class="fas fa-link"></i>
                            <input type="url" name="google_maps_link" placeholder="https://www.google.com/maps/..." value="<?= htmlspecialchars($formData['google_maps_link'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save">Guardar</button>
                </div>
            </div>
        </div>
        <script>
            const departamentosData = <?= json_encode($departamentos) ?>;
            const municipioPreSeleccionado = "<?= $formData['municipio'] ?? '' ?>";
        </script>

        <!-- Modal para Horario (con opción 24/7) -->
        <div id="scheduleModal" class="modal">
            <div class="modal-content better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-clock"></i> Horario</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="is-24-7">
                        <label>
                            <input type="checkbox" name="is_24_7" id="is_24_7" value="1" <?= isset($formData['is_24_7']) && $formData['is_24_7'] ? 'checked' : '' ?>>
                            <span><i class="fas fa-clock"></i> Abierto 24/7</span>
                        </label>
                    </div>
                    
                    <div id="schedule-container">
                        <?php
                        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        foreach ($dias as $dia):
                            $diaKey = strtolower($dia);
                        ?>
                        <div class="day-schedule">
                            <label class="day-label">
                                <input type="checkbox" name="dias_abierto[]" value="<?= $diaKey ?>" <?= isset($formData['dias_abierto']) && in_array($diaKey, $formData['dias_abierto']) ? 'checked' : '' ?>>
                                <span><?= $dia ?></span>
                            </label>
                            <div class="time-slots">
                                <?php
                                $slotCount = isset($formData["apertura_$diaKey"]) ? count($formData["apertura_$diaKey"]) : 1;
                                for ($i = 0; $i < $slotCount; $i++):
                                ?>
                                <div class="time-slot">
                                    <div class="time-input-group">
                                        <i class="fas fa-door-open"></i>
                                        <input type="time" name="apertura_<?= $diaKey ?>[]" value="<?= $formData["apertura_$diaKey"][$i] ?? '' ?>">
                                    </div>
                                    <div class="time-input-group">
                                        <i class="fas fa-door-closed"></i>
                                        <input type="time" name="cierre_<?= $diaKey ?>[]" value="<?= $formData["cierre_$diaKey"][$i] ?? '' ?>">
                                    </div>
                                    <button type="button" class="add-slot-btn" onclick="addTimeSlot('<?= $diaKey ?>')"><i class="fas fa-plus"></i></button>
                                    <?php if ($i > 0): ?>
                                        <button type="button" class="remove-slot-btn" onclick="removeTimeSlot(this)"><i class="fas fa-minus"></i></button>
                                    <?php endif; ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save">Guardar</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Imágenes -->
        <div class="form-section white-bg">
            <h3 class="center"><i class="fas fa-images"></i> Imágenes del Parqueo</h3>
            <div class="image-upload-container">
                <div class="upload-actions">
                    <div class="upload-btn-container">
                        <input type="file" name="imagenes[]" id="parking-images" multiple accept="image/*" style="display: none;">
                        <label for="parking-images" class="upload-btn">
                            <i class="fas fa-folder-open"></i> Elegir archivos
                        </label>
                    </div>
                    <div class="file-info">
                        <span id="file-count">Ningún archivo seleccionado</span>
                    </div>
                    <button type="button" class="remove-all-btn" id="remove-all-images">
                        <i class="fas fa-trash-alt"></i> Eliminar todas
                    </button>
                </div>
                
                <!-- Previsualización de imágenes -->
                <div id="image-preview" class="image-preview-grid"></div>
                
                <!-- Mensaje informativo -->
                <div class="image-instructions">
                    <p><i class="fas fa-info-circle"></i> Puedes subir hasta 8 imágenes</p>
                    <p><i class="fas fa-star"></i> Haz clic en la estrella para seleccionar la imagen de portada</p>
                    <p><i class="fas fa-times"></i> Haz clic en la X para eliminar una imagen</p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: Capacidad -->
        <div class="form-section">
            <h3 class="center"><i class="fas fa-car"></i> Capacidad</h3>
            <button type="button" class="btn-action blue" id="capacityBtn">
                <i class="fas fa-edit"></i> Configurar Capacidad
            </button>
            
            <div id="capacity-summary" class="summary-container">
                <?php if (!empty($formData['capacidad_general'])): ?>
                    <div class="summary-item">
                        <i class="fas fa-users"></i>
                        <span>Capacidad General: <?= $formData['capacidad_general'] ?></span>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-wheelchair"></i>
                        <span>Espacios Discapacitados: <?= $formData['espacios_discapacitados'] ?? 0 ?></span>
                    </div>
                <?php else: ?>
                    <p class="no-info">No se ha configurado la capacidad</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal para Capacidad -->
        <div id="capacityModal" class="modal">
            <div class="modal-content better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-car"></i> Configurar Capacidad</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="capacity-group">
                        <label><i class="fas fa-users"></i> Capacidad general:</label>
                        <input type="number" name="capacidad_general" min="1" required value="<?= $formData['capacidad_general'] ?? '' ?>">
                    </div>
                    <div class="capacity-group">
                        <label><i class="fas fa-wheelchair"></i> Espacios para discapacitados:</label>
                        <input type="number" name="espacios_discapacitados" min="0" value="<?= $formData['espacios_discapacitados'] ?? 0 ?>">
                    </div>
                    <h4><i class="fas fa-car-side"></i> Capacidad por tipo de vehículo</h4>
                    <div class="vehicle-capacity-grid">
                        <?php 
                        $vehiculos->data_seek(0);
                        while ($vh = $vehiculos->fetch_assoc()): 
                        ?>
                            <div class="capacity-group">
                                <label>
                                    <i class="fas fa-<?= $vh['icon'] ?>"></i> <?= htmlspecialchars($vh['category_name']) ?>:
                                </label>
                                <input type="number" name="capacidad_vehiculo[<?= $vh['id'] ?>]" min="0" 
                                    value="<?= $formData['capacidad_vehiculo'][$vh['id']] ?? 0 ?>">
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save">Guardar</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 9: Contacto del Parqueo -->
        <div class="form-section contacto-bg">
            <h3 class="center"><i class="fas fa-phone"></i> Contacto del Parqueo</h3>
            <div class="contacto-container">
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="contacto_nombre" placeholder="Nombre del contacto" value="<?= htmlspecialchars($formData['contacto_nombre'] ?? '') ?>" required>
                </div>
                <div class="input-with-icon">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="contacto_telefono" placeholder="Teléfono" value="<?= htmlspecialchars($formData['contacto_telefono'] ?? '') ?>" required>
                </div>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="contacto_email" placeholder="Correo electrónico" value="<?= htmlspecialchars($formData['contacto_email'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 4: Tarifas -->
        <div class="form-section gray-bg">
            <div class="section-header">
                <h3 class="center"><i class="fas fa-dollar-sign"></i> Tarifas</h3>
                <button type="button" class="btn-add" id="addRateBtn">
                    <i class="fas fa-plus-circle"></i> Agregar Tarifa
                </button>
            </div>
            
            <div id="rates-container" class="rates-grid">
                <?php if (!empty($formData['tarifa_vehicle_type'])): ?>
                    <?php foreach ($formData['tarifa_vehicle_type'] as $index => $vehicle_type_id): ?>
                        <?php if (!empty($formData['tarifa_precio'][$index])): ?>
                            <div class="rate-item">
                                <div class="rate-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="rate-details">
                                    <h4><?= $vehiculos->fetch_assoc()['category_name'] ?></h4>
                                    <p>$<?= $formData['tarifa_precio'][$index] ?> por <?= $formData['tarifa_unidad'][$index] ?></p>
                                    <small>Tipo: <?= $formData['tarifa_tipo'][$index] ?> | Aplica: <?= $formData['tarifa_dias'][$index] ?></small>
                                </div>
                                <button type="button" class="rate-remove" data-index="<?= $index ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-rates">No hay tarifas agregadas</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal para Tarifas Mejorado -->
        <div id="rateModal" class="modal">
            <div class="modal-content better-modal tarifas-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-dollar-sign"></i> Agregar Tarifa</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="rate-form">
                        <div class="form-group">
                            <label><i class="fas fa-car"></i> Tipo de Vehículo:</label>
                            <div class="rate-vehicle-types selectable-options">
                                <?php 
                                $vehiculos->data_seek(0);
                                while ($vh = $vehiculos->fetch_assoc()): 
                                ?>
                                    <div class="selectable-option vehicle-type-option" data-value="<?= $vh['id'] ?>">
                                        <i class="fas fa-<?= $vh['icon'] ?>"></i>
                                        <span><?= htmlspecialchars($vh['category_name']) ?></span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <input type="hidden" name="tarifa_vehicle_type[]" id="selectedRateVehicleType">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Tipo de Tarifa:</label>
                            <div class="rate-types selectable-options">
                                <?php foreach ($tiposTarifas as $tipo): ?>
                                    <div class="selectable-option tarifa-type-option" data-value="<?= $tipo ?>">
                                        <?php 
                                        $icon = '';
                                        switch($tipo) {
                                            case 'normal': $icon = 'fas fa-car'; break;
                                            case 'premium': $icon = 'fas fa-crown'; break;
                                            case 'nocturno': $icon = 'fas fa-moon'; break;
                                            case 'mensual': $icon = 'fas fa-calendar-alt'; break;
                                            case 'comercial': $icon = 'fas fa-briefcase'; break;
                                            case 'evento': $icon = 'fas fa-star'; break;
                                            default: $icon = 'fas fa-dollar-sign';
                                        }
                                        ?>
                                        <i class="<?= $icon ?>"></i>
                                        <span><?= ucfirst($tipo) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="tarifa_tipo[]" id="selectedTarifaType">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> ¿Es gratuita?</label>
                            <div class="rate-free-toggle">
                                <label class="toggle-rate-free">
                                    <input type="radio" name="tarifa_es_gratis" value="1" id="tarifaFreeOption">
                                    <span><i class="fas fa-gift"></i> Gratis</span>
                                </label>
                                <label class="toggle-rate-free">
                                    <input type="radio" name="tarifa_es_gratis" value="0" id="tarifaPriceOption" checked>
                                    <span><i class="fas fa-dollar-sign"></i> Precio</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group rate-price-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio:</label>
                            <div class="input-with-icon">
                                <i class="fas fa-money-bill-wave"></i>
                                <input type="number" step="0.01" min="0" name="tarifa_precio[]" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Unidad de Tiempo:</label>
                            <div class="time-units-grid selectable-options">
                                <?php foreach ($unidadesTiempo as $unidad): ?>
                                    <div class="selectable-option time-unit-option" data-value="<?= $unidad ?>">
                                        <?php 
                                        $icon = '';
                                        switch($unidad) {
                                            case 'minuto': $icon = 'fas fa-stopwatch'; break;
                                            case 'hora': $icon = 'fas fa-clock'; break;
                                            case 'dia': $icon = 'fas fa-calendar-day'; break;
                                            case 'semana': $icon = 'fas fa-calendar-week'; break;
                                            case 'mes': $icon = 'fas fa-calendar-alt'; break;
                                            default: $icon = 'fas fa-clock';
                                        }
                                        ?>
                                        <i class="<?= $icon ?>"></i>
                                        <span><?= ucfirst($unidad) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="tarifa_unidad[]" id="selectedTimeUnit">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Aplica a:</label>
                            <div class="days-grid selectable-options">
                                <?php foreach ($diasSemana as $dias): ?>
                                    <div class="selectable-option day-option" data-value="<?= $dias ?>">
                                        <?php 
                                        $icon = '';
                                        switch($dias) {
                                            case 'Días laborales': $icon = 'fas fa-briefcase'; break;
                                            case 'Fines de semana': $icon = 'fas fa-umbrella-beach'; break;
                                            case 'Toda la semana': $icon = 'fas fa-calendar'; break;
                                            default: $icon = 'fas fa-calendar';
                                        }
                                        ?>
                                        <i class="<?= $icon ?>"></i>
                                        <span><?= $dias === 'Días laborales' ? 'Días laborales' : ($dias === 'Fines de semana' ? 'Fines de semana' : 'Toda la semana') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="tarifa_dias[]" id="selectedDays">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Validez desde (opcional):</label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-day"></i>
                                <input type="date" name="tarifa_validez_inicio[]">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-times"></i> Validez hasta (opcional):</label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-times"></i>
                                <input type="date" name="tarifa_validez_fin[]">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save" id="saveRateBtn">Guardar Tarifa</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 5: Servicios -->
        <div class="form-section green-bg" id="servicesSection">
            <div class="section-header">
                <h3 class="center"><i class="fas fa-concierge-bell"></i> Servicios</h3>
                <button type="button" class="btn-edit" id="editServicesBtn">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
<div id="services-summary" class="summary-container">
    <?php if (!empty($formData['servicios'])): ?>
        <?php 
        $serviciosSeleccionados = explode(',', $formData['servicios']);
        $servicios->data_seek(0);
        ?>
        <?php while ($srv = $servicios->fetch_assoc()): ?>
            <?php if (in_array($srv['id'], $serviciosSeleccionados)): ?>
                <div class="summary-item">
                    <i class="fas fa-check-circle"></i> <!-- Ícono de check -->
                    <span><?= htmlspecialchars($srv['name']) ?></span>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-info">No se han seleccionado servicios</p>
    <?php endif; ?>
</div>
            
            <input type="hidden" name="servicios" id="selectedServices" value="<?= $formData['servicios'] ?? '' ?>">
        </div>

        <!-- Modal para Servicios -->
        <div id="servicesModal" class="modal">
            <div class="modal-content large better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-concierge-bell"></i> Seleccionar Servicios</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="services-grid" id="servicesGrid">
                        <?php $servicios->data_seek(0); while ($srv = $servicios->fetch_assoc()): ?>
                            <div class="service-item" data-id="<?= $srv['id'] ?>">
                                <div class="service-icon">
                                    <?php
                                    $icon = 'fas fa-concierge-bell';
                                    switch($srv['id']) {
                                        case 1: $icon = 'fas fa-credit-card'; break;
                                        case 2: $icon = 'fas fa-id-card'; break;
                                        case 3: $icon = 'fas fa-video'; break;
                                        case 4: $icon = 'fas fa-car-side'; break;
                                        case 5: $icon = 'fas fa-utensils'; break;
                                        case 6: $icon = 'fas fa-wheelchair'; break;
                                        case 7: $icon = 'fas fa-gas-pump'; break;
                                        case 8: $icon = 'fas fa-paw'; break;
                                        case 9: $icon = 'fas fa-restroom'; break;
                                        case 10: $icon = 'fas fa-umbrella-beach'; break;
                                        case 11: $icon = 'fas fa-car'; break;
                                        case 12: $icon = 'fas fa-user-shield'; break;
                                        case 13: $icon = 'fas fa-wifi'; break;
                                    }
                                    ?>
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <span><?= htmlspecialchars($srv['name']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save" id="saveServicesBtn">Guardar Servicios</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 6: Descripción -->
        <div class="form-section yellow-bg" id="descriptionSection">
            <h3 class="center"><i class="fas fa-align-left"></i> Descripción del Parqueo</h3>
            <div class="info-container">
                <textarea name="descripcion" placeholder="Describe tu parqueo (ubicación exacta, características especiales, beneficios, etc.)"><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- SECCIÓN 7: Restricciones -->
        <div class="form-section red-bg" id="restrictionsSection">
            <div class="section-header">
                <h3 class="center"><i class="fas fa-ban"></i> Restricciones</h3>
                <button type="button" class="btn-edit" id="editRestrictionsBtn">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            <div id="restrictions-summary" class="summary-container">
                <?php if (!empty($formData['restricciones'])): ?>
                    <?php 
                    $restriccionesSeleccionadas = explode(',', $formData['restricciones']);
                    $restricciones->data_seek(0);
                    ?>
                    <?php while ($res = $restricciones->fetch_assoc()): ?>
                        <?php if (in_array($res['id'], $restriccionesSeleccionadas)): ?>
                            <div class="summary-item">
                                <i class="fas fa-ban"></i>
                                <span><?= htmlspecialchars($res['name']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-info">No se han configurado restricciones</p>
                <?php endif; ?>
            </div>
            <input type="hidden" name="restricciones" id="selectedRestrictions" value="<?= $formData['restricciones'] ?? '' ?>">
        </div>

        <!-- Modal para Restricciones -->
        <div id="restrictionsModal" class="modal">
            <div class="modal-content large better-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-ban"></i> Restricciones</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="restrictions-grid" id="restrictionsGrid">
                        <?php $restricciones->data_seek(0); while ($res = $restricciones->fetch_assoc()): ?>
                            <div class="restriction-item" data-id="<?= $res['id'] ?>">
                                <div class="restriction-icon">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <span><?= htmlspecialchars($res['name']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save" id="saveRestrictionsBtn">Guardar Restricciones</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 8: Restricciones Físicas -->
        <div class="form-section orange-bg" id="physicalRestrictionsSection">
            <div class="section-header">
                <h3 class="center"><i class="fas fa-ruler-combined"></i> Restricciones Físicas</h3>
            </div>
            
            <div class="physical-restrictions-container">
                <div class="physical-restriction">
                    <div class="input-with-icon">
                        <i class="fas fa-ruler-vertical"></i>
                        <input type="number" step="0.01" min="0" name="altura_maxima" placeholder="Altura máxima permitida (metros)" value="<?= htmlspecialchars($formData['altura_maxima'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="physical-restriction">
                    <div class="input-with-icon">
                        <i class="fas fa-tachometer-alt"></i>
                        <input type="number" min="0" name="velocidad_maxima" placeholder="Velocidad máxima (km/h)" value="<?= htmlspecialchars($formData['velocidad_maxima'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 10: Vista Previa -->
        <div class="form-section purple-bg">
            <h3 class="center"><i class="fas fa-eye"></i> Vista Previa</h3>
            <button type="button" class="btn-action purple" id="previewBtn">
                <i class="fas fa-search"></i> Ver Vista Previa
            </button>
        </div>

        <!-- SECCIÓN 11: Envío del Formulario -->
        <div class="form-section submit-section">
            <div class="terms-agreement">
                <input type="checkbox" id="acceptTerms" name="accept_terms" required>
                <label for="acceptTerms">Acepto los <a href="#">términos y condiciones</a> y <a href="#">política de privacidad</a></label>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Publicar Parqueo
            </button>
        </div>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/pages/publicar-parqueo.js"></script>