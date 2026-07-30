<?php
/* line 3 to 7 is the connection configuration
the database doesnt have a password and the 
utf8mb4 charset supports emojis and international characters*/
define('DB_HOST', 'localhost');
define('DB_NAME', 'forkfresh');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* line 9 to 27 opens a secure connection to the database
the try block attempts to instantiate a new PDO(PHP Data Objects)
using constants
lines 14 to 17 */
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production replace with a friendly error page
    die('<p style="color:red;font-family:sans-serif;padding:20px;">
         Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatPrice(float $price): string {
    return number_format($price, 0, '.', ',') . ' FCFA';
}

define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
