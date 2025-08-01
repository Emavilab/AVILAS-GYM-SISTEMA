<?php
session_start();
require 'config/db.php';
if (!isset($_SESSION['usuario'])) header('Location: index.php');

$mensaje = '';
$tipo_mensaje = 'success';

if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $tipo_mensualidad = $_POST['tipo_mensualidad'];
    $fecha_vencimiento_personalizada = $_POST['fecha_vencimiento_personalizada'];
    $hoy = date('Y-m-d');
    if ($nombre === '' || $telefono === '' || $tipo_mensualidad === '' || !preg_match('/^\d{7,15}$/', $telefono)) {
        header("Location: clientes.php?msg=error_agregar");
        exit;
    }
    if (!empty($fecha_vencimiento_personalizada)) {
        $vencimiento = $fecha_vencimiento_personalizada;
    } else {
        $dias = ($tipo_mensualidad === 'quincenal') ? 17 : 31;
        $vencimiento = date('Y-m-d', strtotime("+$dias days"));
    }
    if ($conn->query("INSERT INTO clientes (nombre, telefono, fecha_registro, fecha_vencimiento, estado, tipo_mensualidad)
                  VALUES ('$nombre', '$telefono', '$hoy', '$vencimiento', 'activo', '$tipo_mensualidad')")) {
        header("Location: clientes.php?msg=agregado");
        exit;
    } else {
        header("Location: clientes.php?msg=error_agregar");
        exit;
    }
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($conn->query("DELETE FROM clientes WHERE id = $id")) {
        header("Location: clientes.php?msg=eliminado");
        exit;
    } else {
        header("Location: clientes.php?msg=error_eliminar");
        exit;
    }
}

if (isset($_GET['renovar'])) {
    $id = $_GET['renovar'];
    $cliente = $conn->query("SELECT tipo_mensualidad, fecha_vencimiento FROM clientes WHERE id = $id")->fetch_assoc();
    $hoy = date('Y-m-d');
    if ($cliente['tipo_mensualidad'] === 'quincenal') {
        $vencimiento = date('Y-m-d', strtotime('+17 days'));
        $update = "UPDATE clientes SET fecha_entrada = '$hoy', fecha_vencimiento = '$vencimiento', estado = 'activo' WHERE id = $id";
    } elseif ($cliente['tipo_mensualidad'] === 'mensual') {
        $vencimiento = date('Y-m-d', strtotime('+31 days'));
        $update = "UPDATE clientes SET fecha_entrada = '$hoy', fecha_vencimiento = '$vencimiento', estado = 'activo' WHERE id = $id";
    } else {
        // Si es personalizado, solo actualiza estado y fecha_entrada, NO la fecha_vencimiento
        $update = "UPDATE clientes SET fecha_entrada = '$hoy', estado = 'activo' WHERE id = $id";
    }
    if ($conn->query($update)) {
        header("Location: clientes.php?msg=renovado");
        exit;
    } else {
        header("Location: clientes.php?msg=error_renovar");
        exit;
    }
}

if (isset($_POST['editar'])) {
    $id = $_POST['editar_id'];
    $nombre = $_POST['editar_nombre'];
    $telefono = $_POST['editar_telefono'];
    $tipo_mensualidad = $_POST['editar_tipo_mensualidad'];
    $fecha_vencimiento = $_POST['editar_fecha_vencimiento'];

    // Si no se selecciona fecha personalizada, calcula según tipo
    if (empty($fecha_vencimiento)) {
        $dias = ($tipo_mensualidad === 'quincenal') ? 17 : 31;
        $fecha_vencimiento = date('Y-m-d', strtotime("+$dias days"));
    }

    if ($conn->query("UPDATE clientes SET nombre='$nombre', telefono='$telefono', tipo_mensualidad='$tipo_mensualidad', fecha_vencimiento='$fecha_vencimiento' WHERE id=$id")) {
        header("Location: clientes.php?msg=editado");
        exit;
    } else {
        header("Location: clientes.php?msg=error_editar");
        exit;
    }
}

// Mensajes según parámetro en la URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'agregado':
            $mensaje = "Cliente agregado correctamente.";
            $tipo_mensaje = "success";
            break;
        case 'eliminado':
            $mensaje = "Cliente eliminado correctamente.";
            $tipo_mensaje = "success";
            break;
        case 'renovado':
            $mensaje = "Cliente renovado correctamente.";
            $tipo_mensaje = "success";
            break;
        case 'editado':
            $mensaje = "Cliente editado correctamente.";
            $tipo_mensaje = "success";
            break;
        case 'error_agregar':
            $mensaje = "Error al agregar cliente.";
            $tipo_mensaje = "danger";
            break;
        case 'error_eliminar':
            $mensaje = "Error al eliminar cliente.";
            $tipo_mensaje = "danger";
            break;
        case 'error_renovar':
            $mensaje = "Error al renovar cliente.";
            $tipo_mensaje = "danger";
            break;
        case 'error_editar':
            $mensaje = "Error al editar cliente.";
            $tipo_mensaje = "danger";
            break;
    }
}

$clientes = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - Avilas Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white text-center">
                    <h3>Gestión de Clientes</h3>
                </div>
                <div class="card-body">
                    <!-- Buscador rápido -->
                    <div class="mb-3">
                        <input type="text" id="buscador" class="form-control" placeholder="Buscar cliente por nombre...">
                    </div>
                    <form method="POST" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input name="nombre" placeholder="Nombre" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <input name="telefono" placeholder="Teléfono" class="form-control" required pattern="\d{7,15}" inputmode="numeric" title="Solo números, mínimo 7 dígitos">
                        </div>
                        <div class="col-md-3">
                            <select name="tipo_mensualidad" id="tipo_mensualidad" class="form-select" required>
                                <option value="" disabled selected>Tipo de mensualidad</option>
                                <option value="mensual">Mensual</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="personalizado">Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="fechaPersonalizadaDiv" style="display:none;">
                            <input type="date" name="fecha_vencimiento_personalizada" id="fecha_vencimiento_personalizada" class="form-control" placeholder="Fecha personalizada">
                        </div>
                        <div class="col-md-12 d-grid">
                            <button name="agregar" class="btn btn-success">Agregar</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center" id="tablaClientes">
                            <thead class="table-dark">
    <tr>
        <th>Nombre</th>
        <th>Teléfono</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Fecha de Registro</th>
        <th>Fecha de Entrada</th>
        <th>Vence</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody>
    <?php while ($c = $clientes->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $c['nombre']; ?></td>
            <td><?php echo $c['telefono']; ?></td>
            <td><?php echo ucfirst($c['tipo_mensualidad']); ?></td>
            <td>
                <?php if ($c['estado'] == 'activo'): ?>
                    <span class="badge bg-success">Activo</span>
                <?php else: ?>
                    <span class="badge bg-danger">Vencido</span>
                <?php endif; ?>
            </td>
            <td><?php echo $c['fecha_registro']; ?></td>
            <td><?php echo $c['fecha_entrada']; ?></td>
            <td><?php echo $c['fecha_vencimiento']; ?></td>
            <td>
                <button type="button" class="btn btn-sm btn-warning me-1"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEditar"
                    data-id="<?php echo $c['id']; ?>"
                    data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>"
                    data-telefono="<?php echo htmlspecialchars($c['telefono']); ?>"
                    data-tipo="<?php echo $c['tipo_mensualidad']; ?>"
                    data-vencimiento="<?php echo $c['fecha_vencimiento']; ?>">
                    Editar
                </button>
                <button type="button" class="btn btn-sm btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEliminar"
                    data-id="<?php echo $c['id']; ?>"
                    data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>">
                    Eliminar
                </button>
                <?php if ($c['estado'] == 'vencido'): ?>
                    <a href="?renovar=<?php echo $c['id']; ?>" class="btn btn-sm btn-success ms-1">Renovar</a>
                <?php else: ?>
                    <a href="#" class="btn btn-sm btn-success ms-1 disabled" tabindex="-1" aria-disabled="true">Renovar</a>
                <?php endif; ?>
                <?php
                    $fechaHoy = date('Y-m-d');
                    $fechaVencimiento = $c['fecha_vencimiento'];
                    $diasRestantes = (strtotime($fechaVencimiento) - strtotime($fechaHoy)) / 86400;
                ?>
                <?php if ($diasRestantes <= 2 && $diasRestantes >= 0): ?>
                    <?php
                        $mensajeWhatsapp = "Hola " . $c['nombre'] . ", tu mensualidad en Avilas Gym vence el " . date('d/m/Y', strtotime($fechaVencimiento)) . ". ¡No olvides renovarla!";
                        $telefono = preg_replace('/[^0-9]/', '', $c['telefono']); // Solo números
                        $urlWhatsapp = "https://wa.me/504$telefono?text=" . urlencode($mensajeWhatsapp);
                    ?>
                    <a href="<?php echo $urlWhatsapp; ?>" target="_blank" class="btn btn-sm btn-success ms-1 btn-whatsapp"
   onclick="this.style.display='none'">
    <i class="bi bi-whatsapp"></i> WhatsApp
</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php } ?>
</tbody>
                        </table>
            </div>
    </div>
</div>

<!-- Modal de validación -->
<div class="modal fade" id="modalMensaje" tabindex="-1" aria-labelledby="modalMensajeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-<?php echo $tipo_mensaje; ?> text-white">
        <h5 class="modal-title" id="modalMensajeLabel">
            <?php echo $tipo_mensaje == 'success' ? 'Éxito' : 'Error'; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php echo $mensaje; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalEliminarLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Seguro que deseas eliminar al cliente <span id="nombreCliente"></span>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<!-- Modal de edición de cliente -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="modalEditarLabel">Editar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="editar_id" id="editar_id">
        <div class="mb-3">
          <label for="editar_nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control" name="editar_nombre" id="editar_nombre" required>
        </div>
        <div class="mb-3">
          <label for="editar_telefono" class="form-label">Teléfono</label>
          <input type="text" class="form-control" name="editar_telefono" id="editar_telefono" required>
        </div>
        <div class="mb-3">
          <label for="editar_tipo_mensualidad" class="form-label">Tipo de mensualidad</label>
          <select class="form-select" name="editar_tipo_mensualidad" id="editar_tipo_mensualidad" required>
            <option value="mensual">Mensual</option>
            <option value="quincenal">Quincenal</option>
            <option value="personalizado">Personalizado</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="editar_fecha_vencimiento" class="form-label">Fecha de vencimiento personalizada</label>
          <input type="date" class="form-control" name="editar_fecha_vencimiento" id="editar_fecha_vencimiento">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" name="editar" class="btn btn-warning">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Buscador rápido por nombre
document.getElementById('buscador').addEventListener('keyup', function() {
    var filtro = this.value.toLowerCase();
    var filas = document.querySelectorAll('#tablaClientes tbody tr');
    filas.forEach(function(fila) {
        var nombre = fila.cells[0].textContent.toLowerCase();
        fila.style.display = nombre.includes(filtro) ? '' : 'none';
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var modalMensaje = document.getElementById('modalMensaje');
    if (modalMensaje) {
        var bsModal = bootstrap.Modal.getOrCreateInstance(modalMensaje);
        bsModal.show();
    }
    
    var modalEliminar = document.getElementById('modalEliminar');
    modalEliminar.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        document.getElementById('nombreCliente').textContent = nombre;
        document.getElementById('btnConfirmarEliminar').href = '?eliminar=' + id;
    });

    // Llenar modal de edición con los datos del cliente
    var modalEditar = document.getElementById('modalEditar');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        var telefono = button.getAttribute('data-telefono');
        var tipo = button.getAttribute('data-tipo');
        var fecha_vencimiento = button.getAttribute('data-vencimiento');
        document.getElementById('editar_id').value = id;
        document.getElementById('editar_nombre').value = nombre;
        document.getElementById('editar_telefono').value = telefono;
        document.getElementById('editar_tipo_mensualidad').value = tipo;
        document.getElementById('editar_fecha_vencimiento').value = fecha_vencimiento;
    });
});

</script>

<script>
document.getElementById('tipo_mensualidad').addEventListener('change', function() {
    var divFecha = document.getElementById('fechaPersonalizadaDiv');
    if (this.value === 'personalizado') {
        divFecha.style.display = '';
        document.getElementById('fecha_vencimiento_personalizada').required = true;
    } else {
        divFecha.style.display = 'none';
        document.getElementById('fecha_vencimiento_personalizada').required = false;
        document.getElementById('fecha_vencimiento_personalizada').value = '';
    }
});
</script>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var nombre = document.querySelector('input[name="nombre"]').value.trim();
    var telefono = document.querySelector('input[name="telefono"]').value.trim();
    var tipo = document.querySelector('select[name="tipo_mensualidad"]').value;
    if (!nombre || !telefono || !tipo) {
        alert('Todos los campos son obligatorios.');
        e.preventDefault();
        return;
    }
    if (!/^\d{7,15}$/.test(telefono)) {
        alert('El teléfono debe contener solo números (mínimo 7 dígitos).');
        e.preventDefault();
        return;
    }
});
</script>

<script>
function limpiarFormularioCliente() {
    var form = document.querySelector('form');
    if (form) {
        form.reset();
        // Oculta el campo de fecha personalizada si está visible
        var fechaDiv = document.getElementById('fechaPersonalizadaDiv');
        if (fechaDiv) fechaDiv.style.display = 'none';
    }
}

// Si tienes botones de navegación con estos IDs, llama a la función al hacer clic
document.getElementById('btnDashboard')?.addEventListener('click', limpiarFormularioCliente);
document.getElementById('btnUsuarios')?.addEventListener('click', limpiarFormularioCliente);
document.getElementById('btnClientes')?.addEventListener('click', limpiarFormularioCliente);

// Escuchar el evento de mensaje para limpiar el formulario
window.addEventListener('message', function(event) {
    if (event.data === 'limpiarFormularioCliente') {
        limpiarFormularioCliente();
    }
});
</script>
</body>
</html>