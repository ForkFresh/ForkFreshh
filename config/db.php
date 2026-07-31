<?php
/**
 * ForkFresh – Central Database Connection
 * Single PDO connection shared by the entire application.
 * All branches previously had their own db.php; this replaces them all.
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'forkfresh');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Change to your MySQL password if set
define('DB_CHARSET', 'utf8mb4');

// ── Base URL (auto-detected) ──────────────────────────────────────────────────
if (!defined('BASE_URL')) {
    $scheme  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    // Walk up the path until we reach the project root (contains index.php)
    $root    = rtrim(dirname($script), '/\\');
    // If we are inside a sub-folder (admin/, customer/, rider/) go one level up
    $parts   = explode('/', trim($root, '/'));
    $subDirs = ['admin', 'customer', 'rider'];
    if (in_array(end($parts), $subDirs, true)) {
        array_pop($parts);
        $root = '/' . implode('/', $parts);
    }
    define('BASE_URL', $scheme . '://' . $host . rtrim($root, '/') . '/');
}

/**
 * Returns a singleton PDO instance.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[ForkFresh DB] ' . $e->getMessage());
            die('<p style="font-family:sans-serif;color:#c00;padding:20px;">
                 Database connection failed. Check your MySQL server and config/db.php settings.</p>');
        }
    }
    return $pdo;
}

// Also expose $pdo as a global variable (legacy support for pages that use $pdo directly)
$pdo = getDB();

// ── Session helper ────────────────────────────────────────────────────────────
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── Auth helpers ──────────────────────────────────────────────────────────────
function getCurrentUserId(): int
{
    startSession();
    return (int)($_SESSION['user_id'] ?? 0);
}

function getCurrentUser(): array
{
    $id = getCurrentUserId();
    if ($id === 0) return [];
    $stmt = getDB()->prepare('SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: [];
}

function getCurrentRiderId(): int
{
    startSession();
    return (int)($_SESSION['rider_id'] ?? 0);
}

function getCurrentAdminId(): int
{
    startSession();
    return (int)($_SESSION['admin_id'] ?? 0);
}

/**
 * Require customer login – redirect to login page if not authenticated.
 */
function requireCustomer(): void
{
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php?role=customer');
        exit;
    }
}

/**
 * Require rider login.
 */
function requireRider(): void
{
    startSession();
    if (empty($_SESSION['rider_id'])) {
        header('Location: ' . BASE_URL . 'login.php?role=rider');
        exit;
    }
}

/**
 * Require admin login.
 */
function requireAdmin(): void
{
    startSession();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . 'login.php?role=admin');
        exit;
    }
}

// ── Output helpers ────────────────────────────────────────────────────────────
/**
 * HTML-escape a string for safe output.
 */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Alias used in customer branch
function h(string $v): string { return e($v); }

/**
 * Format a price as FCFA.
 */
function formatPrice(float $price): string
{
    return number_format($price, 0, '.', ',') . ' FCFA';
}

// Alias used in customer branch
function fcfa(float $amount): string { return 'FCFA ' . number_format($amount, 0, '.', ','); }

// ── JSON / redirect helpers ───────────────────────────────────────────────────
function jsonResponse(bool $success, string $message, array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
