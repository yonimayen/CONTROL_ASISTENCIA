<?php
// mantenimiento/jornadas.php
require_once '../includes/auth.php';
require_once '../config/db.php';

$mensaje = '';
$tipo_mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$codigo_editar = $_GET['codigo'] ?? '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $hora_entrada = $_POST['hora_entrada'] ?? '';
    $hora_salida = $_POST['hora_salida'] ?? '';
    $accion_post = $_POST['accion'] ?? '';

    try {
        if ($accion_post === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO jornadas (codigo, nombre, hora_entrada, hora_salida) VALUES (?, ?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $hora_entrada, $hora_salida]);
            $mensaje = "Jornada creada exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar';
        } elseif ($accion_post === 'editar') {
            $codigo_original = $_POST['codigo_original'];
            $stmt = $pdo->prepare("UPDATE jornadas SET codigo = ?, nombre = ?, hora_entrada = ?, hora_salida = ? WHERE codigo = ?");
            $stmt->execute([$codigo, $nombre, $hora_entrada, $hora_salida, $codigo_original]);
            $mensaje = "Jornada actualizada exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar';
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar
if ($accion === 'eliminar' && !empty($_GET['codigo'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM jornadas WHERE codigo = ?");
        $stmt->execute([$_GET['codigo']]);
        $mensaje = "Jornada eliminada.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "No se puede eliminar la jornada porque está en uso.";
        $tipo_mensaje = "danger";
    }
    $accion = 'listar';
}

include '../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Jornadas Laborales</h2>
        <?php if ($accion === 'listar'): ?>
            <a href="jornadas.php?accion=crear" class="btn btn-primary">Nueva Jornada</a>
        <?php else: ?>
            <a href="jornadas.php" class="btn btn-secondary">Volver a la lista</a>
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
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM jornadas");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['hora_entrada']); ?></td>
                            <td><?php echo htmlspecialchars($row['hora_salida']); ?></td>
                            <td class="action-links">
                                <a href="jornadas.php?accion=editar&codigo=<?php echo urlencode($row['codigo']); ?>" class="edit-link">Editar</a>
                                <a href="jornadas.php?accion=eliminar&codigo=<?php echo urlencode($row['codigo']); ?>" class="delete-link" onclick="return confirm('¿Está seguro de eliminar esta jornada?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($accion === 'crear' || $accion === 'editar'): 
        $j_codigo = ''; $j_nombre = ''; $j_entrada = ''; $j_salida = '';
        if ($accion === 'editar') {
            $stmt = $pdo->prepare("SELECT * FROM jornadas WHERE codigo = ?");
            $stmt->execute([$codigo_editar]);
            $jor = $stmt->fetch();
            if ($jor) {
                $j_codigo = $jor['codigo'];
                $j_nombre = $jor['nombre'];
                $j_entrada = $jor['hora_entrada'];
                $j_salida = $jor['hora_hora_salida'] ?? $jor['hora_salida']; // corrección posible
            }
        }
    ?>
        <form method="POST" action="jornadas.php" style="max-width: 500px;">
            <input type="hidden" name="accion" value="<?php echo $accion; ?>">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="codigo_original" value="<?php echo htmlspecialchars($j_codigo); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Código de la Jornada</label>
                <input type="text" name="codigo" value="<?php echo htmlspecialchars($j_codigo); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nombre de la Jornada</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($j_nombre); ?>" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Hora de Entrada</label>
                    <input type="time" name="hora_entrada" value="<?php echo htmlspecialchars($j_entrada); ?>" required>
                </div>
                <div class="form-group">
                    <label>Hora de Salida</label>
                    <input type="time" name="hora_salida" value="<?php echo htmlspecialchars($j_salida); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
