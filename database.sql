-- ============================================================
--  CafePro – Esquema de base de datos
--  INSTRUCCIONES PARA INFINITYFREE:
--  1. Crea la BD desde el panel → MySQL Databases
--  2. En phpMyAdmin, selecciona la BD creada
--  3. Importa este archivo (sin CREATE DATABASE ni USE)
-- ============================================================

-- ---- Usuarios -----------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL DEFAULT '',
    reset_token   VARCHAR(128)          DEFAULT NULL,
    reset_expires INT                   DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Productos ----------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(255)    NOT NULL,
    precio   DECIMAL(12,2)   NOT NULL DEFAULT 0,
    img_path VARCHAR(500)             DEFAULT NULL,
    stock    INT             NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Ventas -------------------------------------------------
CREATE TABLE IF NOT EXISTS ventas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    fecha      DATE         NOT NULL,
    hora       TIME         NOT NULL,
    medio_pago VARCHAR(50)  NOT NULL DEFAULT 'Efectivo',
    total      DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS venta_items (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT           NOT NULL,
    nombre   VARCHAR(255)  NOT NULL,
    cantidad INT           NOT NULL,
    precio   DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Proveedores --------------------------------------------
CREATE TABLE IF NOT EXISTS proveedores (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(255) NOT NULL,
    contacto VARCHAR(255) NOT NULL DEFAULT '',
    telefono VARCHAR(50)  NOT NULL DEFAULT '',
    email    VARCHAR(255) NOT NULL DEFAULT '',
    notas    TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Compras ------------------------------------------------
CREATE TABLE IF NOT EXISTS compras (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    fecha      DATE          NOT NULL,
    proveedor  VARCHAR(255)  NOT NULL,
    total      DECIMAL(12,2) NOT NULL DEFAULT 0,
    notas      TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compra_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    compra_id     INT           NOT NULL,
    producto      VARCHAR(255)  NOT NULL,
    cantidad      DECIMAL(10,2) NOT NULL,
    costo_unitario DECIMAL(12,2) NOT NULL,
    subtotal      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Caja ---------------------------------------------------
CREATE TABLE IF NOT EXISTS caja (
    id    INT PRIMARY KEY DEFAULT 1,
    fecha DATE          NOT NULL,
    base  DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  Datos iniciales (migración desde JSON)
-- ============================================================

-- Usuario admin (contraseña: 1234 — cámbiala en la app)
INSERT IGNORE INTO usuarios (username, password_hash, email)
VALUES ('admin', '$2y$10$nH2ym6hfga1Uxc/9ZgKVLOvHCtyYlwRyL6ruIF2IOwX.rBy7W94i6', '');

-- Productos existentes
INSERT IGNORE INTO productos (nombre, precio, stock) VALUES
    ('CocaCola', 3200, 6),
    ('Pan',      2000, 15),
    ('Cafe',     1200, 4);

-- Venta de ejemplo
INSERT IGNORE INTO ventas (id, fecha, hora, medio_pago, total)
VALUES (1, '2026-05-06', '04:38:31', 'Efectivo', 11600);

INSERT IGNORE INTO venta_items (venta_id, nombre, cantidad, precio, subtotal) VALUES
    (1, 'CocaCola', 3, 3200, 9600),
    (1, 'Pan',      1, 2000, 2000);

-- Caja inicial
INSERT IGNORE INTO caja (id, fecha, base) VALUES (1, '2026-05-04', 10000);
