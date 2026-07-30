# ForkFresh – Customer Module Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Apache or Nginx with `mod_rewrite` (XAMPP / WAMP / Laragon all work)

---

## 1. Database Setup

1. Open **phpMyAdmin** (or any MySQL client)
2. Run the SQL file:
   ```
   database/forkfresh_schema.sql
   ```
   This creates the `forkfresh` database and all tables, and seeds:
   - 4 default meal plan templates
   - Food preference items (Chicken, Beef, Pork, Potato, Vegetables)
   - Allergen items (Groundnut, Wheat, Soy Beans, Dairy, Eggs, Fish, Sesame, Sulphites, Shellfish, Other)
   - A demo user: **pauline@forkfresh.com** / password: **password123**

---

## 2. Configure Database Credentials

Edit `includes/db.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'forkfresh');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
```

---

## 3. Web Server Setup

Place the project folder inside your web server root, e.g.:
- **XAMPP**: `C:/xampp/htdocs/forkfresh.9888/`
- **Laragon**: `C:/laragon/www/forkfresh.9888/`

Then access:
```
http://localhost/forkfresh.9888/customer/dashboard.php
```

---

## 4. File Structure

```
forkfresh.9888/
├── api/
│   ├── subscription-handler.php   ← Handles subscribe/pause/resume/cancel
│   └── meal-plan-handler.php      ← Handles save_preferences (custom plans)
├── customer/
│   ├── dashboard.php              ← Customer home dashboard
│   ├── meal-plans.php             ← Browse & choose meal plans
│   ├── manage-subscription.php    ← View/manage active subscription
│   ├── customize-meal-plan.php    ← 3-step preference wizard
│   ├── assets/
│   │   ├── css/style.css          ← All styles (no Bootstrap)
│   │   ├── js/app.js              ← Shared JS (sidebar, toast, chips)
│   │   ├── js/customize-wizard.js ← Wizard step logic
│   │   └── images/                ← Place your food images here
│   └── partials/
│       ├── sidebar.php            ← Shared sidebar navigation
│       └── footer.php             ← Shared footer
├── database/
│   └── forkfresh_schema.sql
├── includes/
│   └── db.php                     ← PDO connection + helper functions
└── SETUP.md
```

---

## 5. User Flow

```
dashboard.php
  │
  ├── Sidebar: "Meal Plans"
  │     └── meal-plans.php
  │           ├── "Choose plan" → POST api/subscription-handler.php (subscribe)
  │           │     └── Redirects to manage-subscription.php
  │           └── "Create your own plan" → customize-meal-plan.php?mode=create
  │                 ├── Step 1: Diet + Food Preferences + Spice Level
  │                 ├── Step 2: Allergies + Additional Info
  │                 ├── Step 3: Review
  │                 └── "Create My Meal Plan" → POST api/meal-plan-handler.php
  │                       └── Redirects to manage-subscription.php
  │
  └── Sidebar: "My Subscription"
        └── manage-subscription.php
              ├── "Pause Plan"   → POST api/subscription-handler.php (pause)
              ├── "Resume Plan"  → POST api/subscription-handler.php (resume)
              ├── "Change Plan"  → meal-plans.php?change=1
              └── "Cancel"       → POST api/subscription-handler.php (cancel)
```

---

## 6. Images

Place your food images in `customer/assets/images/`. Expected filenames:
- `forkfresh-logo.png`
- `ndole.jpg`, `jollof.jpg`, `achu.jpg`, `fufu.jpg`
- `fresh-food.jpg`, `frozen-food.jpg`, `drinks.jpg`
- `promo-food.jpg`

Images fall back to `placeholder.svg` if not found.

---

## 7. Integration Notes

This module is designed to merge with the rest of the ForkFresh application:
- The `includes/db.php` connection file is shared across all modules
- Session-based authentication: set `$_SESSION['user_id']` on login
- All API endpoints return `{"success": bool, "message": "..."}` JSON
- The sidebar `partials/sidebar.php` uses relative paths — ensure it's included
  from a file in the `customer/` directory
