<?php
// login.php
session_start();
require_once 'config/db.php';

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        
        // 1. Intentar como ADMINISTRADOR (tabla usuarios)
        $stmtAdmin = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmtAdmin->execute([$username]);
        $admin = $stmtAdmin->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['usuario_id'] = $admin['id'];
            $_SESSION['usuario_nombre'] = $admin['nombre'];
            $_SESSION['rol'] = 'admin';
            header("Location: dashboard.php");
            exit();
        }

        // 2. Intentar como EMPLEADO (tabla empleados)
        $stmtEmp = $pdo->prepare("SELECT * FROM empleados WHERE codigo = ?");
        $stmtEmp->execute([$username]);
        $empleado = $stmtEmp->fetch();

        // Nota: para empleados, temporalmente comparamos la contraseña en texto plano si viene de la DB antigua
        // o si queremos usar password_verify lo ideal es encriptarlas. Para este ejemplo, permitiremos texto plano para empleados
        if ($empleado && $empleado['password'] === $password) {
            $_SESSION['usuario_id'] = $empleado['codigo'];
            $_SESSION['usuario_nombre'] = $empleado['nombre'];
            $_SESSION['rol'] = 'empleado';
            header("Location: index.php");
            exit();
        }

        $error = "Usuario/Código o contraseña incorrectos.";
        
    } else {
        $error = "Por favor, ingresa el usuario y la contraseña.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="card" style="max-width: 400px; margin: 2rem auto;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--primary-color);">Iniciar Sesión</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger" style="text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Usuario (Admin) o Código (Empleado)</label>
            <input type="text" name="username" placeholder="Ej: admin o 001" required autofocus>
        </div>
        
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Entrar</button>
    </form>
    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        Administradores: usen su nombre de usuario.<br>
        Empleados: usen su código de empleado.
    </p>
</div>

<?php include 'includes/footer.php'; ?>
