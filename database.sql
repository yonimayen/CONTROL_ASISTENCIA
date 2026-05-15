CREATE DATABASE IF NOT EXISTS asistencia_db;
USE asistencia_db;

CREATE TABLE IF NOT EXISTS departamentos (
    codigo VARCHAR(10) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS jornadas (
    codigo VARCHAR(10) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL
);

CREATE TABLE IF NOT EXISTS empleados (
    codigo VARCHAR(10) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cod_jornada VARCHAR(10),
    cod_departamento VARCHAR(10),
    FOREIGN KEY (cod_jornada) REFERENCES jornadas(codigo) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (cod_departamento) REFERENCES departamentos(codigo) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_empleado VARCHAR(10),
    fecha DATE NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    tipo ENUM('Ausencia', 'Llegada Tarde', 'Salida Temprano') NOT NULL,
    FOREIGN KEY (cod_empleado) REFERENCES empleados(codigo) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS marcas_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_empleado VARCHAR(10),
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    tipo_marca ENUM('Entrada', 'Salida') NOT NULL,
    FOREIGN KEY (cod_empleado) REFERENCES empleados(codigo) ON DELETE CASCADE ON UPDATE CASCADE
);
