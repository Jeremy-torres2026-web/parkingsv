<?php
session_start();
require_once(__DIR__ . '/../conexion.php');

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para enviar una reseña.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parking_id = isset($_POST['parking_id']) ? intval($_POST['parking_id']) : 0;
    $user_id = $_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validar datos
    if ($parking_id <= 0) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Parqueo inválido.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas.']);
        exit;
    }

    if (empty($comment)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío.']);
        exit;
    }

    // Verificar si el usuario ya ha reseñado este parqueo
    $check_stmt = $conex->prepare("SELECT id FROM reviews WHERE parking_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $parking_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Ya has enviado una reseña para este parqueo.']);
        exit;
    }

    // Insertar reseña
    $stmt = $conex->prepare("INSERT INTO reviews (parking_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $parking_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reseña enviada con éxito.']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Error al enviar la reseña.']);
    }

    $stmt->close();
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}