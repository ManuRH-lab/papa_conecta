-- =====================================================================
--  PAPA CONECTA - Script de creación de la base de datos
--  SGBD objetivo: MySQL 8.0 (compatible con MariaDB 10.11)
--  Capítulo 3 - Sección 3.5 Modelamiento de base de datos
--  Versión 2 (29/09/2026) - ajustes para la implementación con PHP 8:
--    * usuario.contrasena: VARCHAR(255) para password_hash() de PHP y
--      NULL permitido solo para compradores que compran sin crear cuenta
--    * region: la unicidad pasa a (nombre_region, provincia)
--    * producto: nueva columna imagen (foto del producto, CU-02)
--    * vw_catalogo: incluye imagen e identificadores para los filtros
--    * datos de prueba ampliados para el catálogo
-- =====================================================================

SET NAMES utf8mb4;   -- tildes y ñ correctas al ejecutar desde consola

DROP DATABASE IF EXISTS papa_conecta;
CREATE DATABASE papa_conecta
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE papa_conecta;

-- ---------------------------------------------------------------------
-- 1. USUARIO: datos de acceso comunes a los tres roles
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
  id_usuario    INT          NOT NULL AUTO_INCREMENT,
  nombres       VARCHAR(50)  NOT NULL,
  apellidos     VARCHAR(50)  NOT NULL,
  correo        VARCHAR(80)  NOT NULL,
  telefono      VARCHAR(15)  NOT NULL,
  tipo_usuario  ENUM('agricultor','comprador','administrador') NOT NULL,
  contrasena    VARCHAR(255) NULL,              -- hash bcrypt (password_hash de PHP)
  CONSTRAINT pk_usuario PRIMARY KEY (id_usuario),
  CONSTRAINT uq_usuario_correo UNIQUE (correo),
  CONSTRAINT ck_usuario_correo CHECK (correo LIKE '%_@_%._%'),
  -- agricultor y administrador siempre inician sesión; el comprador puede
  -- registrarse al confirmar su primer pedido (CU-07) sin crear contraseña
  CONSTRAINT ck_usuario_contrasena
    CHECK (tipo_usuario = 'comprador' OR contrasena IS NOT NULL)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 2. REGION: zonas productoras (región + provincia) para la trazabilidad
-- ---------------------------------------------------------------------
CREATE TABLE region (
  id_region      INT         NOT NULL AUTO_INCREMENT,
  nombre_region  VARCHAR(50) NOT NULL,
  provincia      VARCHAR(50) NOT NULL,
  altitud_msnm   INT         NOT NULL,
  CONSTRAINT pk_region PRIMARY KEY (id_region),
  CONSTRAINT uq_region_provincia UNIQUE (nombre_region, provincia),
  CONSTRAINT ck_region_altitud CHECK (altitud_msnm > 0)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. VARIEDAD_PAPA: catálogo informativo de variedades nativas (RF-07)
-- ---------------------------------------------------------------------
CREATE TABLE variedad_papa (
  id_variedad  INT         NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(60) NOT NULL,
  color_piel   VARCHAR(30) NOT NULL,
  temporada    VARCHAR(30) NOT NULL,
  beneficios   TEXT        NULL,
  descripcion  TEXT        NULL,
  CONSTRAINT pk_variedad PRIMARY KEY (id_variedad),
  CONSTRAINT uq_variedad_nombre UNIQUE (nombre)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. AGRICULTOR: especialización 1 a 1 de USUARIO
-- ---------------------------------------------------------------------
CREATE TABLE agricultor (
  id_agricultor   INT         NOT NULL AUTO_INCREMENT,
  id_usuario      INT         NOT NULL,
  id_region       INT         NOT NULL,
  comunidad       VARCHAR(80) NOT NULL,
  dni             VARCHAR(8)  NOT NULL,
  fecha_registro  DATE        NOT NULL,
  CONSTRAINT pk_agricultor PRIMARY KEY (id_agricultor),
  CONSTRAINT uq_agricultor_usuario UNIQUE (id_usuario),
  CONSTRAINT uq_agricultor_dni UNIQUE (dni),
  CONSTRAINT ck_agricultor_dni CHECK (dni REGEXP '^[0-9]{8}$'),
  CONSTRAINT fk_agricultor_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_agricultor_region FOREIGN KEY (id_region)
    REFERENCES region (id_region) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 5. COMPRADOR: especialización 1 a 1 de USUARIO
-- ---------------------------------------------------------------------
CREATE TABLE comprador (
  id_comprador       INT          NOT NULL AUTO_INCREMENT,
  id_usuario         INT          NOT NULL,
  direccion_entrega  VARCHAR(150) NOT NULL,
  referencia         VARCHAR(150) NULL,
  CONSTRAINT pk_comprador PRIMARY KEY (id_comprador),
  CONSTRAINT uq_comprador_usuario UNIQUE (id_usuario),
  CONSTRAINT fk_comprador_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 6. PRODUCTO: publicación de un agricultor (CU-02, CU-03, CU-10)
-- ---------------------------------------------------------------------
CREATE TABLE producto (
  id_producto        INT            NOT NULL AUTO_INCREMENT,
  id_agricultor      INT            NOT NULL,
  id_variedad        INT            NOT NULL,
  cantidad_kg        DECIMAL(8,2)   NOT NULL,
  precio_kg          DECIMAL(8,2)   NOT NULL,
  imagen             VARCHAR(255)   NULL,       -- ruta de la foto del producto (CU-02)
  estado             ENUM('pendiente','activo','agotado','inactivo')
                                    NOT NULL DEFAULT 'pendiente',
  observacion        VARCHAR(200)   NULL,       -- motivo de rechazo (CU-10)
  fecha_publicacion  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_producto PRIMARY KEY (id_producto),
  CONSTRAINT ck_producto_cantidad CHECK (cantidad_kg >= 0),
  CONSTRAINT ck_producto_precio CHECK (precio_kg > 0),
  CONSTRAINT fk_producto_agricultor FOREIGN KEY (id_agricultor)
    REFERENCES agricultor (id_agricultor) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_producto_variedad FOREIGN KEY (id_variedad)
    REFERENCES variedad_papa (id_variedad) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_producto_estado ON producto (estado);
CREATE INDEX idx_producto_precio ON producto (precio_kg);

-- ---------------------------------------------------------------------
-- 7. PEDIDO: cabecera de la compra (CU-07)
-- ---------------------------------------------------------------------
CREATE TABLE pedido (
  id_pedido          INT            NOT NULL AUTO_INCREMENT,
  id_comprador       INT            NOT NULL,
  fecha_pedido       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total              DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  estado_pedido      ENUM('pendiente','confirmado','entregado','cancelado')
                                    NOT NULL DEFAULT 'pendiente',
  direccion_entrega  VARCHAR(150)   NOT NULL,
  CONSTRAINT pk_pedido PRIMARY KEY (id_pedido),
  CONSTRAINT ck_pedido_total CHECK (total >= 0),
  CONSTRAINT fk_pedido_comprador FOREIGN KEY (id_comprador)
    REFERENCES comprador (id_comprador) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_pedido_fecha ON pedido (fecha_pedido);

-- ---------------------------------------------------------------------
-- 8. DETALLE_PEDIDO: líneas del pedido
-- ---------------------------------------------------------------------
CREATE TABLE detalle_pedido (
  id_detalle   INT            NOT NULL AUTO_INCREMENT,
  id_pedido    INT            NOT NULL,
  id_producto  INT            NOT NULL,
  cantidad_kg  DECIMAL(8,2)   NOT NULL,
  precio_unit  DECIMAL(8,2)   NOT NULL,
  subtotal     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  CONSTRAINT pk_detalle PRIMARY KEY (id_detalle),
  CONSTRAINT uq_detalle_pedido_producto UNIQUE (id_pedido, id_producto),
  CONSTRAINT ck_detalle_cantidad CHECK (cantidad_kg > 0),
  CONSTRAINT ck_detalle_precio CHECK (precio_unit > 0),
  CONSTRAINT ck_detalle_subtotal CHECK (subtotal >= 0),
  CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedido (id_pedido) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto)
    REFERENCES producto (id_producto) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
--  REGLAS DE NEGOCIO EN LA BASE DE DATOS
-- =====================================================================
DELIMITER //

-- Calcula el subtotal de cada línea (RF-05)
CREATE TRIGGER trg_detalle_subtotal
BEFORE INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
  SET NEW.subtotal = ROUND(NEW.cantidad_kg * NEW.precio_unit, 2);
END//

-- Actualiza el total del pedido y descuenta el stock del producto
CREATE TRIGGER trg_detalle_total_stock
AFTER INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
  UPDATE pedido
     SET total = total + NEW.subtotal
   WHERE id_pedido = NEW.id_pedido;

  -- el estado se evalúa primero porque MySQL asigna de izquierda a derecha
  UPDATE producto
     SET estado = IF(cantidad_kg - NEW.cantidad_kg <= 0, 'agotado', estado),
         cantidad_kg = cantidad_kg - NEW.cantidad_kg
   WHERE id_producto = NEW.id_producto;
END//

DELIMITER ;

-- Vista del catálogo con trazabilidad (CU-04, CU-05, CU-08)
CREATE VIEW vw_catalogo AS
SELECT p.id_producto,
       v.id_variedad,
       v.nombre                              AS variedad,
       p.precio_kg,
       p.cantidad_kg,
       p.imagen,
       CONCAT(u.nombres, ' ', u.apellidos)   AS agricultor,
       a.comunidad,
       r.id_region,
       r.nombre_region                       AS region,
       r.provincia,
       r.altitud_msnm,
       p.fecha_publicacion
  FROM producto p
  JOIN variedad_papa v ON v.id_variedad  = p.id_variedad
  JOIN agricultor    a ON a.id_agricultor = p.id_agricultor
  JOIN usuario       u ON u.id_usuario   = a.id_usuario
  JOIN region        r ON r.id_region    = a.id_region
 WHERE p.estado = 'activo';

-- =====================================================================
--  DATOS DE PRUEBA (referenciales, para validar el modelo)
-- =====================================================================
INSERT INTO region (nombre_region, provincia, altitud_msnm) VALUES
  ('Puno',     'Azángaro',     3859),
  ('Junín',    'Huancayo',     3259),
  ('Huánuco',  'Pachitea',     2570),
  ('Cusco',    'Paucartambo',  2906),
  ('Apurímac', 'Andahuaylas',  2926),
  ('Puno',     'Chucuito',     3869);   -- misma región, otra provincia

INSERT INTO variedad_papa (nombre, color_piel, temporada, beneficios, descripcion) VALUES
  ('Huayro',         'Rojiza',          'Abril - julio',
   'Fuente de potasio y vitamina C.', 'Textura harinosa; ideal para sancochado y causa.'),
  ('Peruanita',      'Amarilla y roja', 'Abril - julio',
   'Aporta carbohidratos complejos y fibra.', 'Pulpa amarilla; ideal para frituras y purés.'),
  ('Amarilla Tumbay','Amarilla',        'Abril - julio',
   'Contiene carotenoides.', 'Muy harinosa; base de la causa limeña y el puré.'),
  ('Huamantanga',    'Morada oscura',   'Abril - julio',
   'Pigmentos con acción antioxidante.', 'Sabor intenso; recomendada para sancochar.'),
  ('Qompis',         'Morada y blanca', 'Mayo - julio',
   'Fuente de energía y vitamina C.', 'Papa harinosa de Cusco; ideal para sancochar y para la huatia.'),
  ('Camotillo',      'Amarilla rojiza', 'Abril - junio',
   'Aporta fibra y potasio.', 'Pulpa amarilla de sabor dulce; ideal para sancochado y ensaladas.');

-- Contraseñas de prueba: Admin#2026 (administrador) y Demo#2026 (agricultores),
-- guardadas con password_hash() de PHP (bcrypt, costo 10)
INSERT INTO usuario (nombres, apellidos, correo, telefono, tipo_usuario, contrasena) VALUES
  ('Admin',   'Plataforma',      'admin@papaconecta.pe',     '999000111', 'administrador',
   '$2y$10$Psw431Wq/wsdHLvi7Sdti.8R0DhtYeGCYbggQcOnMhGOdQn0HLqlK'),
  ('Juana',   'Quispe Mamani',   'juana.quispe@correo.pe',   '951222333', 'agricultor',
   '$2y$10$lsZcrXFHkk04fsC73excW.bkNe.lv44EraJNi4PD36ERpQJNh76XW'),
  ('Teodoro', 'Huamán Rojas',    'teodoro.huaman@correo.pe', '964555666', 'agricultor',
   '$2y$10$lsZcrXFHkk04fsC73excW.bkNe.lv44EraJNi4PD36ERpQJNh76XW'),
  ('Rosa',    'Condori Huillca', 'rosa.condori@correo.pe',   '984111222', 'agricultor',
   '$2y$10$lsZcrXFHkk04fsC73excW.bkNe.lv44EraJNi4PD36ERpQJNh76XW'),
  ('Hilario', 'Ponce Tarazona',  'hilario.ponce@correo.pe',  '962333444', 'agricultor',
   '$2y$10$lsZcrXFHkk04fsC73excW.bkNe.lv44EraJNi4PD36ERpQJNh76XW'),
  -- compradora registrada al confirmar su pedido (sin contraseña)
  ('Lucía',   'Torres Vega',     'lucia.torres@correo.pe',   '987444555', 'comprador', NULL);

INSERT INTO agricultor (id_usuario, id_region, comunidad, dni, fecha_registro) VALUES
  (2, 1, 'Comunidad campesina de Muñani',      '43215678', '2026-03-02'),
  (3, 2, 'Anexo de Chupaca Alta',              '40987654', '2026-03-05'),
  (4, 4, 'Comunidad campesina de Challabamba', '45678123', '2026-03-10'),
  (5, 3, 'Centro poblado de Panao',            '41234567', '2026-03-12');

INSERT INTO comprador (id_usuario, direccion_entrega, referencia) VALUES
  (6, 'Av. Arequipa 1520, Lince, Lima', 'Frente al parque');

INSERT INTO producto (id_agricultor, id_variedad, cantidad_kg, precio_kg, imagen, estado) VALUES
  (1, 1, 120.00, 4.50, 'img/productos/huayro-puno.jpg',       'activo'),
  (1, 4,  60.00, 6.00, 'img/productos/huamantanga-puno.jpg',  'activo'),
  (2, 2, 200.00, 3.80, 'img/productos/peruanita-junin.jpg',   'activo'),
  (2, 3,  80.00, 5.20, NULL,                                  'pendiente'),
  (3, 5,  90.00, 5.00, 'img/productos/qompis-cusco.jpg',      'activo'),
  (4, 6, 150.00, 4.20, 'img/productos/camotillo-huanuco.jpg', 'activo'),
  (3, 2,  70.00, 4.00, 'img/productos/peruanita-cusco.jpg',   'activo');

-- Pedido de ejemplo: los triggers calculan subtotales, total y stock
INSERT INTO pedido (id_comprador, direccion_entrega)
  VALUES (1, 'Av. Arequipa 1520, Lince, Lima');
INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad_kg, precio_unit) VALUES
  (1, 1, 10.00, 4.50),
  (1, 3,  5.00, 3.80);
