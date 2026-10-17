-- =============================================================================
--  Reservas de alojamiento — esquema (DDL) + datos de ejemplo
--  Compatible con MySQL 8.0.16+ y MariaDB 10.4+ (MAMP / WAMP / XAMPP).
--
--  Uso (la BD debe existir; ver README):
--     mysql -u USUARIO -p NOMBRE_BD < database/schema.sql
--  o en phpMyAdmin: seleccionar la BD > Importar > este fichero.
--
--  ¡Atención! Borra y recrea las tablas `reserva` y `reserva_evento`.
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reserva_evento;
DROP TABLE IF EXISTS reserva;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
--  reserva
-- -----------------------------------------------------------------------------
CREATE TABLE reserva (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre_huesped     VARCHAR(120)  NOT NULL,
    email_huesped      VARCHAR(254)  NOT NULL,
    nombre_alojamiento VARCHAR(150)  NOT NULL,
    fecha_entrada      DATE          NOT NULL,
    fecha_salida       DATE          NOT NULL,
    estado             ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    importe            DECIMAL(10,2) NOT NULL,
    notas              VARCHAR(1000) NULL,
    fecha_creacion     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    -- Índices pensados para los filtros del listado.
    KEY idx_reserva_estado   (estado),
    KEY idx_reserva_fechas   (fecha_entrada, fecha_salida),
    KEY idx_reserva_huesped  (nombre_huesped),

    -- Segunda línea de defensa: la validación principal está en la aplicación.
    CONSTRAINT chk_reserva_fechas  CHECK (fecha_salida > fecha_entrada),
    CONSTRAINT chk_reserva_importe CHECK (importe >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  reserva_evento (historial / timeline)
-- -----------------------------------------------------------------------------
CREATE TABLE reserva_evento (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reserva_id     INT UNSIGNED NOT NULL,
    tipo           VARCHAR(30)  NOT NULL,   -- creada | confirmada | cancelada
    descripcion    VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_evento_reserva (reserva_id, fecha_creacion),
    CONSTRAINT fk_evento_reserva
        FOREIGN KEY (reserva_id) REFERENCES reserva (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
--  Datos de ejemplo
--  Variados a propósito: los tres estados, estancias pasadas / en curso / futuras,
--  nombres con tildes, ñ y apóstrofos, y huéspedes con apellido repetido para
--  probar la búsqueda.
-- =============================================================================

INSERT INTO reserva
    (id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion)
VALUES
    ( 1, 'María García López',     'maria.garcia@example.com',        'Hotel Arts Barcelona',               '2026-11-05', '2026-11-08', 'confirmada',  645.00, 'Llegada tarde, sobre las 23:00.',                    '2026-09-12 10:15:00'),
    ( 2, 'John O''Brien',          'john.obrien@example.co.uk',       'Apartamentos Playa Mar - Benidorm',  '2026-10-20', '2026-10-27', 'pendiente',   812.50, NULL,                                                 '2026-10-10 09:02:00'),
    ( 3, 'Lucía Fernández Ruiz',   'lucia.fernandez@example.es',      'Parador de Granada',                 '2026-12-23', '2026-12-26', 'confirmada',  930.00, 'Habitación con vistas a la Alhambra si es posible.', '2026-08-30 20:11:00'),
    ( 4, 'Javier Martín Sánchez',  'javier.martin@example.com',       'Casa Rural El Roble - Asturias',     '2026-10-02', '2026-10-05', 'cancelada',   285.00, 'Viajan con un perro pequeño.',                       '2026-09-01 12:30:00'),
    ( 5, 'Sophie Dubois',          'sophie.dubois@example.fr',        'Hotel Miramar Málaga',               '2026-11-14', '2026-11-16', 'pendiente',   398.00, NULL,                                                 '2026-10-15 16:20:00'),
    ( 6, 'Carlos Núñez Vidal',     'carlos.nunez@example.com',        'Gran Hotel Bali - Benidorm',         '2026-07-10', '2026-07-17', 'confirmada', 1260.00, 'Cliente habitual.',                                  '2026-05-02 11:00:00'),
    ( 7, 'Ana Belén Torres',       'anabelen.torres@example.es',      'Apartamento Centro Sevilla',         '2027-04-02', '2027-04-06', 'pendiente',   520.00, 'Necesitan cuna para bebé.',                          '2026-10-16 22:05:00'),
    ( 8, 'Pablo Romero Gil',       'pablo.romero@example.com',        'Hotel Arts Barcelona',               '2026-10-16', '2026-10-19', 'confirmada',  690.00, NULL,                                                 '2026-09-20 14:00:00'),
    ( 9, 'Emma Schmidt',           'emma.schmidt@example.de',         'Hotel Hesperia Lanzarote',           '2026-12-01', '2026-12-08', 'cancelada',  1105.00, 'Cancelada por cambio de vuelos.',                    '2026-09-05 09:30:00'),
    (10, 'Daniel Moreno Castro',   'daniel.moreno@example.com',       'Parador de Cangas de Onís',          '2026-11-27', '2026-11-29', 'pendiente',   310.00, NULL,                                                 '2026-10-14 08:50:00'),
    (11, 'Laura Jiménez Pérez',    'laura.jimenez@example.es',        'Hotel Miramar Málaga',               '2026-08-03', '2026-08-10', 'confirmada', 1540.00, 'Celebran aniversario: detalle de bienvenida.',       '2026-06-11 19:45:00'),
    (12, 'Marco Rossi',            'marco.rossi@example.it',          'Apartamentos Playa Mar - Benidorm',  '2026-09-14', '2026-09-21', 'cancelada',   735.00, NULL,                                                 '2026-07-22 10:00:00'),
    (13, 'Elena Navarro Díaz',     'elena.navarro@example.com',       'Casa Rural El Roble - Asturias',     '2026-12-30', '2027-01-02', 'confirmada',  450.00, 'Grupo de 6 personas.',                               '2026-10-03 13:33:00'),
    (14, 'Miguel Ángel Ortega',    'miguelangel.ortega@example.es',   'Hotel Hesperia Lanzarote',           '2027-02-10', '2027-02-17', 'pendiente',   980.00, NULL,                                                 '2026-10-17 09:15:00'),
    (15, 'Sara Muñoz Herrera',     'sara.munoz@example.com',          'Apartamento Centro Sevilla',         '2026-10-24', '2026-10-26', 'confirmada',  260.00, NULL,                                                 '2026-10-05 17:00:00'),
    (16, 'Thomas Müller',          'thomas.mueller@example.de',       'Gran Hotel Bali - Benidorm',         '2026-11-02', '2026-11-09', 'pendiente',  1190.00, 'Solicita habitación en planta alta.',                '2026-10-11 11:11:00'),
    (17, 'Isabel Romero Gil',      'isabel.romero@example.com',       'Parador de Granada',                 '2026-10-09', '2026-10-11', 'cancelada',   420.00, NULL,                                                 '2026-09-15 15:15:00'),
    (18, 'Alejandro Vega Ramos',   'alejandro.vega@example.es',       'Hotel Arts Barcelona',               '2027-03-18', '2027-03-21', 'confirmada',  705.00, 'Viaje de empresa: factura a nombre de la empresa.',  '2026-10-12 10:40:00'),
    (19, 'Carmen Delgado Ruiz',    'carmen.delgado@example.com',      'Parador de Cangas de Onís',          '2026-09-25', '2026-09-28', 'confirmada',  465.00, NULL,                                                 '2026-08-20 09:00:00'),
    (20, 'Hugo Serrano Molina',    'hugo.serrano@example.es',         'Hotel Hesperia Lanzarote',           '2026-10-30', '2026-11-03', 'pendiente',   640.00, NULL,                                                 '2026-10-13 20:20:00');

-- Historial coherente con el estado actual de cada reserva:
-- toda reserva nace "pendiente" y después puede confirmarse y/o cancelarse.
INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion) VALUES
    ( 1, 'creada',     'Reserva creada con estado pendiente.',       '2026-09-12 10:15:00'),
    ( 1, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-09-12 18:40:00'),
    ( 2, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-10 09:02:00'),
    ( 3, 'creada',     'Reserva creada con estado pendiente.',       '2026-08-30 20:11:00'),
    ( 3, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-08-31 09:00:00'),
    ( 4, 'creada',     'Reserva creada con estado pendiente.',       '2026-09-01 12:30:00'),
    ( 4, 'cancelada',  'Estado cambiado de pendiente a cancelada.',  '2026-09-25 08:45:00'),
    ( 5, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-15 16:20:00'),
    ( 6, 'creada',     'Reserva creada con estado pendiente.',       '2026-05-02 11:00:00'),
    ( 6, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-05-02 11:30:00'),
    ( 7, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-16 22:05:00'),
    ( 8, 'creada',     'Reserva creada con estado pendiente.',       '2026-09-20 14:00:00'),
    ( 8, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-09-21 10:00:00'),
    ( 9, 'creada',     'Reserva creada con estado pendiente.',       '2026-09-05 09:30:00'),
    ( 9, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-09-05 13:00:00'),
    ( 9, 'cancelada',  'Estado cambiado de confirmada a cancelada.', '2026-10-01 17:22:00'),
    (10, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-14 08:50:00'),
    (11, 'creada',     'Reserva creada con estado pendiente.',       '2026-06-11 19:45:00'),
    (11, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-06-12 09:10:00'),
    (12, 'creada',     'Reserva creada con estado pendiente.',       '2026-07-22 10:00:00'),
    (12, 'cancelada',  'Estado cambiado de pendiente a cancelada.',  '2026-07-30 12:12:00'),
    (13, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-03 13:33:00'),
    (13, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-10-04 10:00:00'),
    (14, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-17 09:15:00'),
    (15, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-05 17:00:00'),
    (15, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-10-06 08:30:00'),
    (16, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-11 11:11:00'),
    (17, 'creada',     'Reserva creada con estado pendiente.',       '2026-09-15 15:15:00'),
    (17, 'cancelada',  'Estado cambiado de pendiente a cancelada.',  '2026-10-08 21:00:00'),
    (18, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-12 10:40:00'),
    (18, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-10-12 12:00:00'),
    (19, 'creada',     'Reserva creada con estado pendiente.',       '2026-08-20 09:00:00'),
    (19, 'confirmada', 'Estado cambiado de pendiente a confirmada.', '2026-08-20 18:30:00'),
    (20, 'creada',     'Reserva creada con estado pendiente.',       '2026-10-13 20:20:00');
