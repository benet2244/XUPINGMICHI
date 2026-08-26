-- =============================================
-- XUPING JOYERÍA — Script de Base de Datos
-- =============================================

CREATE DATABASE IF NOT EXISTS xuping_joyeria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xuping_joyeria;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT '💎',
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    imagen VARCHAR(500) DEFAULT NULL,
    disponible TINYINT(1) DEFAULT 1,
    destacado TINYINT(1) DEFAULT 0,
    material VARCHAR(100) DEFAULT NULL,
    peso VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla de carrito
CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    nombre_cliente VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    telefono VARCHAR(50),
    direccion TEXT,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','pagado','enviado','cancelado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50) DEFAULT 'apartado',
    referencia_pago VARCHAR(200),
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de items del pedido
CREATE TABLE IF NOT EXISTS pedido_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT,
    nombre_producto VARCHAR(200),
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
);

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Categorías
INSERT INTO categorias (nombre, slug, descripcion, icono, orden) VALUES
('Cadenas', 'cadenas', 'Cadenas finas y elegantes en oro y plata', '⛓️', 1),
('Aretes', 'aretes', 'Aretes para toda ocasión, casuales y formales', '💎', 2),
('Anillos', 'anillos', 'Anillos de compromiso, alianzas y moda', '💍', 3),
('Pulseras', 'pulseras', 'Pulseras artesanales y de diseñador', '✨', 4);

-- Usuario administrador (password: admin123)
INSERT INTO admin_users (username, password_hash, nombre) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador XUPING');

-- Productos de ejemplo
INSERT INTO productos (categoria_id, nombre, descripcion, precio, disponible, destacado, material) VALUES
(1, 'Cadena Serpiente Oro 18K', 'Elegante cadena de eslabón serpiente en oro 18 kilates, perfecta para uso diario.', 1250.00, 1, 1, 'Oro 18K'),
(1, 'Cadena Figaro Plata 925', 'Cadena estilo Figaro en plata esterlina 925, diseño italiano clásico.', 480.00, 1, 0, 'Plata 925'),
(1, 'Cadena Cubana Oro 14K', 'Cadena cubana gruesa en oro 14 kilates, el clásico de lujo atemporal.', 2100.00, 0, 1, 'Oro 14K'),
(2, 'Aretes Argolla Diamante', 'Aretes argolla con incrustaciones de diamante de 0.5 quilates.', 890.00, 1, 1, 'Oro Blanco 18K'),
(2, 'Aretes Gota Perla Natural', 'Aretes de gota con perla natural cultivada en agua dulce.', 320.00, 1, 0, 'Plata 925'),
(2, 'Aretes Corazón Oro Rosa', 'Aretes corazón en oro rosa 14K, el regalo perfecto.', 560.00, 1, 0, 'Oro Rosa 14K'),
(3, 'Anillo Solitario Diamante', 'Anillo solitario con diamante de 1 quilate, certificado GIA.', 8500.00, 1, 1, 'Oro Blanco 18K'),
(3, 'Alianza Matrimonial Clásica', 'Par de alianzas matrimoniales en oro amarillo 18K, grabado personalizable.', 1800.00, 1, 0, 'Oro 18K'),
(3, 'Anillo Moda Esmeralda', 'Anillo de moda con esmeralda natural colombiana engarzada en oro.', 3200.00, 0, 0, 'Oro 14K'),
(4, 'Pulsera Tenis Diamantes', 'Pulsera tenis con 50 diamantes talla brillante, total 2 quilates.', 4500.00, 1, 1, 'Oro Blanco 18K'),
(4, 'Pulsera Charm Plata', 'Pulsera de charms en plata 925, personalizable con hasta 12 charms.', 280.00, 1, 0, 'Plata 925'),
(4, 'Pulsera Esclava Oro', 'Pulsera esclava rígida en oro 18K con cierre de seguridad.', 1650.00, 1, 0, 'Oro 18K');
