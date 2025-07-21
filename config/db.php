<?php
$host = 'localhost:3306'; // Cambia el puerto si es necesario
$user = 'root';
$pass = '';
$db = 'gym_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
?>