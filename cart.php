<?php
// Start session to manage cart state dynamically
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    // Set to empty array to show empty state, or add dummy items for testing:
    $_SESSION['cart'] = []; 
}

// Handle item removal or quantity updates if requested via POST/GET
if (isset($_GET['action'])) {
    $id = $_GET['id'] ?? null;
    if ($_GET['action'] == 'remove' && $id !== null) {
        unset($_SESSION['cart'][$id]);
    } elseif ($_GET['action'] == 'update' && $id !== null) {
        $qty = intval($_GET['qty']);
        if ($qty > 0) {
            $_SESSION['cart'][$id]['quantity'] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit();
}

// Calculate totals
$cart_items = $_SESSION['cart'];
$item_count = count($cart_items);
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_fee = ($item_count > 0) ? 1000 : 0; // FCFA 1000 delivery fee if items exist
$total = $subtotal + ($item_count > 0 ? $delivery_fee : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ForkFresh - Cart</title>
</head>
<body>

<div class="dashboard-container">
    
    <div class="main-layout">
        
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
        <img src="IMG_0720.jpg" alt="ForkFresh Logo" style="height: 35px; width: auto;">
    </a>
</div>
            <ul class="nav-links">
                <li><a href="home.php">HOME</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="subscriptions.php">Subscriptions</a></li>
                <li><a href="addresses.php">Addresses</a></li>
                <li><a href="categories.php">Catergories</a></li>
                <li><a href="cart.php" class="active-cart">Cart</a></li>
            </ul>
        </div>

        <!-- Right Content Area -->
        <div class="content-area">
            <div class="cart-title">My Cart (<?php echo $item_count; ?> items)</div>
            
            <div class="cart-grid">
                
                <?php if ($item_count > 0): ?>
                    <!-- Filled State: List of Orders -->
                    <div class="orders-container">
                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="order-card">
                                <div class="item-info-group">
                                    <div class="item-image"></div>
                                    <div class="item-details">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p>FCFA <?php echo number_format($item['price']); ?></p>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="qty-controls">
                                        <a href="cart.php?action=update&id=<?php echo $id; ?>&qty=<?php echo $item['quantity'] - 1; ?>" style="text-decoration:none; padding: 2px 8px; color:#333;">-</a>
                                        <span><?php echo $item['quantity']; ?></span>
                                        <a href="cart.php?action=update&id=<?php echo $id; ?>&qty=<?php echo $item['quantity'] + 1; ?>" style="text-decoration:none; padding: 2px 8px; color:#333;">+</a>
                                    </div>
                                    <div class="item-total-price">
                                        FCFA <?php echo number_format($item['price'] * $item['quantity']); ?>
                                    </div>
                                    <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="remove-btn">×</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State Box -->
                    <div class="orders-box-empty">
                        <div class="empty-icon">🛒</div>
                        <div class="empty-text">Your cart is currently empty.</div>
                        <a href="categories.php" class="shop-now-btn">Start Shopping</a>
                    </div>
                <?php endif; ?>

                <!-- Order Summary Card -->
                <div class="order-summary">
                    <div class="summary-title">Order Summary</div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>FCFA <?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery fee</span>
                        <span><?php echo $item_count > 0 ? 'FCFA ' . number_format($delivery_fee) : '—'; ?></span>
                    </div>
                    <hr class="summary-divider">
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>FCFA <?php echo number_format($total); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="checkout-btn <?php echo $item_count === 0 ? 'disabled' : ''; ?>">Proceed to Checkout</a>
                </div>

            </div>
        </div>
        
    </div>
<?php include 'footer.php'; ?>
</div>

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
        background-color: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        color: #333;
    }
    .dashboard-container {
        width: 1200px;
        background: #f9f8f4;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    /* Main Layout Grid */
    .main-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 550px;
    }

    /* Sidebar Styling */
    .sidebar {
        background: #f9f8f4;
        padding: 30px 20px;
        border-right: 1px solid #eae5de;
    }
    .logo {
        font-size: 24px;
        font-weight: bold;
        color: #d35400;
        margin-bottom: 5px;
    }
    .logo span {
        color: #27ae60;
    }
    .logo-sub {
        font-size: 9px;
        color: #666;
        margin-bottom: 40px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .nav-links {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .nav-links a {
        text-decoration: none;
        color: #333;
        font-size: 15px;
        display: block;
    }
    .nav-links a.active-cart {
        background-color: #27ae60;
        color: white;
        padding: 10px 15px;
        border-radius: 6px;
        font-weight: 500;
    }

    /* Content Area */
    .content-area {
        padding: 30px 40px;
        display: flex;
        flex-direction: column;
    }
    .cart-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 25px;
        color: #222;
    }
    
    .cart-grid {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 25px;
        flex-grow: 1;
    }

    /* Orders Container */
    .orders-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 5px;
    }

    /* Single Order Item Card */
    .order-card {
        background: #ffffff;
        border: 1px solid #e0dbd1;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }
    .item-info-group {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .item-image {
        width: 70px;
        height: 55px;
        background-color: #dbe4ef;
        border-radius: 6px;
        object-fit: cover;
    }
    .item-details h4 {
        font-size: 14px;
        font-weight: 600;
        color: #222;
        margin-bottom: 4px;
    }
    .item-details p {
        font-size: 12px;
        color: #555;
    }
    
    /* Quantity Controls */
    .qty-controls {
        display: flex;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
    }
    .qty-controls button {
        background: #f4f4f4;
        border: none;
        padding: 4px 8px;
        cursor: pointer;
        font-size: 12px;
        color: #333;
    }
    .qty-controls button:hover {
        background: #e0e0e0;
    }
    .qty-controls span {
        padding: 0 10px;
        font-size: 12px;
        font-weight: 500;
    }

    .item-total-price {
        font-size: 13px;
        font-weight: 600;
        color: #222;
        min-width: 80px;
        text-align: right;
    }
    
    .remove-btn {
        color: #999;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        margin-left: 5px;
    }
    .remove-btn:hover {
        color: #e74c3c;
    }

    /* Empty Orders Box */
    .orders-box-empty {
        background: #ffffff;
        border: 1px solid #e0dbd1;
        border-radius: 8px;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 320px;
    }
    .empty-icon {
        font-size: 42px;
        color: #ccc;
        margin-bottom: 12px;
    }
    .empty-text {
        font-size: 15px;
        color: #666;
        margin-bottom: 18px;
    }
    .shop-now-btn {
        background-color: #27ae60;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    .shop-now-btn:hover {
        background-color: #219653;
    }

    /* Order Summary Card */
    .order-summary {
        background: #ffffff;
        border: 1px solid #e0dbd1;
        border-radius: 8px;
        padding: 20px;
        height: fit-content;
    }
    .summary-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-bottom: 12px;
        color: #555;
    }
    .summary-divider {
        border: none;
        border-top: 1px solid #eae5de;
        margin: 15px 0;
    }
    .summary-total {
        font-weight: bold;
        color: #000;
        font-size: 15px;
    }
    
    .checkout-btn {
        width: 100%;
        background-color: #10522c;
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 15px;
        text-align: center;
        text-decoration: none;
        display: block;
    }
    .checkout-btn:hover {
        background-color: #10522c;
    }
    .checkout-btn.disabled {
        background-color: #d0d0d0;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Footer Styling */
    .footer {
        background-color: #10522c;
        color: white;
        padding: 25px 40px;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
        gap: 20px;
        font-size: 13px;
    }
    .footer-brand-title {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
    }
    .footer-brand-desc {
        font-size: 12px;
        line-height: 1.4;
        max-width: 220px;
    }
    .footer-col h4 {
        font-size: 13px;
        margin-bottom: 10px;
        font-weight: 600;
    }
    .footer-col ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .footer-col ul li a {
        color: white;
        text-decoration: none;
        opacity: 0.9;
    }
    .footer-col ul li a:hover {
        opacity: 1;
        text-decoration: underline;
    }
</style>

</body>
</html>