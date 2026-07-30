-- ForkFresh Database Schema
-- Run this file to set up all required tables

CREATE DATABASE IF NOT EXISTS forkfresh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forkfresh;

-- --------------------------------------------------------
-- Users table (customers)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Meal plan templates (predefined plans)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_plan_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('week','month') NOT NULL DEFAULT 'week',
    meals_per_day INT NOT NULL DEFAULT 2,
    days_per_week INT NOT NULL DEFAULT 7,
    meal_type VARCHAR(100) DEFAULT 'Standard meals',
    is_popular TINYINT(1) DEFAULT 0,
    is_custom TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default meal plan templates
INSERT INTO meal_plan_templates (name, description, price, billing_cycle, meals_per_day, days_per_week, meal_type, is_popular) VALUES
('Weekly Meal Plan',    'Healthy meals for the week',      13000.00, 'week',  2, 7, 'Standard meals',     0),
('Monthly Meal Plan',   'Healthy meals for the month',     35000.00, 'month', 2, 7, 'Balanced meals',     1),
('Weight Loss Plan',    'Low calories, high nutrition',    15000.00, 'week',  2, 6, 'High nutrition meals',0),
('Diabetic Friendly Plan','Balanced meals for diabetics',  15000.00, 'week',  2, 7, 'Balanced meals',     0);

-- --------------------------------------------------------
-- Customer subscriptions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    template_id INT UNSIGNED DEFAULT NULL,         -- NULL if custom plan
    plan_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('week','month') NOT NULL DEFAULT 'week',
    meals_per_day INT NOT NULL DEFAULT 2,
    days_per_week INT NOT NULL DEFAULT 7,
    meal_type VARCHAR(100) DEFAULT 'Standard meals',
    status ENUM('active','paused','cancelled') NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    next_billing_date DATE NOT NULL,
    total_spent DECIMAL(12,2) DEFAULT 0.00,
    is_custom TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES meal_plan_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Custom meal plan preferences (linked to subscription)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_plan_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    diet_preference ENUM('high_protein','no_salt','vegetarian','no_maggi','balanced') NOT NULL DEFAULT 'balanced',
    spice_level ENUM('no_spice','mild','medium','hot','extra_hot') NOT NULL DEFAULT 'medium',
    additional_info TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Food preferences (many-to-many via junction table)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_preference_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO food_preference_items (name) VALUES ('Chicken'),('Beef'),('Pork'),('Potato'),('Vegetables');

CREATE TABLE IF NOT EXISTS preference_food_items (
    preference_id INT UNSIGNED NOT NULL,
    food_item_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (preference_id, food_item_id),
    FOREIGN KEY (preference_id) REFERENCES meal_plan_preferences(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_preference_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Allergen items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS allergen_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO allergen_items (name) VALUES
('Groundnut'),('Wheat'),('Soy Beans'),('Dairy'),('Eggs'),('Fish'),('Sesame'),('Sulphites'),('Shellfish'),('Other');

CREATE TABLE IF NOT EXISTS preference_allergens (
    preference_id INT UNSIGNED NOT NULL,
    allergen_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (preference_id, allergen_id),
    FOREIGN KEY (preference_id) REFERENCES meal_plan_preferences(id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES allergen_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Upcoming deliveries
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    delivery_date DATE NOT NULL,
    meal_description VARCHAR(255) DEFAULT 'Breakfast & Lunch',
    status ENUM('scheduled','delivered','skipped') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Orders table (referenced from dashboard sidebar)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','confirmed','preparing','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Demo user (password: password123)
-- --------------------------------------------------------
INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES
('Pauline', 'Demo', 'pauline@forkfresh.com', '+237600000000',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkb.k3m6');
