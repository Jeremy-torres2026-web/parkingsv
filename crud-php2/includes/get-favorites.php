<?php
session_start();
require_once(__DIR__ . '/../conexion.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "SELECT p.id, p.name, l.department, l.municipality, 
        CONCAT(JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.apertura')), ' - ', 
               JSON_UNQUOTE(JSON_EXTRACT(p.schedule, '$.lunes.cierre'))) AS horario,
        (SELECT AVG(rating) FROM reviews WHERE parking_id = p.id) AS rating,
        (SELECT image_url FROM parking_images WHERE parking_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
        FROM favorites f
        JOIN parkings p ON f.parking_id = p.id
        JOIN locations l ON p.location_id = l.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC";

$stmt = $conex->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$output = '';
if ($result->num_rows > 0) {
    while($parking = $result->fetch_assoc()) {
        $rating = $parking['rating'];
        $isNew = empty($rating) || $rating == 0;
        $image_url = $parking['image_url'] ?: 'assets/images/parking-default.png';
        
        $output .= '<div class="parking-card-container">';
        $output .= '<div class="parking-card" data-parking-id="'.htmlspecialchars($parking['id']).'">';
        $output .= '<div class="save-icon active"><i class="fas fa-bookmark"></i></div>';
        $output .= '<a href="detalles-parqueo.php?id='.htmlspecialchars($parking['id']).'" class="parking-card-link">';
        $output .= '<div class="card-image"><img src="'.htmlspecialchars($image_url).'" alt="'.htmlspecialchars($parking['name']).'"></div>';
        $output .= '<div class="card-content">';
        $output .= '<h3>'.htmlspecialchars($parking['name']).'</h3>';
        $output .= '<div class="location">'.htmlspecialchars($parking['department']).', '.htmlspecialchars($parking['municipality']).'</div>';
        $output .= '<div class="schedule-rating">';
        $output .= '<div class="schedule">'.htmlspecialchars($parking['horario']).'</div>';
        $output .= $isNew ? '<div class="new-badge">Nuevo</div>' : '<div class="rating">'.number_format($rating, 1).' ★</div>';
        $output .= '</div></div></a></div></div>';
    }
} else {
    $output = '<div class="no-favorites">';
    $output .= '<img src="assets/images/no-favorites.svg" alt="Sin favoritos">';
    $output .= '<h3>Aún no tienes parqueos favoritos</h3>';
    $output .= '<p>Guarda tus parqueos favoritos para encontrarlos fácilmente</p>';
    $output .= '<a href="parqueos.php" class="btn btn-primary">Explorar parqueos</a>';
    $output .= '</div>';
}

echo json_encode(['html' => $output]);
$stmt->close();
$conex->close();