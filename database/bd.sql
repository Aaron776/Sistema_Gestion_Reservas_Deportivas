CREATE DATABASE IF NOT EXISTS reservas_deportivas;
USE reservas_deportivas;

-- USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    telefono VARCHAR(20),
    rol ENUM('admin','cliente','recepcionista') DEFAULT 'cliente',
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    ultimo_acceso DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    facebook_id VARCHAR(100) NULL UNIQUE
);

-- COMPLEJOS DEPORTIVOS
CREATE TABLE complejos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    descripcion TEXT,
    imagen_url VARCHAR(255),
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- CANCHAS
CREATE TABLE canchas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complejo_id INT,
    nombre VARCHAR(100),
    descripcion TEXT,
    imagen_url VARCHAR(255),
    tipo ENUM('futbol','basket','tenis','padel','voley','patinaje','otro'),
    precio_hora DECIMAL(10,2),
    estado ENUM('disponible','mantenimiento','inactivo') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (complejo_id) REFERENCES complejos(id)
);

-- HORARIOS DISPONIBLES
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hora_inicio TIME,
    hora_fin TIME
);

-- RESERVAS
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    cancha_id INT,
    fecha DATE,
    horario_id INT,
    estado ENUM('pendiente','confirmada','cancelada','finalizada') DEFAULT 'pendiente',
    total DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cancha_id) REFERENCES canchas(id),
    FOREIGN KEY (horario_id) REFERENCES horarios(id),
    UNIQUE (cancha_id, fecha, horario_id) -- evita doble reserva
);

-- PAGOS
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT,
    metodo ENUM('efectivo','transferencia','tarjeta'),
    monto DECIMAL(10,2),
    estado ENUM('pendiente','pagado','fallido') DEFAULT 'pendiente',
    comprobante VARCHAR(255),
    referencia_transaccion VARCHAR(100),
    fecha_pago DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id)
);

-- NOTIFICACIONES
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tipo ENUM('reserva','pago','sistema','promocion') DEFAULT 'sistema',
    mensaje TEXT,
    leida ENUM('si','no') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- BLOQUEO DE HORARIOS (mantenimiento o eventos)
CREATE TABLE bloqueos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cancha_id INT,
    fecha DATE,
    horario_id INT,
    motivo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cancha_id) REFERENCES canchas(id),
    FOREIGN KEY (horario_id) REFERENCES horarios(id)
);
