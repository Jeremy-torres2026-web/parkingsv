<?php
session_start();
try {
    require_once('conexion.php');
    if (!isset($conex) || !$conex) {
        throw new Exception("La conexión a la base de datos no está disponible");
    }
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

include('includes/header.php');

/*if (!isset($_GET['id'])) {
    header('Location: guardados.php');
    exit;
}*/

$folderId = (int)$_GET['id'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Obtener información de la carpeta
$sqlFolder = "SELECT f.*, u.full_name AS owner_name
              FROM favorite_folders f
              JOIN users u ON f.user_id = u.id
              WHERE f.id = ?";
$stmtFolder = $conex->prepare($sqlFolder);
$stmtFolder->bind_param("i", $folderId);
$stmtFolder->execute();
$folder = $stmtFolder->get_result()->fetch_assoc();

/*if (!$folder) {
    header('Location: guardados.php');
    exit;
}*/

// Verificar acceso
$isOwner = ($userId === $folder['user_id']);
$isPublic = ($folder['is_public'] == 1);
/*
if (!$isOwner && !$isPublic) {
    header('Location: guardados.php');
    exit;
}*/

// Obtener parqueos de la carpeta
$sqlParkings = "SELECT p.id, p.name, l.department, l.municipality, 
                CONCAT(JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.apertura')), ' - ', 
                       JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.cierre'))) AS horario,
                (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS rating,
                (SELECT image_url FROM parking_images WHERE parking_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
         FROM favorites f
         JOIN parkings p ON f.parking_id = p.id
         JOIN locations l ON p.location_id = l.id
         WHERE f.folder_id = ?
         ORDER BY f.created_at DESC";

$stmtParkings = $conex->prepare($sqlParkings);
$stmtParkings->bind_param("i", $folderId);
$stmtParkings->execute();
$parkings = $stmtParkings->get_result();
?>

<link rel="stylesheet" href="assets/css/pages/parqueos-publicados.css">

<div class="container">
    <!-- Botón de volver universal -->
<div class="universal-back-container">
    <a href="#" class="universal-back-button" id="universalBackButton">
        <img src="img sources/volver-bg.png" alt="Volver atrás" class="universal-back-icon">
        <span class="universal-back-text">Volver</span>
    </a>
</div>
    <div class="page-header">
        <h1 style="color: <?= $folder['color'] ?>">
            <?= htmlspecialchars($folder['name']) ?>
            <?php if (!$isOwner): ?>
                <small>de <?= htmlspecialchars($folder['owner_name']) ?></small>
            <?php endif; ?>
        </h1>
    </div>
    
    <div class="parkings-grid">
        <?php if ($parkings->num_rows > 0): ?>
            <?php while($parking = $parkings->fetch_assoc()): 
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
            <div class="no-favorites">
                <img src="img sources/!signal.png" alt="Sin favoritos" width="100px">
                <h3>Esta carpeta está vacía</h3>
                <p>No hay parqueos guardados en esta carpeta</p>
                <?php if ($isOwner): ?>
                    <a href="guardados.php" class="btn btn-primary">Agregar parqueos</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmtFolder->close();
$stmtParkings->close();
$conex->close();
include('includes/footer.php');
?>