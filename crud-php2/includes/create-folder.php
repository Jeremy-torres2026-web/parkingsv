<?php
session_start();
require_once(__DIR__ . '/../conexion.php');
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$folderName = $_POST['name'] ?? '';
$folderColor = $_POST['color'] ?? '#0C6FF9';
$parkings = isset($_POST['parkings']) ? json_decode($_POST['parkings'], true) : [];

if (empty($folderName)) {
    $response['message'] = 'Nombre de carpeta vacío';
    echo json_encode($response);
    exit;
}

try {
    $conex->begin_transaction();

    // 1. Crear la carpeta
    $shareToken = bin2hex(random_bytes(6));
    $sqlFolder = "INSERT INTO favorite_folders (user_id, name, color, share_token) 
                 VALUES (?, ?, ?, ?)";
    $stmtFolder = $conex->prepare($sqlFolder);
    $stmtFolder->bind_param("isss", $userId, $folderName, $folderColor, $shareToken);
    $stmtFolder->execute();
    $folderId = $conex->insert_id;

    // 2. Asociar parqueos a la carpeta
    if (!empty($parkings)) {
        // Primero eliminar de favoritos sin carpeta si existen
        $sqlDelete = "DELETE FROM favorites WHERE user_id = ? AND parking_id = ? AND folder_id IS NULL";
        $stmtDelete = $conex->prepare($sqlDelete);
        
        // Insertar o actualizar en la nueva carpeta
        $sqlInsert = "INSERT INTO favorites (user_id, parking_id, folder_id) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE folder_id = VALUES(folder_id)";
        $stmtInsert = $conex->prepare($sqlInsert);
        
        foreach ($parkings as $parkingId) {
            // Eliminar de favoritos sin carpeta
            $stmtDelete->bind_param("ii", $userId, $parkingId);
            $stmtDelete->execute();
            
            // Insertar en la carpeta nueva
            $stmtInsert->bind_param("iii", $userId, $parkingId, $folderId);
            $stmtInsert->execute();
        }
    }

    $conex->commit();
    
    $response = [
        'success' => true,
        'folderId' => $folderId,
        'shareToken' => $shareToken,
        'folderName' => $folderName,
        'folderColor' => $folderColor
    ];
} catch (Exception $e) {
    $conex->rollback();
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    if (isset($stmtFolder)) $stmtFolder->close();
    if (isset($stmtDelete)) $stmtDelete->close();
    if (isset($stmtInsert)) $stmtInsert->close();
}

echo json_encode($response);
$conex->close();
?>