
-- ForkFresh Database Schema
-- Database: forkfresh
-- Run this file in phpMyAdmin or via MySQL CLI:
--   mysql -u root -p forkfresh < schema.sql


USE forkfresh;


-- 1. RIDERS

CREATE TABLE IF NOT EXISTS riders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rider_code    VARCHAR(20)  NOT NULL UNIQUE,          -- e.g. RDR2456
    name          VARCHAR(100) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url    VARCHAR(255) DEFAULT NULL,
    status        ENUM('online','offline','busy') NOT NULL DEFAULT 'offline',
    rating        DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    vehicle_type  ENUM('motorcycle','bicycle','car') NOT NULL DEFAULT 'motorcycle',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2. ORDERS

CREATE TABLE IF NOT EXISTS orders (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number      VARCHAR(20)  NOT NULL UNIQUE,      -- e.g. FF125679
    customer_name     VARCHAR(100) NOT NULL,
    customer_phone    VARCHAR(20)  NOT NULL,
    restaurant_name   VARCHAR(150) NOT NULL,
    restaurant_lat    DECIMAL(10,7) NOT NULL DEFAULT 0,
    restaurant_lng    DECIMAL(10,7) NOT NULL DEFAULT 0,
    dropoff_address   VARCHAR(255) NOT NULL,
    dropoff_lat       DECIMAL(10,7) NOT NULL DEFAULT 0,
    dropoff_lng       DECIMAL(10,7) NOT NULL DEFAULT 0,
    status            ENUM(
                        'pending',
                        'assigned',
                        'preparing',
                        'on_the_way',
                        'out_for_delivery',
                        'delivered',
                        'cancelled'
                      ) NOT NULL DEFAULT 'pending',
    rider_id          INT UNSIGNED DEFAULT NULL,
    total_amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    estimated_minutes INT NOT NULL DEFAULT 30,
    placed_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_at       TIMESTAMP NULL DEFAULT NULL,
    delivered_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL,
    INDEX idx_status  (status),
    INDEX idx_rider   (rider_id),
    INDEX idx_placed  (placed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 3. ORDER STATUS LOG  (full history)

CREATE TABLE IF NOT EXISTS order_status_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    status     VARCHAR(50)  NOT NULL,
    note       VARCHAR(255) DEFAULT NULL,
    changed_by ENUM('system','rider','admin') NOT NULL DEFAULT 'system',
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 4. GPS TRACKING

CREATE TABLE IF NOT EXISTS gps_tracking (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rider_id   INT UNSIGNED NOT NULL,
    order_id   INT UNSIGNED DEFAULT NULL,
    latitude   DECIMAL(10,7) NOT NULL,
    longitude  DECIMAL(10,7) NOT NULL,
    speed_kmh  DECIMAL(5,2) DEFAULT 0,
    heading    DECIMAL(5,2) DEFAULT 0,          -- degrees 0-360
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_rider_time (rider_id, recorded_at),
    INDEX idx_order      (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 5. PUSH SUBSCRIPTIONS  (Web Push / browser)

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscriber_type ENUM('customer','rider','admin') NOT NULL DEFAULT 'customer',
    subscriber_id   INT UNSIGNED DEFAULT NULL,   -- rider id or customer id
    endpoint     TEXT NOT NULL,
    p256dh       TEXT NOT NULL,
    auth_key     TEXT NOT NULL,
    user_agent   VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_endpoint (endpoint(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 6. PUSH NOTIFICATIONS LOG

CREATE TABLE IF NOT EXISTS push_notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED DEFAULT NULL,
    order_id        INT UNSIGNED DEFAULT NULL,
    rider_id        INT UNSIGNED DEFAULT NULL,
    title           VARCHAR(100) NOT NULL,
    body            TEXT         NOT NULL,
    status          ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    sent_at         TIMESTAMP NULL DEFAULT NULL,
    error_msg       VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id)        REFERENCES orders(id)             ON DELETE SET NULL,
    FOREIGN KEY (rider_id)        REFERENCES riders(id)             ON DELETE SET NULL,
    INDEX idx_status    (status),
    INDEX idx_order     (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- SEED DATA – one demo rider + three orders

INSERT IGNORE INTO riders
    (rider_code, name, phone, email, password_hash, status, rating)
VALUES
    ('RDR2456', 'Jean Claude', '+237678123456',
     'jean.claude@forkfresh.com',
     '$2y$12$exampleHashedPasswordHere000000000000000000000',   -- change in prod
     'online', 4.80),
    ('RDR1001', 'Marie Ngassa', '+237699001122',
     'marie.ngassa@forkfresh.com',
     '$2y$12$exampleHashedPasswordHere000000000000000000000',
     'offline', 4.60);

INSERT IGNORE INTO orders
    (order_number, customer_name, customer_phone,
     restaurant_name, restaurant_lat, restaurant_lng,
     dropoff_address, dropoff_lat, dropoff_lng,
     status, rider_id, total_amount, estimated_minutes, placed_at)
VALUES
    ('FF125679', 'Alice Fon', '+237677001122',
     'La Delice Buea',  4.1527, 9.2403,
     'Molyko, Buea',    4.1560, 9.2450,
     'out_for_delivery', 1, 4500.00, 30,
     '2023-05-24 14:30:00'),

    ('FF125687', 'Bob Etame', '+237677334455',
     'La Delice Buea',  4.1527, 9.2403,
     'Great Soppo, Buea', 4.1490, 9.2380,
     'assigned', 1, 6200.00, 25,
     NOW()),

    ('FF125690', 'Clara Manga', '+237677556677',
     'Chicken Republic', 4.1550, 9.2410,
     'Mile 17, Buea',    4.1600, 9.2500,
     'pending', NULL, 3800.00, 40,
     NOW());

-- Log initial statuses
INSERT IGNORE INTO order_status_log (order_id, status, changed_by) VALUES
    (1, 'pending',           'system'),
    (1, 'assigned',          'system'),
    (1, 'preparing',         'rider'),
    (1, 'on_the_way',        'rider'),
    (1, 'out_for_delivery',  'rider'),
    (2, 'pending',           'system'),
    (2, 'assigned',          'system'),
    (3, 'pending',           'system');
