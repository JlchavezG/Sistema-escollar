-- Base de datos para sistema escolar
CREATE DATABASE sistema_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_escolar;

-- Tabla de usuarios (profesores y administrativos)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100),
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('profesor', 'administrativo') NOT NULL,
    telefono VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de alumnos
CREATE TABLE alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100),
    fecha_nacimiento DATE,
    grado VARCHAR(50),
    grupo VARCHAR(10),
    tutor_nombre VARCHAR(200),
    tutor_telefono VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de módulos/materias
CREATE TABLE modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    clave VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de asignación de módulos a profesores
CREATE TABLE asignacion_modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profesor_id INT NOT NULL,
    modulo_id INT NOT NULL,
    grado VARCHAR(50) NOT NULL,
    grupo VARCHAR(10) NOT NULL,
    ciclo_escolar VARCHAR(20) NOT NULL,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id),
    FOREIGN KEY (modulo_id) REFERENCES modulos(id),
    UNIQUE KEY unique_asignacion (profesor_id, modulo_id, grado, grupo, ciclo_escolar)
);

-- Tabla de calificaciones
CREATE TABLE calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    modulo_id INT NOT NULL,
    profesor_id INT NOT NULL,
    grado VARCHAR(50) NOT NULL,
    grupo VARCHAR(10) NOT NULL,
    ciclo_escolar VARCHAR(20) NOT NULL,
    mes VARCHAR(20) NOT NULL,
    calificacion DECIMAL(5,2) NOT NULL,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
    FOREIGN KEY (modulo_id) REFERENCES modulos(id),
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)
);

-- Tabla de boletas (diseños personalizables)
CREATE TABLE plantillas_boletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contenido_html TEXT NOT NULL,
    css_personalizado TEXT,
    activa BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES usuarios(id)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo VARCHAR(50)
);