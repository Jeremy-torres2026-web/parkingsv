<?php
session_start();
include('conexion.php');
include('includes/header.php');
$page_title = "Parking SV - Mis Favoritos";

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Obtener carpetas del usuario
$sqlFolders = "SELECT id, name, color, is_public, share_token 
               FROM favorite_folders 
               WHERE user_id = ? 
               ORDER BY created_at DESC";
$stmtFolders = $conex->prepare($sqlFolders);
$stmtFolders->bind_param("i", $userId);
$stmtFolders->execute();
$folders = $stmtFolders->get_result();

// Obtener parqueos favoritos (para el panel de creación)
$sqlFavorites = "SELECT p.id, p.name 
                 FROM favorites f
                 JOIN parkings p ON f.parking_id = p.id
                 WHERE f.user_id = ? AND (f.folder_id IS NULL OR f.folder_id = 0)";
$stmtFavorites = $conex->prepare($sqlFavorites);
$stmtFavorites->bind_param("i", $userId);
$stmtFavorites->execute();
$favorites = $stmtFavorites->get_result();
?>

<link rel="stylesheet" href="assets/css/pages/parqueos-publicados.css">
<link rel="stylesheet" href="assets/css/pages/guardados.css">

<div class="container">
    <div class="page-header">
        <h1>Mis <span class="highlight">favoritos</span></h1>
    </div>

    <!-- Sección de Carpetas -->
    <div class="folders-section">
        <h2 class="section-title">Carpetas</h2>
        <div class="folders-container" id="folders-container">
            <?php if ($folders->num_rows > 0): ?>
                <?php while($folder = $folders->fetch_assoc()): ?>
                    <div class="folder-card" data-folder-id="<?= $folder['id'] ?>" style="background-color: <?= $folder['color'] ?>;">
                        <div class="folder-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <h3><?= htmlspecialchars($folder['name']) ?></h3>
                        <div class="folder-actions">
                            <button class="btn-share" data-token="<?= $folder['share_token'] ?>">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-folders-container">
                    <div class="no-folders-illustration"><img src="img sources/no-carpets.png" alt="No carpeta"></div>
                    <h3 class="no-folders-title">¡Tu biblioteca de parqueos está vacía!</h3>
                    <p class="no-folders-description">
                        Organiza tus parqueos favoritos en carpetas temáticas para encontrarlos fácilmente. 
                        Crea tu primera carpeta para comenzar a personalizar tu experiencia.
                    </p>
                    
                    <div class="floating-btn-hint">
                        <p>Usa el botón flotante <i class="fas fa-plus-circle"></i> para crear tu primera carpeta</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sección de Favoritos sin carpeta -->
    <div class="favorites-section">
        <h2 class="section-title">Favoritos sin carpeta</h2>
        <div class="parkings-grid" id="favorites-container">
            <?php
            $sqlParkings = "SELECT p.id, p.name, l.department, l.municipality, 
                           CONCAT(JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.apertura')), ' - ', 
                                  JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.cierre'))) AS horario,
                           (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS rating,
                           (SELECT image_url FROM parking_images WHERE parking_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
                    FROM favorites f
                    JOIN parkings p ON f.parking_id = p.id
                    JOIN locations l ON p.location_id = l.id
                    WHERE f.user_id = ? AND (f.folder_id IS NULL OR f.folder_id = 0)
                    ORDER BY f.created_at DESC";
            $stmtParkings = $conex->prepare($sqlParkings);
            $stmtParkings->bind_param("i", $userId);
            $stmtParkings->execute();
            $result = $stmtParkings->get_result();

            if ($result->num_rows > 0):
                while($parking = $result->fetch_assoc()): 
                    $rating = $parking['rating'];
                    $isNew = empty($rating) || $rating == 0;
                    $image_url = $parking['image_url'] ?: 'assets/images/parking-default.png';
            ?>
                <div class="parking-card-container">
                    <div class="parking-card" data-parking-id="<?= $parking['id'] ?>">
                        <div class="save-icon active">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <a href="detalles-parqueo.php?id=<?= $parking['id'] ?>" class="parking-card-link">
                            <div class="card-image">
                                <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($parking['name']) ?>">
                            </div>
                            <div class="card-content">
                                <h3><?= htmlspecialchars($parking['name']) ?></h3>
                                <div class="location"><?= htmlspecialchars($parking['department']) ?>, <?= htmlspecialchars($parking['municipality']) ?></div>
                                <div class="schedule-rating">
                                    <div class="schedule"><?= $parking['horario'] ?></div>
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
            <?php else: ?>
                <div class="no-favorites-container">
                    <div class="no-favorites-illustration"><img src="img sources/no-guardados-o-parqueos.png" alt="No parqueos"></div>
                    <h3 class="no-favorites-title">¡Zona de parqueos desorganizada!</h3>
                    <p class="no-favorites-description">
                        Parece que no has organizado tus parqueos favoritos todavía. 
                        ¿Por qué no exploras los parqueos disponibles y comienzas a construir tu colección?
                    </p>
                    
                    <a href="parqueos.php" class="explore-btn">
                        <img src="img sources/Icon_parqueos_publicados-bg.png" alt="Parqueos">
                        Ver parqueos disponibles
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Botón flotante para crear carpetas -->
<div class="floating-folder-creator">
    <button id="quick-folder-btn" class="btn btn-primary btn-circle btn-xl has-aura">
        <i class="fas fa-plus"></i>
    </button>
    
    <div class="folder-creation-panel">
        <h5>Nueva Carpeta</h5>
        <div class="mb-3">
            <input type="text" class="form-control" id="quick-folder-name" placeholder="Nombre de la carpeta">
        </div>
        
        <div class="mb-3">
            <label>Color:</label>
            <div class="color-picker">
                <input type="color" id="quick-folder-color" value="#0C6FF9" hidden>
                <div class="color-options">
                    <div class="color-option active" data-color="#0C6FF9" style="background-color: #0C6FF9;"></div>
                    <div class="color-option" data-color="#4CAF50" style="background-color: #4CAF50;"></div>
                    <div class="color-option" data-color="#FF5722" style="background-color: #FF5722;"></div>
                    <div class="color-option" data-color="#9C27B0" style="background-color: #9C27B0;"></div>
                    <div class="color-option" data-color="#FFC107" style="background-color: #FFC107;"></div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label>Seleccionar parqueos:</label>
            <div class="parkings-selection">
                <?php 
                // Resetear el puntero del resultado para volver a iterar
                $favorites->data_seek(0);
                while($parking = $favorites->fetch_assoc()): 
                ?>
                    <div class="form-check">
                        <input class="form-check-input parking-checkbox" type="checkbox" 
                               value="<?= $parking['id'] ?>" id="parking-<?= $parking['id'] ?>">
                        <label class="form-check-label" for="parking-<?= $parking['id'] ?>">
                            <?= htmlspecialchars($parking['name']) ?>
                        </label>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <button id="create-folder-confirm" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Crear Carpeta
        </button>
    </div>
</div>

<?php 
$stmtFolders->close();
$stmtFavorites->close();
$stmtParkings->close();
$conex->close();
include('includes/footer.php'); 
?>

<script src="assets/js/pages/guardados.js"></script>