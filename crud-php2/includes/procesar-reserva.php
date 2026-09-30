<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Verificar identidad
if (!isset($_SESSION['identity_verified']) || $_SESSION['identity_verified'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Verificación de identidad requerida']);
    exit;
}

// Limpiar flag de verificación después de usarlo
unset($_SESSION['identity_verified']);

$user_id = $_SESSION['user_id'];
$parking_id = intval($_POST['parking_id']);
$vehicle_type_id = intval($_POST['vehicle_type_id']);
$start_date = $_POST['start_date'];
$start_time = $_POST['start_time'];
$end_date = $_POST['end_date'];
$end_time = $_POST['end_time'];

// Validar datos
if (empty($parking_id) || empty($vehicle_type_id) || empty($start_date) || 
    empty($start_time) || empty($end_date) || empty($end_time)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

// Combinar fecha y hora
$fechaHoraInicio = "$start_date $start_time:00";
$fechaHoraFin = "$end_date $end_time:00";

// Verificar que el vehículo pertenece al usuario
$vehicle_check = $conex->prepare("
    SELECT 1 FROM user_vehicles 
    WHERE user_id = ? AND vehicle_type_id = ?
");
$vehicle_check->bind_param("ii", $user_id, $vehicle_type_id);
$vehicle_check->execute();
if (!$vehicle_check->get_result()->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Vehículo no válido o no registrado']);
    exit;
}

// Verificar disponibilidad (usando la misma lógica que verificar-disponibilidad.php)
$capacity_stmt = $conex->prepare("
    SELECT pvc.reservable_vehicle_c 
    FROM parking_vehicle_capacities pvc 
    WHERE pvc.parking_id = ? AND pvc.vehicle_type_id = ?
");
$capacity_stmt->bind_param("ii", $parking_id, $vehicle_type_id);
$capacity_stmt->execute();
$capacity_result = $capacity_stmt->get_result();

if ($capacity_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No hay capacidad para este tipo de vehículo']);
    exit;
}

$capacity = $capacity_result->fetch_assoc();

$reservations_stmt = $conex->prepare("
    SELECT COUNT(*) as active_reservations
    FROM reservations
    WHERE parking_id = ? 
    AND vehicle_type_id = ?
    AND status IN ('reservado', 'usado')
    AND (
        (fechaHoraInicio < ? AND fechaHoraFin > ?) OR
        (fechaHoraInicio < ? AND fechaHoraFin > ?) OR
        (fechaHoraInicio >= ? AND fechaHoraFin <= ?)
    )
");
$reservations_stmt->bind_param("iissssss", 
    $parking_id, $vehicle_type_id, 
    $fechaHoraFin, $fechaHoraInicio,
    $fechaHoraInicio, $fechaHoraFin,
    $fechaHoraInicio, $fechaHoraFin
);
$reservations_stmt->execute();
$reservations_result = $reservations_stmt->get_result()->fetch_assoc();

if ($reservations_result['active_reservations'] >= $capacity['reservable_vehicle_c']) {
    echo json_encode(['success' => false, 'message' => 'Ya no hay disponibilidad para las fechas seleccionadas']);
    exit;
}

// Generar código QR único
$codigo_qr = md5(uniqid() . $user_id . $parking_id . time() . rand(1000, 9999));

// Insertar reserva
$insert_stmt = $conex->prepare("
    INSERT INTO reservations (user_id, parking_id, vehicle_type_id, fechaHoraInicio, fechaHoraFin, codigo_qr, status)
    VALUES (?, ?, ?, ?, ?, ?, 'reservado')
");
$insert_stmt->bind_param("iiisss", $user_id, $parking_id, $vehicle_type_id, $fechaHoraInicio, $fechaHoraFin, $codigo_qr);

if ($insert_stmt->execute()) {
    $reservation_id = $conex->insert_id;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reserva creada exitosamente',
        'reservation_id' => $reservation_id,
        'qr_code' => $codigo_qr
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al crear la reserva: ' . $conex->error]);
}
?>