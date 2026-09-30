<?php
$body_class = 'page-parqueos-publicados';
session_start();
include('conexion.php');
include('includes/header.php');
$page_title = "Parking SV - Parqueos Publicados";

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Obtener vehículos del usuario
$userVehicles = [];
if ($isLoggedIn) {
    $vehicleSql = "SELECT vt.id, vt.category_name 
                   FROM user_vehicles uv
                   JOIN vehicle_types vt ON vt.id = uv.vehicle_type_id
                   WHERE uv.user_id = $userId";
    $vehicleResult = $conex->query($vehicleSql);
    while ($row = $vehicleResult->fetch_assoc()) {
        $userVehicles[] = $row;
    }
}

// Obtener departamentos únicos desde la tabla locations
$departments = [];
$deptSql = "SELECT DISTINCT department FROM locations ORDER BY department";
$deptResult = $conex->query($deptSql);
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['department'];
}

// Consulta base con filtros
$sql = "SELECT p.id, p.name, l.department, l.municipality, 
               p.schedule, p.is_24_7,
               (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS rating,
               (SELECT image_url FROM parking_images WHERE parking_id = p.id AND is_primary = 1 LIMIT 1) AS image_url";

// Calcular distancia si el usuario tiene ubicación
$distanceField = "";
$distanceJoin = "";

// Aplicar filtros
$filtersApplied = false;

// Filtro por vehículo
if (isset($_GET['vehicle']) && is_numeric($_GET['vehicle'])) {
    $vehicleId = intval($_GET['vehicle']);
    $sql .= " AND EXISTS (
        SELECT 1 FROM parking_vehicle_capacities pvc 
        WHERE pvc.parking_id = p.id 
        AND pvc.vehicle_type_id = $vehicleId
        AND pvc.capacity > 0
    )";
    $filtersApplied = true;
}

// Filtro por departamento
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $department = $conex->real_escape_string($_GET['department']);
    $sql .= " AND l.department = '$department'";
    $filtersApplied = true;
}

// Filtro por municipio
if (isset($_GET['municipality']) && !empty($_GET['municipality'])) {
    $municipality = $conex->real_escape_string($_GET['municipality']);
    $sql .= " AND l.municipality = '$municipality'";
    $filtersApplied = true;
}

// Filtro por fecha (día de la semana)
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $dayMap = [
        'monday' => 'lunes',
        'tuesday' => 'martes',
        'wednesday' => 'miercoles',
        'thursday' => 'jueves',
        'friday' => 'viernes',
        'saturday' => 'sabado',
        'sunday' => 'domingo'
    ];
    
    $selectedDay = strtolower($_GET['date']);
    $daySpanish = isset($dayMap[$selectedDay]) ? $dayMap[$selectedDay] : '';
    
    if ($daySpanish) {
        $sql .= " AND (
            p.is_24_7 = 1 OR 
            JSON_CONTAINS_PATH(p.schedule, 'one', '$.\"$daySpanish\"') OR
            JSON_CONTAINS_PATH(p.schedule, 'one', '$.\"" . ucfirst($daySpanish) . "\"')
        )";
        $filtersApplied = true;
    }
}

// Filtro por favoritos
if (isset($_GET['favorites']) && $isLoggedIn) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM favorites f 
        WHERE f.parking_id = p.id 
        AND f.user_id = $userId
    )";
    $filtersApplied = true;
}

// Filtro por precio máximo
if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $maxPrice = floatval($_GET['max_price']);
    $sql .= " AND EXISTS (
        SELECT 1 FROM parking_fees pf
        WHERE pf.parking_id = p.id
        AND pf.fee_type = 'normal'
        AND pf.time_unit = 'hora'
        AND CAST(REPLACE(pf.price, ',', '.') AS DECIMAL(10,2)) <= $maxPrice
    )";
    $filtersApplied = true;
}

// Filtro por reservable
if (isset($_GET['reservable']) && $_GET['reservable'] != 'all') {
    if ($_GET['reservable'] == 'yes') {
        $sql .= " AND EXISTS (
            SELECT 1 FROM parking_vehicle_capacities pvc
            WHERE pvc.parking_id = p.id
            AND pvc.reservable_vehicle_c > 0
        )";
    } else {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM parking_vehicle_capacities pvc
            WHERE pvc.parking_id = p.id
            AND pvc.reservable_vehicle_c > 0
        )";
    }
    $filtersApplied = true;
}

// Filtro por especificaciones del usuario
if (isset($_GET['specs']) && $isLoggedIn) {
    $specs = is_array($_GET['specs']) ? $_GET['specs'] : [$_GET['specs']];
    
    foreach ($specs as $specId) {
        $specId = intval($specId);
        switch ($specId) {
            case 1: // Discapacitad@ a bordo
                $sql .= " AND EXISTS (
                    SELECT 1 FROM parking_capacities pc
                    WHERE pc.parking_id = p.id
                    AND pc.disability_spaces > 0
                )";
                break;
            case 2: // Conductor/a de Taxi
                $sql .= " AND EXISTS (
                    SELECT 1 FROM parking_capacities pc
                    WHERE pc.parking_id = p.id
                    AND pc.taxi_spaces > 0
                )";
                break;
            case 3: // Futura mamá a bordo
                $sql .= " AND EXISTS (
                    SELECT 1 FROM parking_capacities pc
                    WHERE pc.parking_id = p.id
                    AND pc.pregnant_people_spaces > 0
                )";
                break;
            case 4: // Mascotas a bordo
                $sql .= " AND EXISTS (
                    SELECT 1 FROM parking_services ps
                    JOIN services s ON s.id = ps.service_id
                    WHERE ps.parking_id = p.id
                    AND s.name = 'Mascotas permitidas'
                )";
                break;
            case 5: // Vehículo eléctrico
                $sql .= " AND EXISTS (
                    SELECT 1 FROM parking_services ps
                    JOIN services s ON s.id = ps.service_id
                    WHERE ps.parking_id = p.id
                    AND s.name = 'Carga para autos eléctricos'
                )";
                break;
            case 6: // Altura del vehículo
                // Obtener la altura del vehículo del usuario
                $userHeightSql = "SELECT value FROM user_specifications WHERE user_id = $userId AND specification_type_id = 6";
                $userHeightResult = $conex->query($userHeightSql);
                if ($userHeightResult->num_rows > 0) {
                    $userHeight = $userHeightResult->fetch_assoc()['value'];
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM parking_restrictions pr
                        WHERE pr.parking_id = p.id
                        AND pr.max_height >= $userHeight
                    )";
                }
                break;
        }
    }
    $filtersApplied = true;
}

// Construir consulta final
$sql = "SELECT p.id, p.name, l.department, l.municipality, 
               p.schedule, p.is_24_7, r.rating, pi.image_url
        $distanceField
        FROM parkings p
        JOIN locations l ON p.location_id = l.id
        LEFT JOIN (
            SELECT parking_id, AVG(rating) AS rating 
            FROM reviews 
            GROUP BY parking_id
        ) r ON r.parking_id = p.id
        LEFT JOIN (
            SELECT parking_id, image_url 
            FROM parking_images 
            WHERE is_primary = 1
        ) pi ON pi.parking_id = p.id
        WHERE p.status = 'activo'";

$result = $conex->query($sql);
?>

<div id="auth-status" data-is-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>" style="display: none;"></div>

<link rel="stylesheet" href="assets/css/pages/parqueos-publicados.css">

<div class="container">
    <div class="page-header">
        <h1>¡Aquí está lo que <span class="highlight">buscas</span>!</h1>
    </div>
    
    <div class="search-container">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Buscar parqueo...">
        </div>
        <button class="filter-btn" id="filter-btn">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </div>
    
<!-- Filtros desplegables -->
<div class="filters-container" id="filters-container" style="display: none;">
    <form id="filters-form">
        <!-- Filtro por vehículo -->
        <?php if (!empty($userVehicles)): ?>
        <div class="filter-group">
            <label>Mi vehículo:</label>
            <div class="vehicle-options">
                <?php foreach ($userVehicles as $vehicle): ?>
                <label class="vehicle-option">
                    <input type="radio" name="vehicle" value="<?= $vehicle['id'] ?>">
                    <i class="fas fa-<?= 
                        $vehicle['id'] == 1 ? 'motorcycle' : 
                        ($vehicle['id'] == 9 ? 'bicycle' : 'car')
                    ?>"></i>
                    <?= htmlspecialchars($vehicle['category_name']) ?>
                </label>
                <?php endforeach; ?>
                <label class="vehicle-option">
                    <input type="radio" name="vehicle" value="all" checked>
                    <i class="fas fa-all"></i> Todos los vehículos
                </label>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtro por ubicación -->
        <div class="filter-group">
            <label><i class="fas fa-map-marker-alt"></i> Ubicación:</label>
            <div class="location-filters">
                <select name="department" id="department-filter">
                    <option value="">Todos los departamentos</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="municipality" id="municipality-filter" disabled>
                    <option value="">Todos los municipios</option>
                </select>
            </div>
        </div>
        
        <!-- Filtro por precio -->
        <div class="filter-group">
            <label><i class="fas fa-tag"></i> Precio máximo por hora:</label>
            <div class="price-filter">
                <input type="range" name="max_price" id="max-price" min="0" max="50" step="0.5" value="50">
                <div class="price-values">
                    <span>$0</span>
                    <span id="current-price">$50.00</span>
                    <span>$50+</span>
                </div>
            </div>
        </div>
        
        <!-- Filtro por disponibilidad de reserva -->
        <div class="filter-group">
            <label><i class="fas fa-calendar-check"></i> Reservas:</label>
            <div class="reservation-options">
                <label class="reservation-option">
                    <input type="radio" name="reservable" value="all" checked>
                    <i class="fas fa-globe"></i> Todos
                </label>
                <label class="reservation-option">
                    <input type="radio" name="reservable" value="yes">
                    <i class="fas fa-check-circle" style="color: #4CAF50;"></i> Solo reservables
                </label>
                <label class="reservation-option">
                    <input type="radio" name="reservable" value="no">
                    <i class="fas fa-times-circle" style="color: #f44336;"></i> No reservables
                </label>
            </div>
        </div>
        
        <!-- Filtro por horario -->
        <div class="filter-group">
            <label><i class="far fa-clock"></i> Disponible en:</label>
            <select name="date" id="date-filter">
                <option value="">Cualquier día</option>
                <option value="monday">Lunes</option>
                <option value="tuesday">Martes</option>
                <option value="wednesday">Miércoles</option>
                <option value="thursday">Jueves</option>
                <option value="friday">Viernes</option>
                <option value="saturday">Sábado</option>
                <option value="sunday">Domingo</option>
            </select>
        </div>
        
        <!-- Filtro por especificaciones del usuario -->
        <?php 
        // Obtener especificaciones del usuario si está logueado
        $userSpecs = [];
        if ($isLoggedIn) {
            $specsSql = "SELECT ust.id, ust.name, ust.icon, us.value 
                         FROM user_specification_types ust
                         LEFT JOIN user_specifications us ON ust.id = us.specification_type_id AND us.user_id = $userId
                         ORDER BY ust.name";
            $specsResult = $conex->query($specsSql);
            while ($row = $specsResult->fetch_assoc()) {
                $userSpecs[] = $row;
            }
        }
        ?>
        
        <?php if (!empty($userSpecs)): ?>
        <div class="filter-group">
            <label><i class="fas fa-user-cog"></i> Mis necesidades:</label>
            <div class="specs-options">
                <?php foreach ($userSpecs as $spec): 
                    $isActive = !is_null($spec['value']);
                ?>
                <label class="spec-option <?= $isActive ? 'active' : '' ?>" data-spec-id="<?= $spec['id'] ?>">
                    <input type="checkbox" name="specs[]" value="<?= $spec['id'] ?>" <?= $isActive ? 'checked' : '' ?>>
                    <i class="fas fa-<?= $spec['icon'] ?>"></i> 
                    <?= htmlspecialchars($spec['name']) ?>
                    <?php if ($isActive && $spec['value']): ?>
                    <span class="spec-value">(<?= $spec['value'] ?>)</span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtro por favoritos -->
        <?php if ($isLoggedIn): ?>
        <div class="filter-group">
            <label class="favorites-filter">
                <input type="checkbox" name="favorites" value="1">
                <i class="fas fa-heart" style="color: #e91e63;"></i> Solo mis favoritos
            </label>
        </div>
        <?php endif; ?>
        
        <div class="filter-buttons">
            <button type="button" id="apply-filters" class="btn-primary">
                <i class="fas fa-check"></i> Aplicar filtros
            </button>
            <button type="button" id="reset-filters" class="btn-secondary">
                <i class="fas fa-times"></i> Limpiar filtros
            </button>
        </div>
    </form>
</div>
    
    <h2 class="section-title">Parqueos más buscados</h2>
    
    <div class="parkings-grid" id="parkings-container">
        <?php while($parking = $result->fetch_assoc()): 
            $rating = $parking['rating'];
            $isNew = empty($rating) || $rating == 0;
            $image_url = $parking['image_url'] ?: 'assets/images/parking-default.png';
            $schedule_data = $parking['schedule'];
            $is_24_7 = $parking['is_24_7'];
        ?>
        <div class="parking-card-container">
            <div class="parking-card" 
                 data-parking-id="<?= $parking['id'] ?>" 
                 data-schedule='<?= $schedule_data ?>'
                 data-is-24-7="<?= $is_24_7 ?>">
                
                <!-- Botón de favoritos -->
                <div class="save-icon" data-parking-id="<?= $parking['id'] ?>">
                    <i class="far fa-bookmark"></i>
                </div>    
                <a href="detalles-parqueo.php?id=<?= $parking['id'] ?>" class="parking-card-link">
                    <div class="card-image">
                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($parking['name']) ?>">
                    </div>
                    <div class="card-content">
                        <h3><?= htmlspecialchars($parking['name']) ?></h3>
                        <div class="location"><?= htmlspecialchars($parking['department']) ?>, <?= htmlspecialchars($parking['municipality']) ?></div>
                        
                        <div class="schedule-rating">
                            <div class="schedule-status-container">
                                <div class="schedule">
                                    <i class="far fa-clock"></i> 
                                    <span class="schedule-text"></span>
                                </div>
                                <div class="open-status"></div>
                            </div>
                            <?php if ($isNew): ?>
                                <div class="new-badge">Nuevo</div>
                            <?php else: ?>
                                <div class="rating"><?= number_format($rating, 1) ?> ★</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php 
$conex->close();
include('includes/footer.php'); 
?>

<script src="assets/js/pages/parqueos-publicados.js"></script>