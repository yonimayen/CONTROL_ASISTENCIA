<?php
// mantenimiento/departamentos.php
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
    $accion_post = $_POST['accion'] ?? '';

    try {
        if ($accion_post === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO departamentos (codigo, nombre) VALUES (?, ?)");
            $stmt->execute([$codigo, $nombre]);
            $mensaje = "Departamento creado exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar'; // Volver a la lista
        } elseif ($accion_post === 'editar') {
            $codigo_original = $_POST['codigo_original'];
            $stmt = $pdo->prepare("UPDATE departamentos SET codigo = ?, nombre = ? WHERE codigo = ?");
            $stmt->execute([$codigo, $nombre, $codigo_original]);
            $mensaje = "Departamento actualizado exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar'; // Volver a la lista
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar
if ($accion === 'eliminar' && !empty($_GET['codigo'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM departamentos WHERE codigo = ?");
        $stmt->execute([$_GET['codigo']]);
        $mensaje = "Departamento eliminado.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "No se puede eliminar el departamento porque está en uso.";
        $tipo_mensaje = "danger";
    }
    $accion = 'listar';
}

include '../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Departamentos</h2>
        <?php if ($accion === 'listar'): ?>
            <a href="departamentos.php?accion=crear" class="btn btn-primary">Nuevo Departamento</a>
        <?php else: ?>
            <a href="departamentos.php" class="btn btn-secondary">Volver a la lista</a>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM departamentos");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td class="action-links">
                                <a href="departamentos.php?accion=editar&codigo=<?php echo urlencode($row['codigo']); ?>" class="edit-link">Editar</a>
                                <a href="departamentos.php?accion=eliminar&codigo=<?php echo urlencode($row['codigo']); ?>" class="delete-link" onclick="return confirm('¿Está seguro de eliminar este departamento?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($accion === 'crear' || $accion === 'editar'): 
        $d_codigo = '';
        $d_nombre = '';
        if ($accion === 'editar') {
            $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE codigo = ?");
            $stmt->execute([$codigo_editar]);
            $depto = $stmt->fetch();
            if ($depto) {
                $d_codigo = $depto['codigo'];
                $d_nombre = $depto['nombre'];
            }
        }
    ?>
        <form method="POST" action="departamentos.php" style="max-width: 500px;">
            <input type="hidden" name="accion" value="<?php echo $accion; ?>">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="codigo_original" value="<?php echo htmlspecialchars($d_codigo); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Código del Departamento</label>
                <input type="text" name="codigo" value="<?php echo htmlspecialchars($d_codigo); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nombre del Departamento</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($d_nombre); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
