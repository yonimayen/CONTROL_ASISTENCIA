<?php
// dashboard.php
require_once 'includes/auth.php';
include 'includes/header.php'; 
?>

<div class="card">
    <h2>Panel de Administración</h2>
    <p>Seleccione un módulo para administrar el sistema.</p>
</div>

<div class="form-grid">
    <div class="card" style="text-align: center;">
        <h3 style="color: var(--primary-color); font-size: 2rem;">🏢</h3>
        <h3>Departamentos</h3>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Gestionar los departamentos de la empresa.</p>
        <a href="mantenimiento/departamentos.php" class="btn btn-primary">Administrar</a>
    </div>

    <div class="card" style="text-align: center;">
        <h3 style="color: var(--primary-color); font-size: 2rem;">⏰</h3>
        <h3>Jornadas Laborales</h3>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Configurar los diferentes horarios de trabajo.</p>
        <a href="mantenimiento/jornadas.php" class="btn btn-primary">Administrar</a>
    </div>

    <div class="card" style="text-align: center;">
        <h3 style="color: var(--primary-color); font-size: 2rem;">👥</h3>
        <h3>Empleados</h3>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Administrar el personal, asignar jornada y departamento.</p>
        <a href="mantenimiento/empleados.php" class="btn btn-primary">Administrar</a>
    </div>

    <div class="card" style="text-align: center;">
        <h3 style="color: var(--primary-color); font-size: 2rem;">📝</h3>
        <h3>Permisos y Ausencias</h3>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Registrar llegadas tardías, salidas tempranas o faltas.</p>
        <a href="mantenimiento/permisos.php" class="btn btn-primary">Administrar</a>
    </div>

    <div class="card" style="text-align: center;">
        <h3 style="color: var(--primary-color); font-size: 2rem;">📊</h3>
        <h3>Reportes</h3>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Consultar y exportar el historial de marcaciones.</p>
        <a href="reportes.php" class="btn btn-primary">Ver Reportes</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
