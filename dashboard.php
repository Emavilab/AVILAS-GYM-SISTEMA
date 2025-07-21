<?php
session_start();
require 'config/db.php';
if (!isset($_SESSION['usuario'])) header('Location: index.php');

$conn->query("UPDATE clientes SET estado = 'vencido' WHERE CURDATE() > fecha_vencimiento");
$activos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'activo'")->fetch_assoc()['total'];
$vencidos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'vencido'")->fetch_assoc()['total'];
$total = $conn->query("SELECT COUNT(*) AS total FROM clientes")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Avilas Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow">
                <h1 class="text-center mb-4">Bienvenido, <?php echo $_SESSION['usuario']; ?></h1>
                <div class="d-flex justify-content-center gap-4 mb-4 flex-wrap">
                    <div class="bg-success text-white p-4 rounded text-center">
                        <strong>Activos:</strong><br><?php echo $activos; ?>
                    </div>
                    <div class="bg-danger text-white p-4 rounded text-center">
                        <strong>Vencidos:</strong><br><?php echo $vencidos; ?>
                    </div>
                    <div class="bg-primary text-white p-4 rounded text-center">
                        <strong>Total de clientes:</strong><br><?php echo $total; ?>
                    </div>
                </div>
                <a href="clientes.php" class="btn btn-primary w-100 mb-2">Gestionar Clientes</a>
                <a href="logout.php" class="btn btn-outline-danger w-100">Cerrar sesión</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let timer;
function resetTimer() {
    clearTimeout(timer);
    timer = setTimeout(function() {
        window.location.href = 'logout.php';
    }, 120000); // 2 minutos en milisegundos
}

// Eventos que reinician el contador
['mousemove', 'keydown', 'mousedown', 'touchstart'].forEach(function(evt) {
    document.addEventListener(evt, resetTimer, false);
});

resetTimer(); // Iniciar el contador al cargar la página
</script>
</body>
</html>
