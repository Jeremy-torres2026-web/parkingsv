<?php
session_start();
include('includes/header.php');
include('conexion.php');
$page_title = "Parking SV - Tus Parqueos";

// Verificar sesión como en mi-cuenta.php
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

// Obtener datos del usuario como en mi-cuenta.php
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conex->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    header("Location: mi-cuenta.php");
    exit();
}

$user = $result_user->fetch_assoc();

// Verificar si es owner como en mi-cuenta.php
if ($user['user_type'] !== 'owner') {
    header("Location: mi-cuenta.php");
    exit();
}

// Consulta para obtener los parqueos del dueño
$sql = "SELECT p.id, p.name, p.description, l.department, l.municipality, 
               (SELECT image_url FROM parking_images WHERE parking_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
        FROM parkings p
        JOIN locations l ON p.location_id = l.id
        WHERE p.owner_id = ?
        ORDER BY p.created_at DESC";

$stmt = $conex->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<link rel="stylesheet" href="assets/css/pages/mis-parqueos.css">
<link rel="stylesheet" href="assets/css/pages/parqueos-publicados.css">
<br>
<h1 class="page-header" style="text-align:center;">Parqueos que das a <span class="highlight">conocer</span></h1>

<div class="container">
    <div class="search-container">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Buscar parqueo...">
        </div>
    </div>

    <div class="parkings-grid" id="parkings-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($parking = $result->fetch_assoc()): 
                $image_url = $parking['image_url'] ?: 'assets/images/parking-default.png';
            ?>
                <div class="parking-card-container">
                    <div class="parking-card" data-parking-id="<?= $parking['id'] ?>">
                        <a href="ver-editar-parqueo.php?id=<?= $parking['id'] ?>" class="parking-card-link">
                            <div class="card-image">
                                <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($parking['name']) ?>">
                            </div>
                            <div class="card-content">
                                <h3><?= htmlspecialchars($parking['name']) ?></h3>
                                <div class="location"><?= htmlspecialchars($parking['department']) ?>, <?= htmlspecialchars($parking['municipality']) ?></div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-my-parkings-container">
                <div class="no-my-parkings-illustration">
                    <img src="https://placehold.co/180x140?text=Sin+parqueos" alt="Sin parqueos publicados">
                </div>
                <h3 class="no--my-parkings-title">¡Aún no has publicado ningún parqueo!</h3>
                <p class="no-my-parkings-description">
                    Comparte tus parqueos con miles de usuarios. <br>
                    Haz clic en el botón <span class="no-my-parkings-plus"><i class="fas fa-plus-circle"></i></span> para publicar tu primer parqueo y empezar a recibir reservaciones.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Botón flotante para añadir parqueo -->
<div class="floating-parking-adder">
    <a href="publicar-parqueo.php" class="btn btn-primary btn-circle btn-xl has-aura" title="Publicar parqueo">
        <i class="fas fa-plus"></i>
    </a>
</div>

<?php 
$stmt->close();
$stmt_user->close();
$conex->close();
include('includes/footer.php'); 
?>
<script src="assets/js/pages/mis-parqueos.js"></script>