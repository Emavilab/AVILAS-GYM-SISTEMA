<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM usuarios WHERE usuario = '$usuario'");
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario'] = $usuario;
            $conn->query("UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario = '$usuario'");
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Avilas Gym</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-4">
            <form method="POST" class="card p-4 shadow text-center">
                <img src="imagen/1.png" alt="Logo Avilas Gym" 
                     class="img-fluid mb-4 mx-auto d-block rounded-circle border border-3 border-primary shadow"
                     style="max-height: 180px; max-width: 180px; object-fit: cover;">
                <h2 class="mb-4">Iniciar Sesión</h2>

                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'usuario_eliminado'): ?>
                    <div class="alert alert-warning text-center">
                        Tu usuario fue eliminado. Por favor contacta al administrador.
                    </div>
                <?php endif; ?>

                <input name="usuario" class="form-control mb-3" placeholder="Usuario" required>
                <input name="password" type="password" class="form-control mb-3" placeholder="Contraseña" required>
                <button class="btn btn-primary w-100">Ingresar</button>
                <?php
                if (isset($error)) {
                    echo '<div class="alert alert-danger mt-3">' . $error . '</div>';
                }
                ?>
            </form>
        </div>
    </div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
