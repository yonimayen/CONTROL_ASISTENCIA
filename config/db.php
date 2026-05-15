<?php
// config/db.php
date_default_timezone_set('America/Mexico_City'); // Configurar zona horaria (UTC-6)

$db_file = __DIR__ . '/../database.sqlite';
$is_new = !file_exists($db_file);

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Crear tablas
    $schema = <<<SQL
CREATE TABLE IF NOT EXISTS departamentos (
    codigo TEXT PRIMARY KEY,
    nombre TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS jornadas (
    codigo TEXT PRIMARY KEY,
    nombre TEXT NOT NULL,
    hora_entrada TEXT NOT NULL,
    hora_salida TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS empleados (
    codigo TEXT PRIMARY KEY,
    nombre TEXT NOT NULL,
    password TEXT NOT NULL DEFAULT '12345',
    cod_jornada TEXT,
    cod_departamento TEXT,
    FOREIGN KEY (cod_jornada) REFERENCES jornadas(codigo) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (cod_departamento) REFERENCES departamentos(codigo) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS permisos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cod_empleado TEXT,
    fecha TEXT NOT NULL,
    motivo TEXT NOT NULL,
    tipo TEXT NOT NULL,
    FOREIGN KEY (cod_empleado) REFERENCES empleados(codigo) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS marcas_asistencia (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cod_empleado TEXT,
    fecha TEXT NOT NULL,
    hora TEXT NOT NULL,
    tipo_marca TEXT NOT NULL,
    FOREIGN KEY (cod_empleado) REFERENCES empleados(codigo) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    nombre TEXT NOT NULL
);
SQL;
    $pdo->exec($schema);

    // Añadir columna password a empleados si la base de datos ya existía de antes
    if (!$is_new) {
        $columns = $pdo->query("PRAGMA table_info(empleados)")->fetchAll(PDO::FETCH_ASSOC);
        $has_password = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'password') {
                $has_password = true;
                break;
            }
        }
        if (!$has_password) {
            $pdo->exec("ALTER TABLE empleados ADD COLUMN password TEXT NOT NULL DEFAULT '12345'");
        }
    }

    // Insertar usuario administrador por defecto si no existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO usuarios (username, password, nombre) VALUES ('admin', '$hash', 'Administrador Principal')");
    }
    
} catch (PDOException $e) {
    die("Error de conexión a SQLite: " . $e->getMessage());
}
?>
