<?php

// config/db.php  –  PDO database connection


define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'forkfresh');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance.
 * Throws a RuntimeException on failure (never leaks credentials).
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
            // Log internally, never expose credentials or full trace
            error_log('[ForkFresh DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Check server logs.');
        }
    }

    return $pdo;
}
