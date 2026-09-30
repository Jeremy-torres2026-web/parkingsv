<?php
$host = "localhost";
$user = "root";
$pass = ""; // tu contraseña de MySQL, normalmente vacía en XAMPP
$db = "parking_sv_db";

$conex = mysqli_connect($host, $user, $pass, $db);

if (!$conex) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>