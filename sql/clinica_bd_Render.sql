CREATE TABLE pacientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    domicilio VARCHAR(255) NOT NULL,
    dni VARCHAR(10) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    fecha_turno_asignado DATE NOT NULL,
    obra_social VARCHAR(100),
    num_afiliado VARCHAR(50),
    plan VARCHAR(100)
);

CREATE TABLE tratamientos (
    id SERIAL PRIMARY KEY,
    id_paciente INT NOT NULL,

    numero_diente INT NOT NULL,
    plan_tratamiento VARCHAR(255) NOT NULL,
    fecha_tratamiento DATE NOT NULL,
    presupuesto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE
);