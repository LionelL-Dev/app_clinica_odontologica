-- base de datos de la clínica odontológica

-- creación de base de datos
CREATE DATABASE IF NOT EXISTS clinica_odontologica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- seleccionar la base de datos
USE clinica_odontologica;

-- creación de tabla pacientes
CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- obligatorios (NOT NULL) 
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    domicilio VARCHAR(255) NOT NULL,
    dni VARCHAR(10) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    fecha_turno_asignado DATE NOT NULL,

    -- opcionales
    obra_social VARCHAR(100),
    num_afiliado VARCHAR(50),
    plan VARCHAR(100)
);

-- Tabla secundaria (historial de los dientes)
CREATE TABLE tratamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,

    numero_diente INT NOT NULL,
    plan_tratamiento VARCHAR(255) NOT NULL, -- Ej: "Extracción", "Tratamiento de conducto", "Empaste"
    fecha_tratamiento DATE NOT NULL,
    presupuesto DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE
);