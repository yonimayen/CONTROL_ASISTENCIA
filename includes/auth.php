<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_subdir = strpos($_SERVER['PHP_SELF'], '/mantenimiento/') !== false;
$prefix = $is_subdir ? '../' : './';

// Si no hay sesión iniciada, redirigimos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . $prefix . "login.php");
    exit();
}

// Proteger rutas administrativas
$current_page = basename($_SERVER['PHP_SELF']);
$admin_pages = ['dashboard.php', 'reportes.php', 'departamentos.php', 'empleados.php', 'jornadas.php', 'permisos.php'];

if (in_array($current_page, $admin_pages) && $_SESSION['rol'] !== 'admin') {
    // Si un empleado intenta entrar a admin, expulsarlo a su portal
    header("Location: " . $prefix . "index.php");
    exit();
}
?>
