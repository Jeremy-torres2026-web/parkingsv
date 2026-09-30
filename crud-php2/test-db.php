<?php
// test-db.php
require_once 'conexion.php';

// Verificar conexión
echo "<h2>Probando conexión...</h2>";
if ($conex) {
    echo "<p style='color:green'>✓ Conexión a la base de datos exitosa</p>";
} else {
    die("<p style='color:red'>✗ Error de conexión: " . mysqli_connect_error() . "</p>");
}

// Verificar tabla users
$result = mysqli_query($conex, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color:green'>✓ La tabla 'users' existe</p>";
} else {
    die("<p style='color:red'>✗ La tabla 'users' NO existe</p>");
}

// Verificar estructura de la tabla
echo "<h2>Estructura de la tabla users:</h2>";
$result = mysqli_query($conex, "DESCRIBE users");
echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>".$row['Field']."</td>";
    echo "<td>".$row['Type']."</td>";
    echo "<td>".$row['Null']."</td>";
    echo "<td>".$row['Key']."</td>";
    echo "<td>".$row['Default']."</td>";
    echo "</tr>";
}
echo "</table>";

// Insertar prueba
echo "<h2>Probando inserción...</h2>";
$test_email = "test_".time()."@test.com";
$result = mysqli_query($conex, 
    "INSERT INTO users (full_name, email, password_hash, phone_number, date_of_birth, user_type) 
    VALUES ('Test User', '$test_email', '".password_hash('test123', PASSWORD_DEFAULT)."', '12345678', '2000-01-01', 'customer')");

if ($result) {
    echo "<p style='color:green'>✓ Inserción de prueba exitosa (ID: ".mysqli_insert_id($conex).")</p>";
} else {
    echo "<p style='color:red'>✗ Error en inserción: " . mysqli_error($conex) . "</p>";
}

// Verificar datos
echo "<h2>Usuarios en la base de datos:</h2>";
$result = mysqli_query($conex, "SELECT id, full_name, email FROM users LIMIT 10");
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>#".$row['id']." - ".$row['full_name']." (".$row['email'].")</li>";
}
echo "</ul>";

mysqli_close($conex);
?>