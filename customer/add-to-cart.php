<?php
/**
 * ForkFresh – Add to Cart handler
 * Accepts POST from any product page; stores in DB cart_items.
 */
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));
$redirect  = $_POST['redirect'] ?? BASE_URL . 'categories.php';

if ($productId > 0) {
    $userId = getCurrentUserId();
    $db     = getDB();

    // Check product exists
    $chk = $db->prepare('SELECT id FROM products WHERE id=? AND is_active=1 LIMIT 1');
    $chk->execute([$productId]);

    if ($chk->fetch()) {
        // Upsert: if already in cart, increment quantity
        $stmt = $db->prepare('
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ');
        $stmt->execute([$userId, $productId, $qty]);
    }
}

header('Location: ' . $redirect);
exit;
