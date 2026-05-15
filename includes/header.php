<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['usuario_id']);
$is_subdir = strpos($_SERVER['PHP_SELF'], '/mantenimiento/') !== false;
$prefix = $is_subdir ? '../' : './';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Asistencia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="<?php echo $prefix; ?>index.php" class="navbar-brand">⏱️ Control Asistencia</a>
    <div class="nav-links">
        <a href="<?php echo $prefix; ?>index.php">Inicio (Marcación)</a>
        <?php if ($is_logged_in): ?>
            <a href="<?php echo $prefix; ?>dashboard.php">Panel de Administración</a>
            <a href="<?php echo $prefix; ?>logout.php" style="color: var(--danger); font-weight: 600;">Cerrar Sesión</a>
        <?php else: ?>
            <a href="<?php echo $prefix; ?>login.php" style="color: var(--primary-color); font-weight: 600;">Login Admin</a>
        <?php endif; ?>
    </div>
</nav>

<main class="container">
