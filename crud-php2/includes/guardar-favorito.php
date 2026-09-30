<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../conexion.php');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parking_id']) && isset($_POST['action'])) {
    $userId = $_SESSION['user_id'];
    $parkingId = (int)$_POST['parking_id'];
    $action = $_POST['action'];
    $folderId = isset($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    
    try {
        if ($action === 'add') {
            // Verificar si ya existe
            $sql = "SELECT id FROM favorites WHERE user_id = ? AND parking_id = ?";
            $stmt = $conex->prepare($sql);
            $stmt->bind_param("ii", $userId, $parkingId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                $sql = "INSERT INTO favorites (user_id, parking_id, folder_id) VALUES (?, ?, ?)";
                $stmt = $conex->prepare($sql);
                $stmt->bind_param("iii", $userId, $parkingId, $folderId);
                $stmt->execute();
            } else if ($folderId !== null) {
                // Actualizar folder_id si ya existe
                $sql = "UPDATE favorites SET folder_id = ? WHERE user_id = ? AND parking_id = ?";
                $stmt = $conex->prepare($sql);
                $stmt->bind_param("iii", $folderId, $userId, $parkingId);
                $stmt->execute();
            }
            
            $response = ['success' => true, 'message' => 'Favorito agregado'];
        } elseif ($action === 'remove') {
            $sql = "DELETE FROM favorites WHERE user_id = ? AND parking_id = ?" . 
                   ($folderId !== null ? " AND folder_id = ?" : "");
            $stmt = $conex->prepare($sql);
            
            if ($folderId !== null) {
                $stmt->bind_param("iii", $userId, $parkingId, $folderId);
            } else {
                $stmt->bind_param("ii", $userId, $parkingId);
            }
            
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'Favorito eliminado'];
        } else {
            $response['message'] = 'Acción no válida';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
    }
} else {
    $response['message'] = 'Datos inválidos';
}

$conex->close();
echo json_encode($response);