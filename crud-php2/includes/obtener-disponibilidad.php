<?php
require_once(__DIR__ . '/../conexion.php');

$parking_id = $_GET['parking_id'] ?? null;

if (!$parking_id) {
    echo json_encode(['success' => false, 'message' => 'ID de parqueo no especificado']);
    exit;
}

// Obtener disponibilidad actual con información de reservas
$stmt = $conex->prepare("
    SELECT 
        pc.general_capacity,
        pc.reservable_capacity,
        COALESCE(pa.available_spaces, pc.general_capacity) as available_spaces,
        -- Calcular espacios reservables disponibles
        (pc.reservable_capacity - COALESCE((
            SELECT COUNT(*) 
            FROM reservations 
            WHERE parking_id = ? 
            AND status = 'reservado'
            AND fechaHoraInicio <= NOW() 
            AND fechaHoraFin >= NOW()
        ), 0)) as available_reservable
    FROM parking_capacities pc
    LEFT JOIN parking_availability pa ON pc.parking_id = pa.parking_id
    WHERE pc.parking_id = ?
");

$stmt->bind_param("ii", $parking_id, $parking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'available' => $data['available_spaces'],
        'reservable' => max(0, $data['available_reservable']) // No permitir valores negativos
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Parqueo no encontrado']);
}