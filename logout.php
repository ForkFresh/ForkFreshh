<?php
require_once __DIR__ . '/config/db.php';
startSession();
session_unset();
session_destroy();
// Redirect to the right login tab based on who was logged in
$role = $_GET['role'] ?? 'customer';
header('Location: ' . BASE_URL . 'login.php?role=' . urlencode($role));
exit;
