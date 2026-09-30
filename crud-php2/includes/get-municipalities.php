<?php
include('conexion.php');

$department = isset($_GET['department']) ? $_GET['department'] : '';

if (!empty($department)) {
    $department = $conex->real_escape_string($department);
    $sql = "SELECT DISTINCT municipality 
            FROM locations 
            WHERE department = '$department'
            ORDER BY municipality";
    
    $result = $conex->query($sql);
    $municipalities = [];
    
    while ($row = $result->fetch_assoc()) {
        $municipalities[] = $row['municipality'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($municipalities);
    exit;
}

echo json_encode([]);