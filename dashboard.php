<?php
session_start();
require 'config/db.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

// Verificar si el usuario aún existe
$usuario_actual = $_SESSION['usuario'];
$resultado = $conn->query("SELECT id FROM usuarios WHERE usuario = '$usuario_actual'");
if (!$resultado || $resultado->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: index.php?mensaje=usuario_eliminado");
    exit;
}

// Actualizar estado de clientes
$conn->query("UPDATE clientes SET estado = 'vencido' WHERE CURDATE() > fecha_vencimiento");

$activos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'activo'")->fetch_assoc()['total'];
$vencidos = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE estado = 'vencido'")->fetch_assoc()['total'];
$total = $conn->query("SELECT COUNT(*) AS total FROM clientes")->fetch_assoc()['total'];

$usuariosRegistrados = $conn->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc()['total'];
$ultimoAcceso = $conn->query("SELECT MAX(ultimo_acceso) AS fecha FROM usuarios")->fetch_assoc()['fecha'];

$modalMsg = '';
$modalType = 'success';

// Agregar usuario
if (isset($_POST['agregar_usuario'])) {
    $nuevo_usuario = $_POST['nuevo_usuario'];
    $nuevo_password = password_hash($_POST['nuevo_password'], PASSWORD_DEFAULT);
    if ($conn->query("INSERT INTO usuarios (usuario, password) VALUES ('$nuevo_usuario', '$nuevo_password')")) {
        $modalMsg = "Usuario agregado correctamente.";
        $modalType = "success";
    } else {
        $modalMsg = "Error al agregar usuario.";
        $modalType = "danger";
    }
}

// Editar usuario
if (isset($_POST['editar_confirmar'])) {
    $id = $_POST['editar_id'];
    $usuario = trim($_POST['editar_usuario']);
    $password = $_POST['editar_password'];

    if ($usuario === '') {
        $modalMsg = "El campo usuario no puede estar vacío.";
        $modalType = "danger";
    } else {
        $updates = ["usuario='" . $conn->real_escape_string($usuario) . "'"];
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $updates[] = "password='$password_hash'";
        }
        $updateSQL = "UPDATE usuarios SET " . implode(", ", $updates) . " WHERE id=$id";
        if ($conn->query($updateSQL)) {
            $modalMsg = "Usuario actualizado correctamente.";
            $modalType = "success";
        } else {
            $modalMsg = "Error al actualizar usuario.";
            $modalType = "danger";
        }
    }
}

// Eliminar usuario
if (isset($_GET['eliminar_usuario'])) {
    $id = intval($_GET['eliminar_usuario']);

    $res = $conn->query("SELECT usuario FROM usuarios WHERE id = $id");
    $usuario_eliminado = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['usuario'] : null;

    if ($conn->query("DELETE FROM usuarios WHERE id=$id")) {
        if ($usuario_eliminado === $_SESSION['usuario']) {
            session_destroy();
            header("Location: index.php?mensaje=usuario_eliminado");
            exit;
        }
        $modalMsg = "Usuario eliminado correctamente.";
        $modalType = "success";
    } else {
        $modalMsg = "Error al eliminar usuario.";
        $modalType = "danger";
    }
}

$usuarios = $conn->query("SELECT * FROM usuarios");

// Obtener el primer y último mes de registro
$primero = $conn->query("SELECT MIN(fecha_registro) AS primero FROM clientes")->fetch_assoc()['primero'];
$ultimo = date('Y-m-01'); // Mes actual

$labelsMeses = [];
$clientesPorMes = [];

if ($primero) {
    $inicio = new DateTime(date('Y-m-01', strtotime($primero)));
    $fin = new DateTime($ultimo);
    while ($inicio <= $fin) {
        $mes = $inicio->format('Y-m');
        $label = $inicio->format('M Y');
        $labelsMeses[] = $label;
        $res = $conn->query("SELECT COUNT(*) AS total FROM clientes WHERE DATE_FORMAT(fecha_registro, '%Y-%m') = '$mes'");
        $clientesPorMes[] = $res->fetch_assoc()['total'];
        $inicio->modify('+1 month');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Avilas Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { min-height: 100vh; }
        .sidebar { min-height: 100vh; background: #23395d; color: #fff; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #1a2940; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar d-flex flex-column p-3">
            <div class="mb-4 text-center">
                <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                <div class="fw-bold mt-2"><?php echo $_SESSION['usuario']; ?></div>
            </div>
            <ul class="nav nav-pills flex-column mb-auto">
                <li><a href="#" class="nav-link active" id="btnDashboard"><i class="bi bi-house me-2"></i>Dashboard</a></li>
                <li><a href="#" class="nav-link" id="btnClientes"><i class="bi bi-people me-2"></i>Gestionar Clientes</a></li>
                <li><a href="#" class="nav-link" id="btnUsuarios"><i class="bi bi-person-gear me-2"></i>Gestionar Usuarios</a></li>
                <li class="mt-4"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
        </nav>

        <main class="col-md-10 ms-sm-auto px-4">
            <div id="seccionDashboard">
                <h2 class="mt-4 mb-4">Resumen</h2>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center shadow">
                            <div class="card-body">
                                <h5 class="card-title text-success"><i class="bi bi-person-check-fill"></i> Activos</h5>
                                <h2 class="text-success"><?php echo $activos; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center shadow">
                            <div class="card-body">
                                <h5 class="card-title text-danger"><i class="bi bi-person-x-fill"></i> Vencidos</h5>
                                <h2 class="text-danger"><?php echo $vencidos; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center shadow">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><i class="bi bi-people-fill"></i> Total Clientes</h5>
                                <h2 class="text-primary"><?php echo $total; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center shadow">
                            <div class="card-body">
                                <h5 class="card-title text-info"><i class="bi bi-person-badge"></i> Usuarios registrados</h5>
                                <h2 class="text-info"><?php echo $usuariosRegistrados; ?></h2>
                                <p class="mt-2 mb-0 text-secondary">
                                    Último acceso: 
                                    <?php echo $ultimoAcceso ? date('d/m/Y H:i', strtotime($ultimoAcceso)) : 'Sin registros'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="card shadow mb-4">
    <div class="card-body">
        <h5 class="card-title text-center mb-3">Clientes registrados por mes</h5>
        <canvas id="graficoClientesMes" height="100"></canvas>
    </div>
</div>
                </div>
            </div>

            <div id="seccionClientes" style="display:none;">
                <iframe src="clientes.php" style="width:100%;height:80vh;border:none;"></iframe>
            </div>

            <div id="seccionUsuarios" style="display:none;">
                <div class="card shadow mb-4">
                    <div class="card-header bg-secondary text-white text-center">
                        <h4>Gestión de Usuarios</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-2 mb-4">
                            <div class="col-md-5">
                                <input name="nuevo_usuario" class="form-control" placeholder="Usuario" required>
                            </div>
                            <div class="col-md-5">
                                <input name="nuevo_password" type="password" class="form-control" placeholder="Contraseña" required>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button name="agregar_usuario" class="btn btn-success">Agregar</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($u = $usuarios->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm me-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditarUsuario"
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-usuario="<?php echo htmlspecialchars($u['usuario']); ?>">
                                                    Editar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEliminarUsuario"
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-usuario="<?php echo htmlspecialchars($u['usuario']); ?>">
                                                    Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if ($modalMsg): ?>
<div class="modal fade show" id="modalMensaje" tabindex="-1" aria-labelledby="modalMensajeLabel" style="display:block;" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-<?php echo $modalType; ?> text-white">
        <h5 class="modal-title"><?php echo $modalType == 'success' ? 'Éxito' : 'Error'; ?></h5>
        <a href="dashboard.php" class="btn-close" aria-label="Cerrar"></a>
      </div>
      <div class="modal-body text-center">
        <?php echo $modalMsg; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        ¿Seguro que deseas eliminar al usuario <span id="nombreUsuarioEliminar" class="fw-bold"></span>?
      </div>
      <div class="modal-footer">
        <a href="#" id="btnConfirmarEliminarUsuario" class="btn btn-danger">Eliminar</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title">Editar usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="editar_id" id="modalEditarId">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" id="modalEditarUsuario" name="editar_usuario" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva contraseña (opcional)</label>
            <input type="password" class="form-control" name="editar_password">
        </div>
      </div>
      <div class="modal-footer">
        <button name="editar_confirmar" class="btn btn-warning">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalMensaje = document.getElementById('modalMensaje');
    if (modalMensaje) {
        // Recarga la página automáticamente después de cerrar el modal with a delay
        modalMensaje.addEventListener('hidden.bs.modal', function () {
            setTimeout(function () {
                location.reload();
            }, 5000); // Espera 5 segundos antes de recargar
        });

        // Mostrar el modal al cargar la página
        var bsModal = bootstrap.Modal.getOrCreateInstance(modalMensaje);
        bsModal.show();
    }

    var eliminarModal = document.getElementById('modalEliminarUsuario');
    eliminarModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var usuario = button.getAttribute('data-usuario');
        document.getElementById('nombreUsuarioEliminar').textContent = usuario;
        document.getElementById('btnConfirmarEliminarUsuario').href = '?eliminar_usuario=' + id;
    });

    var editarModal = document.getElementById('modalEditarUsuario');
    editarModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var usuario = button.getAttribute('data-usuario');
        document.getElementById('modalEditarId').value = id;
        document.getElementById('modalEditarUsuario').value = usuario;
    });

    document.getElementById('btnDashboard').onclick = function () {
        document.getElementById('seccionDashboard').style.display = 'block';
        document.getElementById('seccionClientes').style.display = 'none';
        document.getElementById('seccionUsuarios').style.display = 'none';
        this.classList.add('active');
        document.getElementById('btnClientes').classList.remove('active');
        document.getElementById('btnUsuarios').classList.remove('active');

        // Actualizar datos del resumen dinámicamente
        fetch('resumen.php')
            .then(response => response.json())
            .then(data => {
                document.querySelector('.card-title.text-success + h2').textContent = data.activos;
                document.querySelector('.card-title.text-danger + h2').textContent = data.vencidos;
                document.querySelector('.card-title.text-primary + h2').textContent = data.total;
            })
            .catch(error => console.error('Error al actualizar el resumen:', error));
    };
    document.getElementById('btnClientes').onclick = function () {
        document.getElementById('seccionDashboard').style.display = 'none';
        document.getElementById('seccionClientes').style.display = 'block';
        document.getElementById('seccionUsuarios').style.display = 'none';
        this.classList.add('active');
        document.getElementById('btnDashboard').classList.remove('active');
        document.getElementById('btnUsuarios').classList.remove('active');
    };
    document.getElementById('btnUsuarios').onclick = function () {
        document.getElementById('seccionDashboard').style.display = 'none';
        document.getElementById('seccionClientes').style.display = 'none';
        document.getElementById('seccionUsuarios').style.display = 'block';
        this.classList.add('active');
        document.getElementById('btnDashboard').classList.remove('active');
        document.getElementById('btnClientes').classList.remove('active');
    };

    // Gráfico de Clientes por Mes
    const ctx = document.getElementById('graficoClientesMes').getContext('2d');
    const graficoClientesMes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labelsMeses); ?>,
            datasets: [{
                label: 'Clientes nuevos',
                data: <?php echo json_encode($clientesPorMes); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});

function limpiarFormularioClienteIframe() {
    // Busca el iframe de clientes
    var iframe = document.querySelector('#seccionClientes iframe');
    if (iframe && iframe.contentWindow) {
        // Ejecuta la función de limpieza dentro del iframe
        iframe.contentWindow.postMessage('limpiarFormularioCliente', '*');
    }
}

// Llama a la función cada vez que cambias de pantalla
document.getElementById('btnDashboard')?.addEventListener('click', limpiarFormularioClienteIframe);
document.getElementById('btnUsuarios')?.addEventListener('click', limpiarFormularioClienteIframe);
document.getElementById('btnClientes')?.addEventListener('click', limpiarFormularioClienteIframe);

let timer;
function resetTimer() {
    clearTimeout(timer);
    timer = setTimeout(function() {
        window.location.href = 'logout.php';
    }, 120000); // 2 minutes
}
['mousemove', 'keydown', 'mousedown', 'touchstart'].forEach(function(evt) {
    document.addEventListener(evt, resetTimer, false);
});
resetTimer();
</script>
</body>
</html>
