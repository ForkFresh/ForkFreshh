<?php
// Include your database connection (e.g., db.php)
include 'db.php';

// Get the order ID from the URL and sanitize it
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order = null;
$order_items = [];

if ($order_id > 0) {
    // 1. Fetch main order details
    $order_query = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();

    // 2. Fetch the items tied to this order
    $items_query = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $items_query->bind_param("i", $order_id);
    $items_query->execute();
    $order_items = $items_query->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Summary</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if ($order): ?>
            <h1>Thank You for Your Order!</h1>
            <p>Your order reference is: <strong>#<?php echo $order['id']; ?></strong></p>
            
            <div class="summary-box">
                <h3>Order Summary</h3>
                <ul class="item-list">
                    <?php while ($item = $order_items->fetch_assoc()): ?>
                        <li>
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <hr>
                <p class="total"><strong>Total Paid:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>
        <?php else: ?>
            <p>Sorry, no order found. Please check your link.</p>
        <?php endif; ?>
    </div>
</body>
</html>