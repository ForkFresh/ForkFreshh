-- =============================================================
-- ForkFresh – Unified Database Schema
-- Covers: users (customers), admins, riders, products,
--         categories, cart, orders, payments, subscriptions,
--         meal plans, contact messages, hero/gallery settings
-- =============================================================

CREATE DATABASE IF NOT EXISTS forkfresh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE forkfresh;

-- ─────────────────────────────────────────────────────────────
-- 1. USERS  (customers)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(100)  NOT NULL,
    last_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    phone         VARCHAR(25)   DEFAULT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    avatar        VARCHAR(255)  DEFAULT NULL,
    role          ENUM('customer') NOT NULL DEFAULT 'customer',
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. ADMINS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. RIDERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS riders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rider_code    VARCHAR(20)   NOT NULL UNIQUE,
    name          VARCHAR(150)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    phone         VARCHAR(25)   NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    avatar_url    VARCHAR(255)  DEFAULT NULL,
    status        ENUM('online','offline','busy') NOT NULL DEFAULT 'offline',
    rating        DECIMAL(3,2)  NOT NULL DEFAULT 5.00,
    vehicle_type  ENUM('motorcycle','bicycle','car') NOT NULL DEFAULT 'motorcycle',
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    image_url  VARCHAR(255) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. PRODUCTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(150) NOT NULL UNIQUE,
    description  TEXT         DEFAULT NULL,
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image_url    VARCHAR(255) DEFAULT NULL,
    image        VARCHAR(255) DEFAULT NULL,   -- legacy column (categories branch)
    is_featured  TINYINT(1)   NOT NULL DEFAULT 0,
    is_popular   TINYINT(1)   NOT NULL DEFAULT 0,
    is_available TINYINT(1)   NOT NULL DEFAULT 1,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category  (category_id),
    INDEX idx_featured  (is_featured),
    INDEX idx_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. HERO SETTINGS  (homepage banner image)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hero_settings (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    alt_text  VARCHAR(255) DEFAULT 'Hero image',
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. ABOUT GALLERY  (about page collage images)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS about_gallery (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url  VARCHAR(255) NOT NULL,
    alt_text   VARCHAR(255) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. ADDRESSES  (customer delivery addresses)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS addresses (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    first_name       VARCHAR(100) DEFAULT NULL,
    last_name        VARCHAR(100) DEFAULT NULL,
    street_address   VARCHAR(255) NOT NULL,
    apartment_suite  VARCHAR(100) DEFAULT NULL,
    city             VARCHAR(100) NOT NULL,
    country          VARCHAR(100) NOT NULL DEFAULT 'Cameroon',
    phone_number     VARCHAR(25)  DEFAULT NULL,
    is_saved         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 9. CART ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    added_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 10. ORDERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(20)   DEFAULT NULL,
    user_id             INT UNSIGNED  NOT NULL,
    address_id          INT UNSIGNED  DEFAULT NULL,
    rider_id            INT UNSIGNED  DEFAULT NULL,
    subtotal            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_fee        DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    total_amount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_time_type  VARCHAR(50)   DEFAULT 'ASAP',
    rider_note          TEXT          DEFAULT NULL,
    order_status        VARCHAR(50)   NOT NULL DEFAULT 'pending',
    customer_name       VARCHAR(150)  DEFAULT NULL,
    customer_phone      VARCHAR(25)   DEFAULT NULL,
    restaurant_name     VARCHAR(150)  DEFAULT NULL,
    dropoff_address     VARCHAR(255)  DEFAULT NULL,
    estimated_minutes   INT           NOT NULL DEFAULT 30,
    placed_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_at         TIMESTAMP     NULL DEFAULT NULL,
    delivered_at        TIMESTAMP     NULL DEFAULT NULL,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (rider_id)   REFERENCES riders(id)    ON DELETE SET NULL,
    INDEX idx_user   (user_id),
    INDEX idx_status (order_status),
    INDEX idx_rider  (rider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 11. ORDER ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED  NOT NULL,
    product_id  INT UNSIGNED  DEFAULT NULL,
    quantity    INT UNSIGNED  NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 12. PAYMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id            INT UNSIGNED  NOT NULL,
    payment_method      VARCHAR(50)   NOT NULL,
    cardholder_name     VARCHAR(150)  DEFAULT NULL,
    card_number_masked  VARCHAR(25)   DEFAULT NULL,
    card_expiry         VARCHAR(10)   DEFAULT NULL,
    momo_phone_number   VARCHAR(25)   DEFAULT NULL,
    momo_carrier        VARCHAR(20)   DEFAULT NULL,
    payment_status      VARCHAR(30)   NOT NULL DEFAULT 'Pending',
    paid_at             TIMESTAMP     NULL DEFAULT NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 13. ORDER STATUS LOG
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_status_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    status     VARCHAR(50)  NOT NULL,
    note       VARCHAR(255) DEFAULT NULL,
    changed_by ENUM('system','rider','admin','customer') NOT NULL DEFAULT 'system',
    changed_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 14. GPS TRACKING
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gps_tracking (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rider_id    INT UNSIGNED  NOT NULL,
    order_id    INT UNSIGNED  DEFAULT NULL,
    latitude    DECIMAL(10,7) NOT NULL,
    longitude   DECIMAL(10,7) NOT NULL,
    speed_kmh   DECIMAL(5,2)  DEFAULT 0,
    heading     DECIMAL(5,2)  DEFAULT 0,
    recorded_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rider_id) REFERENCES riders(id)  ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id)  ON DELETE SET NULL,
    INDEX idx_rider_time (rider_id, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 15. MEAL PLAN TEMPLATES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS meal_plan_templates (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    price         DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('week','month') NOT NULL DEFAULT 'week',
    meals_per_day INT NOT NULL DEFAULT 2,
    days_per_week INT NOT NULL DEFAULT 7,
    meal_type     VARCHAR(100) DEFAULT 'Standard meals',
    is_popular    TINYINT(1)   DEFAULT 0,
    is_custom     TINYINT(1)   DEFAULT 0,
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 16. SUBSCRIPTIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscriptions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,
    template_id       INT UNSIGNED  DEFAULT NULL,
    plan_name         VARCHAR(150)  NOT NULL,
    price             DECIMAL(10,2) NOT NULL,
    billing_cycle     ENUM('week','month') NOT NULL DEFAULT 'week',
    meals_per_day     INT NOT NULL DEFAULT 2,
    days_per_week     INT NOT NULL DEFAULT 7,
    meal_type         VARCHAR(100)  DEFAULT 'Standard meals',
    status            ENUM('active','paused','cancelled') NOT NULL DEFAULT 'active',
    start_date        DATE NOT NULL,
    next_billing_date DATE NOT NULL,
    total_spent       DECIMAL(12,2) DEFAULT 0.00,
    is_custom         TINYINT(1)    DEFAULT 0,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)                ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES meal_plan_templates(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 17. MEAL PLAN PREFERENCES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS meal_plan_preferences (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id  INT UNSIGNED NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    diet_preference  ENUM('high_protein','no_salt','vegetarian','no_maggi','balanced') NOT NULL DEFAULT 'balanced',
    spice_level      ENUM('no_spice','mild','medium','hot','extra_hot') NOT NULL DEFAULT 'medium',
    additional_info  TEXT DEFAULT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 18. FOOD PREFERENCE ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS food_preference_items (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preference_food_items (
    preference_id INT UNSIGNED NOT NULL,
    food_item_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (preference_id, food_item_id),
    FOREIGN KEY (preference_id) REFERENCES meal_plan_preferences(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id)  REFERENCES food_preference_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 19. ALLERGEN ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS allergen_items (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preference_allergens (
    preference_id INT UNSIGNED NOT NULL,
    allergen_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (preference_id, allergen_id),
    FOREIGN KEY (preference_id) REFERENCES meal_plan_preferences(id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id)   REFERENCES allergen_items(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 20. DELIVERIES  (subscription schedule)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS deliveries (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id  INT UNSIGNED NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    delivery_date    DATE NOT NULL,
    meal_description VARCHAR(255) DEFAULT 'Breakfast & Lunch',
    status           ENUM('scheduled','delivered','skipped') DEFAULT 'scheduled',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 21. CONTACT MESSAGES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    subject    VARCHAR(255) DEFAULT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 22. PUSH SUBSCRIPTIONS  (Web Push)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscriber_type ENUM('customer','rider','admin') NOT NULL DEFAULT 'customer',
    subscriber_id   INT UNSIGNED DEFAULT NULL,
    endpoint        TEXT NOT NULL,
    p256dh          TEXT NOT NULL,
    auth_key        TEXT NOT NULL,
    user_agent      VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_endpoint (endpoint(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- SEED DATA
-- =============================================================

-- Admin account  (password: admin123)
INSERT IGNORE INTO admins (name, email, password_hash) VALUES
('ForkFresh Admin', 'admin@forkfresh.cm',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkb.k3m6');

-- Demo customer  (password: password123)
INSERT IGNORE INTO users (first_name, last_name, email, phone, password_hash) VALUES
('Pauline', 'Demo', 'pauline@forkfresh.cm', '+237600000000',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkb.k3m6');

-- Demo riders  (password: rider123)
INSERT IGNORE INTO riders (rider_code, name, email, phone, password_hash, status, rating) VALUES
('RDR2456', 'Jean Claude',  'jean.claude@forkfresh.cm',  '+237678123456',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkb.k3m6', 'online',  4.80),
('RDR1001', 'Marie Ngassa', 'marie.ngassa@forkfresh.cm', '+237699001122',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkb.k3m6', 'offline', 4.60);

-- Hero image
INSERT IGNORE INTO hero_settings (id, image_url, alt_text) VALUES
(1, 'assets/images/IMG_8063.PNG', 'Authentic African dish');

-- About gallery
INSERT IGNORE INTO about_gallery (image_url, alt_text, sort_order) VALUES
('assets/images/achu.jpg',    'Achu Soup',      1),
('assets/images/eru.jpg',     'Fufu and Eru',   2),
('assets/images/jollof.jpg',  'Jollof Rice',    3);

-- Categories
INSERT IGNORE INTO categories (id, name, slug, image_url, sort_order) VALUES
(1, 'Fresh Food',               'fresh-food',   'assets/images/fresh.jpg',   1),
(2, 'Frozen Food',              'frozen-food',  'assets/images/frozen.jpg',  2),
(3, 'Drinks & Smoothies',       'drinks',       'assets/images/drinks.jpg',  3),
(4, 'Meal Plans',               'meal-plans',   'assets/images/mealplan.jpg',4);

-- Products
INSERT IGNORE INTO products (id, category_id, name, slug, description, price, image_url, is_featured, is_popular, is_available) VALUES
(1, 1, 'Ndolé with Plantain',     'ndole-plantain',   'A rich Cameroonian bitter-leaf stew served with plantain.',       2000, 'assets/images/NDOLE.jpg',  1, 1, 1),
(2, 1, 'Jollof Rice and Chicken', 'jollof-chicken',   'Party-style jollof rice with grilled chicken.',                   1500, 'assets/images/jollof.jpg', 1, 1, 1),
(3, 1, 'Achu Soup',               'achu-soup',        'Traditional yellow Achu soup with cocoyam fufu.',                 2500, 'assets/images/achu.jpg',   1, 1, 1),
(4, 1, 'Fufu and Eru',            'fufu-eru',         'Water fufu paired with lush eru vegetable stew.',                 1000, 'assets/images/Eru1.jpg',   1, 1, 1),
(5, 1, 'Koki Beans',              'koki-beans',       'Steamed bean pudding wrapped in banana leaves.',                  1200, 'assets/images/koki.jpg',   1, 1, 1),
(6, 1, 'Pepper Soup',             'pepper-soup',      'Spicy and aromatic pepper soup – fish or goat meat.',             1800, 'assets/images/pepper.jpg', 1, 1, 1),
(7, 2, 'Frozen Eru Pack',         'frozen-eru',       'Pre-cooked eru, ready to heat and serve.',                        2200, 'assets/images/Eru1.jpg',   0, 0, 1),
(8, 2, 'Frozen Jollof Rice',      'frozen-jollof',    '500 g portion of frozen jollof rice.',                            1800, 'assets/images/jollof.jpg', 0, 0, 1),
(9, 3, 'Baobab Juice',            'baobab-juice',     'Refreshing baobab fruit juice, naturally sweet.',                 800,  'assets/images/detox.jpg',  0, 1, 1),
(10,3, 'Green Detox Smoothie',    'green-detox',      'Blended spinach, cucumber, lemon and ginger.',                    1000, 'assets/images/detox.jpg',  0, 1, 1);

-- Meal plan templates
INSERT IGNORE INTO meal_plan_templates (name, description, price, billing_cycle, meals_per_day, days_per_week, meal_type, is_popular) VALUES
('Weekly Meal Plan',          'Healthy meals for the week',          13000.00, 'week',  2, 7, 'Standard meals',      0),
('Monthly Meal Plan',         'Healthy meals for the month',         35000.00, 'month', 2, 7, 'Balanced meals',      1),
('Weight Loss Plan',          'Low calories, high nutrition',        15000.00, 'week',  2, 6, 'High nutrition meals', 0),
('Diabetic Friendly Plan',    'Balanced meals for diabetics',        15000.00, 'week',  2, 7, 'Balanced meals',      0);

-- Food preference items
INSERT IGNORE INTO food_preference_items (name) VALUES
('Chicken'),('Beef'),('Pork'),('Potato'),('Vegetables');

-- Allergen items
INSERT IGNORE INTO allergen_items (name) VALUES
('Groundnut'),('Wheat'),('Soy Beans'),('Dairy'),('Eggs'),
('Fish'),('Sesame'),('Sulphites'),('Shellfish'),('Other');

-- Demo orders for rider dashboard
INSERT IGNORE INTO orders (id, order_number, user_id, rider_id, subtotal, delivery_fee, total_amount,
    order_status, customer_name, customer_phone, restaurant_name, dropoff_address,
    estimated_minutes, placed_at) VALUES
(1, 'FF125679', 1, 1, 3500, 1000, 4500, 'out_for_delivery',
    'Alice Fon',  '+237677001122', 'La Delice Buea', 'Molyko, Buea',    30, '2026-07-30 14:30:00'),
(2, 'FF125687', 1, 1, 5200, 1000, 6200, 'assigned',
    'Bob Etame',  '+237677334455', 'La Delice Buea', 'Great Soppo, Buea', 25, NOW()),
(3, 'FF125690', 1, NULL, 2800, 1000, 3800, 'pending',
    'Clara Manga','+237677556677', 'Chicken Republic','Mile 17, Buea',  40, NOW());
