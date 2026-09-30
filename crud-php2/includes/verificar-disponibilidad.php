<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$parking_id = intval($_POST['parking_id']);
$vehicle_type_id = intval($_POST['vehicle_type_id']);
$start_date = $_POST['start_date'];
$start_time = $_POST['start_time'];
$end_date = $_POST['end_date'];
$end_time = $_POST['end_time'];

// Validaciones básicas
if (empty($parking_id) || empty($vehicle_type_id) || empty($start_date) || 
    empty($start_time) || empty($end_date) || empty($end_time)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Combinar fecha y hora
$fechaHoraInicio = "$start_date $start_time:00";
$fechaHoraFin = "$end_date $end_time:00";

// Verificar que las fechas son válidas
if (strtotime($fechaHoraInicio) >= strtotime($fechaHoraFin)) {
    echo json_encode(['success' => false, 'message' => 'La fecha de fin debe ser posterior a la de inicio']);
    exit;
}

// Verificar capacidad y disponibilidad
$capacity_stmt = $conex->prepare("
    SELECT pvc.capacity, pvc.reservable_vehicle_c 
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

// Verificar reservas existentes en el mismo horario
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

$available_spaces = $capacity['reservable_vehicle_c'] - $reservations_result['active_reservations'];

if ($available_spaces <= 0) {
    echo json_encode(['success' => false, 'message' => 'No hay espacios disponibles para las fechas seleccionadas']);
    exit;
}

// Obtener tarifa aplicable
$fee_stmt = $conex->prepare("
    SELECT price, time_unit 
    FROM parking_fees 
    WHERE parking_id = ? AND vehicle_type_id = ? 
    AND fee_type = 'normal'
    LIMIT 1
");
$fee_stmt->bind_param("ii", $parking_id, $vehicle_type_id);
$fee_stmt->execute();
$fee_result = $fee_stmt->get_result();

$hourly_rate = '0.00';
if ($fee_result->num_rows > 0) {
    $fee = $fee_result->fetch_assoc();
    $hourly_rate = is_numeric($fee['price']) ? $fee['price'] : '0.00';
}

// Calcular tarifa estimada
$start = new DateTime($fechaHoraInicio);
$end = new DateTime($fechaHoraFin);
$hours = $end->diff($start)->h + ($end->diff($start)->days * 24);
$estimated_fee = $hours * floatval($hourly_rate);

// Aplicar descuento si hay promociones (aquí puedes añadir lógica de descuentos)
$discount = 0;
// Ejemplo: 10% de descuento para reservas de más de 4 horas
if ($hours > 4) {
    $discount = $estimated_fee * 0.10;
    $estimated_fee -= $discount;
}

echo json_encode([
    'success' => true,
    'available_spaces' => $available_spaces,
    'hourly_rate' => number_format(floatval($hourly_rate), 2),
    'estimated_fee' => number_format($estimated_fee, 2),
    'discount' => number_format($discount, 2),
    'duration_hours' => $hours
]);
?>