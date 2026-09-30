<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = intval($_POST['user_id']);
$password = $_POST['password'];

// Verificar que el usuario coincide con la sesión
if ($user_id !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Error de autenticación']);
    exit;
}

// Inicializar contador de intentos en sesión
if (!isset($_SESSION['password_attempts'])) {
    $_SESSION['password_attempts'] = 3;
    $_SESSION['last_attempt'] = time();
}

// Verificar si está bloqueado temporalmente
if (isset($_SESSION['blocked_until']) && time() < $_SESSION['blocked_until']) {
    $remaining = $_SESSION['blocked_until'] - time();
    echo json_encode([
        'success' => false, 
        'message' => 'Demasiados intentos. Espera ' . ceil($remaining / 60) . ' minutos.'
    ]);
    exit;
}

// Obtener hash de contraseña del usuario
$stmt = $conex->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$user = $result->fetch_assoc();

// Verificar contraseña
if (password_verify($password, $user['password_hash'])) {
    // Contraseña correcta - resetear intentos
    $_SESSION['password_attempts'] = 3;
    unset($_SESSION['blocked_until']);
    $_SESSION['identity_verified'] = true;
    $_SESSION['identity_verified_at'] = time();
    
    echo json_encode(['success' => true, 'message' => 'Identidad verificada']);
} else {
    // Contraseña incorrecta
    $_SESSION['password_attempts']--;
    
    if ($_SESSION['password_attempts'] <= 0) {
        // Bloquear por 30 minutos
        $_SESSION['blocked_until'] = time() + (30 * 60);
        echo json_encode([
            'success' => false, 
            'message' => 'Demasiados intentos fallidos. Cuenta bloqueada por 30 minutos.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Contraseña incorrecta. Te quedan ' . $_SESSION['password_attempts'] . ' intentos.'
        ]);
    }
}
?>