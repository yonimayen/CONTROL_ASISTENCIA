<?php
// reportes.php
require_once 'includes/auth.php';
require_once 'config/db.php';

$empleados = $pdo->query("SELECT codigo, nombre FROM empleados ORDER BY nombre")->fetchAll();

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$cod_empleado = $_GET['cod_empleado'] ?? '';

$reporte_generado = false;
$datos_empleado = null;
$filas_reporte = [];
$totales = ['tarde' => 0, 'temprano' => 0];

if (!empty($cod_empleado)) {
    $reporte_generado = true;
    
    // Obtener datos del empleado y su jornada
    $stmt = $pdo->prepare("
        SELECT e.codigo, e.nombre, 
               COALESCE(d.codigo, 'N/A') as d_codigo, COALESCE(d.nombre, 'Sin Asignar') as d_nombre, 
               COALESCE(j.nombre, 'Sin Asignar') as j_nombre, j.hora_entrada, j.hora_salida
        FROM empleados e
        LEFT JOIN departamentos d ON e.cod_departamento = d.codigo
        LEFT JOIN jornadas j ON e.cod_jornada = j.codigo
        WHERE e.codigo = ?
    ");
    $stmt->execute([$cod_empleado]);
    $datos_empleado = $stmt->fetch();

    if ($datos_empleado) {
        // Obtener marcas de asistencia del rango
        $stmt = $pdo->prepare("SELECT fecha, hora, tipo_marca FROM marcas_asistencia WHERE cod_empleado = ? AND fecha BETWEEN ? AND ? ORDER BY fecha, hora");
        $stmt->execute([$cod_empleado, $fecha_inicio, $fecha_fin]);
        $marcas_raw = $stmt->fetchAll();
        
        $marcas_por_fecha = [];
        foreach ($marcas_raw as $m) {
            $fecha = $m['fecha'];
            if (!isset($marcas_por_fecha[$fecha])) {
                $marcas_por_fecha[$fecha] = ['Entrada' => null, 'Salida' => null];
            }
            if ($m['tipo_marca'] === 'Entrada' && $marcas_por_fecha[$fecha]['Entrada'] === null) {
                $marcas_por_fecha[$fecha]['Entrada'] = $m['hora'];
            } elseif ($m['tipo_marca'] === 'Salida') {
                $marcas_por_fecha[$fecha]['Salida'] = $m['hora'];
            }
        }

        // Obtener permisos del rango
        $stmt = $pdo->prepare("SELECT fecha, motivo, tipo FROM permisos WHERE cod_empleado = ? AND fecha BETWEEN ? AND ?");
        $stmt->execute([$cod_empleado, $fecha_inicio, $fecha_fin]);
        $permisos_raw = $stmt->fetchAll();
        $permisos_por_fecha = [];
        foreach ($permisos_raw as $p) {
            $permisos_por_fecha[$p['fecha']] = $p;
        }

        // Variables de iteración y cálculo
        $current_time = strtotime($fecha_inicio);
        $end_time = strtotime($fecha_fin);
        
        $j_entrada = $datos_empleado['hora_entrada'] ?? null;
        $j_salida = $datos_empleado['hora_salida'] ?? null;

        // Generar filas día a día
        while ($current_time <= $end_time) {
            $fecha_actual = date('Y-m-d', $current_time);
            
            $entrada_real = $marcas_por_fecha[$fecha_actual]['Entrada'] ?? '*';
            $salida_real = $marcas_por_fecha[$fecha_actual]['Salida'] ?? '*';
            
            $minutos_tarde = 0;
            $minutos_temprano = 0;
            $horas_trabajadas = '*';
            $observaciones = [];

            // Identificar permisos del día
            if (isset($permisos_por_fecha[$fecha_actual])) {
                $observaciones[] = $permisos_por_fecha[$fecha_actual]['motivo'];
            }

            // Identificar olvidos de marcación
            if ($entrada_real === '*' || $salida_real === '*') {
                // Solo si no hay un permiso justificado de ausencia completa
                if (!isset($permisos_por_fecha[$fecha_actual]) || $permisos_por_fecha[$fecha_actual]['tipo'] !== 'Ausencia') {
                    // Evitar poner "Olvido marcar" en fines de semana (opcional, por ahora lo ponemos siempre que falte marca)
                    $observaciones[] = "Olvido marcar";
                }
            }

            // Hacer cálculos matemáticos si el empleado tiene jornada asignada y marcas válidas
            if ($j_entrada && $j_salida) {
                if ($entrada_real !== '*') {
                    $t_entrada_real = strtotime($fecha_actual . ' ' . $entrada_real);
                    $t_j_entrada = strtotime($fecha_actual . ' ' . $j_entrada);
                    if ($t_entrada_real > $t_j_entrada) {
                        $minutos_tarde = floor(($t_entrada_real - $t_j_entrada) / 60);
                    }
                }
                
                if ($salida_real !== '*') {
                    $t_salida_real = strtotime($fecha_actual . ' ' . $salida_real);
                    $t_j_salida = strtotime($fecha_actual . ' ' . $j_salida);
                    if ($t_salida_real < $t_j_salida) {
                        $minutos_temprano = floor(($t_j_salida - $t_salida_real) / 60);
                    }
                }
                
                if ($entrada_real !== '*' && $salida_real !== '*') {
                    $diff = $t_salida_real - $t_entrada_real;
                    if ($diff > 0) {
                        $h = floor($diff / 3600);
                        $m = floor(($diff % 3600) / 60);
                        $horas_trabajadas = sprintf("%d:%02d", $h, $m);
                    }
                }
            }

            $totales['tarde'] += $minutos_tarde;
            $totales['temprano'] += $minutos_temprano;

            $filas_reporte[] = [
                'fecha' => date('d/m/Y', $current_time),
                'entrada' => $entrada_real !== '*' ? date('H:i', strtotime($entrada_real)) : '*',
                'salida' => $salida_real !== '*' ? date('H:i', strtotime($salida_real)) : '*',
                'tarde' => $minutos_tarde,
                'temprano' => $minutos_temprano,
                'horas' => $horas_trabajadas,
                'obs' => implode(' / ', $observaciones)
            ];

            $current_time = strtotime('+1 day', $current_time);
        }
    } else {
        $reporte_generado = false; // Empleado no existe
    }
}

include 'includes/header.php';
?>

<div class="card no-print" style="margin-bottom: 2rem;">
    <h2>Reporte Detallado de Entradas y Salidas</h2>
    <p class="text-muted">Genera un análisis completo de horas y tardanzas por empleado.</p>
    
    <form method="GET" action="reportes.php" style="margin-top: 1.5rem;">
        <div class="form-grid">
            <div class="form-group">
                <label>Empleado Obligatorio</label>
                <select name="cod_empleado" required>
                    <option value="">-- Seleccione un empleado --</option>
                    <?php foreach($empleados as $e): ?>
                        <option value="<?php echo htmlspecialchars($e['codigo']); ?>" <?php echo ($cod_empleado === $e['codigo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['codigo'] . ' - ' . $e['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
            </div>
            <div class="form-group">
                <label>Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
            </div>
        </div>
        <div style="text-align: right; margin-top: 1rem;">
            <button type="submit" class="btn btn-primary">Generar Reporte Detallado</button>
            <?php if ($reporte_generado): ?>
                <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir PDF</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($reporte_generado && $datos_empleado): ?>
<div class="card" id="area-impresion" style="font-family: Arial, sans-serif; padding: 2rem;">
    
    <div style="border: 1px solid #000; padding: 20px;">
        <h3 style="text-align: center; margin-bottom: 20px; font-weight: normal;">Reporte de Entradas y Salidas del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></h3>
        
        <table style="width: 100%; border: none; margin-bottom: 20px; font-size: 15px;">
            <tr>
                <td style="width: 120px;">Empleado:</td>
                <td><?php echo htmlspecialchars($datos_empleado['codigo'] . ' - ' . $datos_empleado['nombre']); ?></td>
            </tr>
            <tr>
                <td>Departamento:</td>
                <td><?php echo htmlspecialchars($datos_empleado['d_codigo'] . ' - ' . $datos_empleado['d_nombre']); ?></td>
            </tr>
        </table>
        
        <p style="margin-bottom: 5px; font-size: 15px;">Jornada: <?php echo htmlspecialchars($datos_empleado['j_nombre']); ?> de <?php echo $datos_empleado['hora_entrada'] ? substr($datos_empleado['hora_entrada'], 0, 5) : '--:--'; ?> a <?php echo $datos_empleado['hora_salida'] ? substr($datos_empleado['hora_salida'], 0, 5) : '--:--'; ?></p>
        
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; text-align: center; font-size: 14px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Fecha</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Entrada</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Salida</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Entrada<br>Tarde<br>minutos</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Salida<br>Temprano<br>minutos</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Horas<br>Trabajadas</th>
                    <th style="border: 1px solid #000; padding: 8px; font-weight: normal;">Observaciones<br>/Permisos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas_reporte as $fila): ?>
                    <tr>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['fecha']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['entrada']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['salida']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['tarde']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['temprano']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px;"><?php echo $fila['horas']; ?></td>
                        <td style="border: 1px solid #000; padding: 5px; font-size: 12px;"><?php echo htmlspecialchars($fila['obs']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="border: 1px solid #000; padding: 5px; text-align: right;">Totales</th>
                    <th style="border: 1px solid #000; padding: 5px; font-weight: normal;"><?php echo $totales['tarde']; ?></th>
                    <th style="border: 1px solid #000; padding: 5px; font-weight: normal;"><?php echo $totales['temprano']; ?></th>
                    <th style="border: 1px solid #000; padding: 5px;"></th>
                    <th style="border: 1px solid #000; padding: 5px;"></th>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
<?php endif; ?>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #area-impresion, #area-impresion * {
        visibility: visible;
    }
    #area-impresion {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .no-print {
        display: none !important;
    }
    /* Elimina el shadow y borders del card principal para que parezca hoja limpia */
    .card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
