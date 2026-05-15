<?php
// index.php
require_once 'includes/auth.php'; // Requiere login
require_once 'config/db.php';

$mensaje = '';
$tipo_mensaje = '';

$cod_empleado = $_SESSION['usuario_id'];
$nombre_empleado = $_SESSION['usuario_nombre'];

// Solo los empleados pueden marcar asistencia desde aquí. Los admins deberían ir al dashboard.
if ($_SESSION['rol'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_marca'])) {
    $tipo_marca = $_POST['tipo_marca'];
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO marcas_asistencia (cod_empleado, fecha, hora, tipo_marca) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cod_empleado, $fecha_actual, $hora_actual, $tipo_marca]);
        
        $mensaje = "¡Marca de $tipo_marca registrada exitosamente a las $hora_actual!";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al registrar la marca: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener historial personal del empleado (últimos 10 registros)
$stmt = $pdo->prepare("SELECT fecha, hora, tipo_marca FROM marcas_asistencia WHERE cod_empleado = ? ORDER BY fecha DESC, hora DESC LIMIT 10");
$stmt->execute([$cod_empleado]);
$historial = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto; text-align: center;">
    <h2>Bienvenido, <?php echo htmlspecialchars($nombre_empleado); ?></h2>
    <p class="text-muted" style="margin-bottom: 2rem;">Portal Personal de Asistencia</p>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div style="background-color: #f1f5f9; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--primary-color);" id="reloj">00:00:00</h1>
        <p style="font-size: 1.2rem; color: var(--secondary-color);"><?php echo date('d / m / Y'); ?></p>
    </div>

    <form method="POST" action="index.php">
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
            <button type="submit" name="tipo_marca" value="Entrada" class="btn btn-success" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                🟢 Registrar Entrada
            </button>
            <button type="submit" name="tipo_marca" value="Salida" class="btn btn-danger" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                🔴 Registrar Salida
            </button>
        </div>
    </form>
</div>

<div class="card" style="max-width: 600px; margin: 2rem auto;">
    <h3>Tus Últimas Marcaciones</h3>
    <?php if (count($historial) > 0): ?>
        <table style="width: 100%; margin-top: 1rem; text-align: center;">
            <thead>
                <tr>
                    <th style="text-align: center;">Fecha</th>
                    <th style="text-align: center;">Hora</th>
                    <th style="text-align: center;">Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $row): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($row['hora']); ?></td>
                        <td>
                            <strong style="color: <?php echo $row['tipo_marca'] === 'Entrada' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                <?php echo htmlspecialchars($row['tipo_marca']); ?>
                            </strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); margin-top: 1rem;">No tienes registros recientes.</p>
    <?php endif; ?>
</div>

<script>
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        document.getElementById('reloj').textContent = `${horas}:${minutos}:${segundos}`;
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();
</script>

<?php include 'includes/footer.php'; ?>
