<?php
require 'config/db.php';

// Actualizar estado de clientes
$conn->query("UPDATE clientes SET estado = 'vencido' WHERE CURDATE() > fecha_vencimiento");

// Obtener datos del resumen
$activos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'activo'")->fetch_assoc()['total'];
$vencidos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'vencido'")->fetch_assoc()['total'];
$total = $conn->query("SELECT COUNT(*) AS total FROM clientes")->fetch_assoc()['total'];

// Devolver datos como JSON
echo json_encode([
    'activos' => $activos,
    'vencidos' => $vencidos,
    'total' => $total
]);
?>