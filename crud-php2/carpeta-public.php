<?php
session_start();
include('conexion.php');
include('includes/header.php');

if (!isset($_GET['token'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'];

// Obtener carpeta por token
$sqlFolder = "SELECT f.*, u.full_name AS owner_name
              FROM favorite_folders f
              JOIN users u ON f.user_id = u.id
              WHERE f.share_token = ? AND f.is_public = 1";
$stmtFolder = $conex->prepare($sqlFolder);
$stmtFolder->bind_param("s", $token);
$stmtFolder->execute();
$folder = $stmtFolder->get_result()->fetch_assoc();

if (!$folder) {
    header('Location: index.php');
    exit;
}

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
$stmtParkings->bind_param("i", $folder['id']);
$stmtParkings->execute();
$parkings = $stmtParkings->get_result();
?>

<link rel="stylesheet" href="assets/css/pages/parqueos-publicados.css">

<div class="container">
    <div class="page-header">
        <h1 style="color: <?= $folder['color'] ?>">
            <?= htmlspecialchars($folder['name']) ?>
            <small>por <?= htmlspecialchars($folder['owner_name']) ?></small>
        </h1>
        <p>Carpeta compartida públicamente</p>
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
                <img src="assets/images/no-favorites.svg" alt="Sin favoritos">
                <h3>Esta carpeta está vacía</h3>
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