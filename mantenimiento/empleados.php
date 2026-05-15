<?php
// mantenimiento/empleados.php
require_once '../includes/auth.php';
require_once '../config/db.php';

$mensaje = '';
$tipo_mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$codigo_editar = $_GET['codigo'] ?? '';

// Obtener catálogos para los select
$departamentos = $pdo->query("SELECT * FROM departamentos")->fetchAll();
$jornadas = $pdo->query("SELECT * FROM jornadas")->fetchAll();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $cod_jornada = empty($_POST['cod_jornada']) ? null : $_POST['cod_jornada'];
    $cod_departamento = empty($_POST['cod_departamento']) ? null : $_POST['cod_departamento'];
    $password = $_POST['password'] ?? '12345'; // Contraseña por defecto si se omite
    $accion_post = $_POST['accion'] ?? '';

    try {
        if ($accion_post === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO empleados (codigo, nombre, password, cod_jornada, cod_departamento) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $password, $cod_jornada, $cod_departamento]);
            $mensaje = "Empleado creado exitosamente.";
            $tipo_mensaje = "success";
            $accion = 'listar';
        } elseif ($accion_post === 'editar') {
            $codigo_original = $_POST['codigo_original'];
            $stmt = $pdo->prepare("UPDATE empleados SET codigo = ?, nombre = ?, password = ?, cod_jornada = ?, cod_departamento = ? WHERE codigo = ?");
            $stmt->execute([$codigo, $nombre, $password, $cod_jornada, $cod_departamento, $codigo_original]);
            $mensaje = "Empleado actualizado exitosamente.";
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
        $stmt = $pdo->prepare("DELETE FROM empleados WHERE codigo = ?");
        $stmt->execute([$_GET['codigo']]);
        $mensaje = "Empleado eliminado.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "No se puede eliminar el empleado porque tiene registros asociados.";
        $tipo_mensaje = "danger";
    }
    $accion = 'listar';
}

include '../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Empleados</h2>
        <?php if ($accion === 'listar'): ?>
            <a href="empleados.php?accion=crear" class="btn btn-primary">Nuevo Empleado</a>
        <?php else: ?>
            <a href="empleados.php" class="btn btn-secondary">Volver a la lista</a>
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
                        <th>Jornada</th>
                        <th>Departamento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT e.*, j.nombre as jornada_nombre, d.nombre as depto_nombre 
                              FROM empleados e 
                              LEFT JOIN jornadas j ON e.cod_jornada = j.codigo
                              LEFT JOIN departamentos d ON e.cod_departamento = d.codigo";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['jornada_nombre'] ?? 'No asignada'); ?></td>
                            <td><?php echo htmlspecialchars($row['depto_nombre'] ?? 'No asignado'); ?></td>
                            <td class="action-links">
                                <a href="empleados.php?accion=editar&codigo=<?php echo urlencode($row['codigo']); ?>" class="edit-link">Editar</a>
                                <a href="empleados.php?accion=eliminar&codigo=<?php echo urlencode($row['codigo']); ?>" class="delete-link" onclick="return confirm('¿Está seguro de eliminar este empleado?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($accion === 'crear' || $accion === 'editar'): 
        $e_codigo = ''; $e_nombre = ''; $e_jornada = ''; $e_depto = ''; $e_password = '12345';
        if ($accion === 'editar') {
            $stmt = $pdo->prepare("SELECT * FROM empleados WHERE codigo = ?");
            $stmt->execute([$codigo_editar]);
            $emp = $stmt->fetch();
            if ($emp) {
                $e_codigo = $emp['codigo'];
                $e_nombre = $emp['nombre'];
                $e_password = $emp['password'];
                $e_jornada = $emp['cod_jornada'];
                $e_depto = $emp['cod_departamento'];
            }
        }
    ?>
        <form method="POST" action="empleados.php" style="max-width: 600px;">
            <input type="hidden" name="accion" value="<?php echo $accion; ?>">
            <?php if ($accion === 'editar'): ?>
                <input type="hidden" name="codigo_original" value="<?php echo htmlspecialchars($e_codigo); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Código de Empleado (Login)</label>
                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($e_codigo); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña de Acceso</label>
                    <input type="text" name="password" value="<?php echo htmlspecialchars($e_password); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($e_nombre); ?>" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Jornada Laboral</label>
                    <select name="cod_jornada">
                        <option value="">Seleccione una jornada...</option>
                        <?php foreach($jornadas as $j): ?>
                            <option value="<?php echo $j['codigo']; ?>" <?php echo ($e_jornada == $j['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($j['nombre'] . ' (' . $j['hora_entrada'] . ' - ' . $j['hora_salida'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Departamento</label>
                    <select name="cod_departamento">
                        <option value="">Seleccione un departamento...</option>
                        <?php foreach($departamentos as $d): ?>
                            <option value="<?php echo $d['codigo']; ?>" <?php echo ($e_depto == $d['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
