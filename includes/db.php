<?php
/**
 * ForkFresh Database Connection
 * Uses PDO for secure, prepared-statement queries
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'forkfresh');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

/**
 * Start session safely
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Return currently logged-in user ID (from session)
 * Falls back to demo user ID 1 for development
 */
function getCurrentUserId(): int {
    startSession();
    return (int)($_SESSION['user_id'] ?? 1);
}

/**
 * Fetch current user row
 */
function getCurrentUser(): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?');
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch() ?: [];
}

/**
 * Sanitise output for HTML context
 */
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format currency in FCFA
 */
function fcfa(float $amount): string {
    return 'FCFA ' . number_format($amount, 0, '.', ',');
}

/**
 * JSON response helper
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
