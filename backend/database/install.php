<?php

// install.php  –  One-time database installer
// Visit: http://localhost/ForkFresh/backend/database/install.php
// DELETE this file after running it in production!


// Only allow from localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Access denied.');
}

// ── Connection settings 
$host    = '127.0.0.1';
$user    = 'root';
$pass    = '';          // XAMPP default
$charset = 'utf8mb4';

try {
    // Connect WITHOUT a database first (to create it)
    $pdo = new PDO(
        "mysql:host=$host;charset=$charset",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS forkfresh
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE forkfresh");

    echo "<pre>\n✅ Database 'forkfresh' ready.\n\n";

    // Read and split schema into individual statements
    $sql = file_get_contents(__DIR__ . '/schema.sql');

    // Remove the USE statement (already selected above)
    $sql = preg_replace('/^USE\s+\w+\s*;/mi', '', $sql);

    // Split on semicolons (crude but reliable for our schema)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => strlen($s) > 10
    );

    $ok = 0; $skip = 0;
    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
            $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 80);
            echo "  ✔ $preview…\n";
            $ok++;
        } catch (PDOException $e) {
            // "already exists" errors are fine; print others
            if (str_contains($e->getMessage(), 'already exists') ||
                str_contains($e->getMessage(), 'Duplicate entry')) {
                $skip++;
            } else {
                echo "  ⚠ " . $e->getMessage() . "\n";
                echo "    SQL: " . substr($stmt, 0, 120) . "\n";
            }
        }
    }

    echo "\n✅ Done — $ok statements executed, $skip skipped (already exist).\n";
    echo "\n⚠️  DELETE this file now: backend/database/install.php\n</pre>";

} catch (PDOException $e) {
    echo "<pre>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Check that MySQL is running in XAMPP Control Panel.</pre>";
}
