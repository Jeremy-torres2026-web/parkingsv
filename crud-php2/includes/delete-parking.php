<?php
session_start();
require_once(__DIR__ . '/../conexion.php');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener ID del parqueo
$parking_id = $_POST['parking_id'] ?? 0;

// Verificar propiedad
$stmt = $conex->prepare("SELECT owner_id, location_id FROM parkings WHERE id = ?");
$stmt->bind_param("i", $parking_id);
$stmt->execute();
$result = $stmt->get_result();
$parking = $result->fetch_assoc();

if (!$parking || $parking['owner_id'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = "No tienes permiso para eliminar este parqueo";
    header("Location(__DIR__ . '/../mis-parqueos.php)");
    exit();
}

// Iniciar transacción
$conex->begin_transaction();

try {
    // 1. Eliminar imágenes físicas
    $images_stmt = $conex->prepare("SELECT image_url FROM parking_images WHERE parking_id = ?");
    $images_stmt->bind_param("i", $parking_id);
    $images_stmt->execute();
    $images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($images as $image) {
        if (file_exists($image['image_url'])) {
            unlink($image['image_url']);
        }
    }

    // 2. Eliminar registros relacionados en orden
    $tables = [
        'favorites' => 'parking_id',
        'parking_news' => 'parking_id',
        'parking_updates' => 'parking_id',
        'reviews' => 'parking_id',
        'parking_fees' => 'parking_id',
        'parking_images' => 'parking_id',
        'parking_vehicle_capacities' => 'parking_id',
        'parking_restriction_items' => 'parking_id',
        'parking_services' => 'parking_id',
        'parking_restrictions' => 'parking_id',
        'parking_capacities' => 'parking_id',
        'parkings' => 'id',
        'locations' => 'id'
    ];
    
    $location_id = $parking['location_id'];
    
    foreach ($tables as $table => $column) {
        $value = ($table === 'locations') ? $location_id : $parking_id;
        $sql = "DELETE FROM $table WHERE $column = ?";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("i", $value);
        $stmt->execute();
    }

    // Confirmar cambios
    $conex->commit();
    
    $_SESSION['success_message'] = "Parqueo eliminado exitosamente";
} catch (Exception $e) {
    // Revertir en caso de error
    $conex->rollback();
    $_SESSION['error_message'] = "Error al eliminar el parqueo: " . $e->getMessage();
}

header("Location: mis-parqueos.php");
exit();
?>