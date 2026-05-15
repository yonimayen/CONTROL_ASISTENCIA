<?php
// mantenimiento/permisos.php
require_once '../includes/auth.php';
require_once '../config/db.php';

$mensaje = '';
$tipo_mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$id_editar = $_GET['id'] ?? '';

// Obtener empleados para el select
$empleados = $pdo->query("SELECT codigo, nombre FROM empleados ORDER BY nombre")->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_empleado = $_POST['cod_empleado'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $accion_post = $_POST['accion'] ?? '';

    try {
        if ($accion_post === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO permisos (cod_empleado, fecha, motivo, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$cod_empleado, $fecha, $motivo, $tipo]);
            $mensaje = "Permiso registrado exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar';
        } elseif ($accion_post === 'editar') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE permisos SET cod_empleado = ?, fecha = ?, motivo = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$cod_empleado, $fecha, $motivo, $tipo, $id]);
            $mensaje = "Permiso actualizado exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar';
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar
if ($accion === 'eliminar' && !empty($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM permisos WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $mensaje = "Permiso eliminado.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
    $accion = 'listar';
}

include '../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Permisos y Ausencias</h2>
        <?php if ($accion === 'listar'): ?>
            <a href="permisos.php?accion=crear" class="btn btn-primary">Nuevo Registro</a>
        <?php else: ?>
            <a href="permisos.php" class="btn btn-secondary">Volver a la lista</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if ($accion === 'listar'): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, e.nombre as empleado_nombre 
                              FROM permisos p 
                              JOIN empleados e ON p.cod_empleado = e.codigo
                              ORDER BY p.fecha DESC";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['empleado_nombre'] . ' (' . $row['cod_empleado'] . ')'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                            <td>
                                <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; 
                                    background-color: <?php echo $row['tipo'] == 'Ausencia' ? '#fee2e2; color: #991b1b;' : ($row['tipo'] == 'Llegada Tarde' ? '#fef3c7; color: #92400e;' : '#e0f2fe; color: #075985;'); ?>">
                                    <?php echo htmlspecialchars($row['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                            <td class="action-links">
                                <a href="permisos.php?accion=editar&id=<?php echo $row['id']; ?>" class="edit-link">Editar</a>
                                <a href="permisos.php?accion=eliminar&id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('¿Está seguro de eliminar este registro?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($accion === 'crear' || $accion === 'editar'): 
        $p_empleado = ''; $p_fecha = date('Y-m-d'); $p_motivo = ''; $p_tipo = 'Ausencia';
        if ($accion === 'editar') {
            $stmt = $pdo->prepare("SELECT * FROM permisos WHERE id = ?");
            $stmt->execute([$id_editar]);
            $permiso = $stmt->fetch();
            if ($permiso) {
                $p_empleado = $permiso['cod_empleado'];
                $p_fecha = $permiso['fecha'];
                $p_motivo = $permiso['motivo'];
                $p_tipo = $permiso['tipo'];
            }
        }
    ?>
        <form method="POST" action="permisos.php" style="max-width: 600px;">
            <input type="hidden" name="accion" value="<?php echo $accion; ?>">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="id" value="<?php echo $id_editar; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Empleado</label>
                <select name="cod_empleado" required>
                    <option value="">Seleccione un empleado...</option>
                    <?php foreach($empleados as $e): ?>
                        <option value="<?php echo $e['codigo']; ?>" <?php echo ($p_empleado == $e['codigo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['nombre'] . ' (' . $e['codigo'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($p_fecha); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Registro</label>
                    <select name="tipo" required>
                        <option value="Ausencia" <?php echo ($p_tipo == 'Ausencia') ? 'selected' : ''; ?>>Ausencia Completa</option>
                        <option value="Llegada Tarde" <?php echo ($p_tipo == 'Llegada Tarde') ? 'selected' : ''; ?>>Llegada Tarde</option>
                        <option value="Salida Temprano" <?php echo ($p_tipo == 'Salida Temprano') ? 'selected' : ''; ?>>Salida Temprano</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Motivo</label>
                <input type="text" name="motivo" value="<?php echo htmlspecialchars($p_motivo); ?>" required placeholder="Ej: Cita médica, Problemas de transporte...">
            </div>

            <button type="submit" class="btn btn-primary">Guardar Registro</button>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
