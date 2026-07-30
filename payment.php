<?php
// Start session to access cart items from cart.php
session_start();

include 'db.php';
$user_id = 1;
$address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : 1;

// Fetch cart items from session instead of hardcoded/database queries if using session cart
$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0;
$total_items_count = 0;
$items_to_order = [];

foreach ($cart_items as $id => $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items_count += $item['quantity'];
    
    // Structure items for database insertion later
    $items_to_order[] = [
        'product_id' => $id,
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'name' => $item['name'],
        'total_price' => $item_total
    ];
}

$delivery_fee = ($total_items_count > 0) ? 1000.00 : 0.00;
$total_amount = $subtotal + $delivery_fee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $cardholder_name = $_POST['cardholder_name'] ?? null;
    $card_number = $_POST['card_number'] ?? null;
    $card_expiry = $_POST['card_expiry'] ?? null;
    $momo_phone_number = $_POST['momo_phone_number'] ?? null;
    $momo_carrier = $_POST['momo_carrier'] ?? null;
    $rider_note = $_POST['rider_note'] ?? null;

    $card_masked = $card_number ? '****-****-****-' . substr($card_number, -4) : null;

    if (count($items_to_order) > 0) {
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, subtotal, delivery_fee, total_amount, delivery_time_type, rider_note, order_status) VALUES (?, ?, ?, ?, ?, 'ASAP', ?, 'Paid')");
        $order_stmt->bind_param("iiddds", $user_id, $address_id, $subtotal, $delivery_fee, $total_amount, $rider_note);
        $order_stmt->execute();
        $order_id = $order_stmt->insert_id;

        foreach ($items_to_order as $item) {
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $item_stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['price'], $item['total_price']);
            $item_stmt->execute();
        }

        $pay_stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, cardholder_name, card_number_masked, card_expiry, momo_phone_number, momo_carrier, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Successful')");
        $pay_stmt->bind_param("issssss", $order_id, $payment_method, $cardholder_name, $card_masked, $card_expiry, $momo_phone_number, $momo_carrier);
        $pay_stmt->execute();

        // Clear session cart after successful order
        unset($_SESSION['cart']);

        header("Location: cart.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - ForkFresh</title>
    <style>
        body { background-color: #f7f5f0; font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; }
        .page-wrapper { max-width: 1200px; margin: 20px auto; background: #fdfbf7; border: 1px solid #e0d8c5; border-radius: 10px; padding: 30px; }
        .header-top { border-bottom: 1px solid #ddd; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #2e7d32; text-transform: lowercase; }
        .form-grid { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; margin-bottom: 5px; color: #444; }
        input[type="text"], input[type="tel"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: #fff; font-size: 14px; box-sizing: border-box; }
        .payment-card-box { background: #fff; border: 1px solid #e0d8c5; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .order-summary-box { background: #faf8f3; border: 1px solid #e0d8c5; padding: 20px; border-radius: 8px; width: 350px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
        .summary-row.total { font-weight: bold; color: #000; font-size: 16px; margin-top: 10px; }
        .action-btn { display: block; width: 100%; background: #104813; color: #fff; text-align: center; padding: 12px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; margin-top: 15px; text-decoration: none; }
        .action-btn.disabled { background: #ccc; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="header-top">
        <div class="logo">
            <a href="address.php" style="text-decoration: none; display: inline-flex; align-items: center;">
                <img src="IMG_0720.jpg" alt="ForkFresh Logo" style="height: 35px; width: auto;">
            </a>
        </div>
    </div>
    
    <div style="font-size: 20px; font-weight: bold; margin-bottom: 12px; color: #333;">Payment Methods</div>

    <!-- Horizontal Step Tracker -->
    <div style="display: flex; gap: 40px; margin-bottom: 25px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
        <div style="font-weight: bold; color: #888;">1. Address</div>
        <div style="font-weight: bold; color: #2e7d32; border-bottom: 2px solid #2e7d32; padding-bottom: 8px;">2. Payment</div>
        <div style="font-weight: bold; color: #888;">3. Review</div>
    </div>

    <!-- Main Layout Side-by-Side -->
    <div style="display: flex; gap: 30px; align-items: flex-start;">
        <div style="flex: 2;">
            <h3 style="margin-bottom: 15px; color: #333;">Payment options</h3>
            <form action="payment.php?address_id=<?php echo $address_id; ?>" method="POST" id="paymentForm">
                
                <div class="payment-card-box">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <span><input type="radio" name="payment_method" value="Credit/Debit Card" checked> <strong>Credit/ Debit Card</strong></span>
                            <span style="font-size: 18px;">💳</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <input type="text" name="cardholder_name" placeholder="Cardholder Name">
                    </div>
                    <div class="form-group">
                        <input type="text" name="card_number" placeholder="Card Number">
                    </div>
                    <div class="form-grid">
                        <input type="text" name="card_expiry" placeholder="Expire Date (MM/YY)">
                        <input type="text" name="cvv" placeholder="CVV">
                    </div>
                    <div class="form-group" style="margin-top: 10px; margin-bottom: 0;">
                        <label style="cursor: pointer;"><input type="checkbox" name="save_card" value="1"> Save this card for future use</label>
                    </div>
                </div>

                <div class="payment-card-box">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <span><input type="radio" name="payment_method" value="Mobile Money"> <strong>Mobile Money</strong></span>
                            <span style="font-size: 16px;">📱</span>
                        </label>
                    </div>
                    <div class="form-grid" style="margin-bottom: 0;">
                        <input type="tel" name="momo_phone_number" placeholder="Phone Number" maxlength="9" pattern="[0-9]{9}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);" title="Please enter exactly 9 digits">
                        <select name="momo_carrier">
                            <option value="" disabled selected>Carrier</option>
                            <option value="MTN">MTN</option>
                            <option value="Orange">Orange</option>
                        </select>
                    </div>
                </div>

                <div class="payment-card-box">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="cursor: pointer;"><input type="checkbox" id="riderNoteToggle"> Note for rider</label>
                    </div>
                    <input type="text" name="rider_note" placeholder="Add a note for the rider (optional)" style="margin-bottom: 0;">
                </div>
            </form>
        </div>

        <div class="order-summary-box">
            <h3>Order Summary</h3>
            
            <!-- Dynamic Items List from Session Cart -->
            <div style="max-height: 140px; overflow-y: auto; margin-bottom: 10px;">
                <?php if ($total_items_count > 0): ?>
                    <?php foreach ($items_to_order as $item): ?>
                        <div class="summary-row" style="font-size: 13px;">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>FCFA <?php echo number_format($item['total_price']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="font-size: 13px; color: #888; text-align: center; padding: 15px 0;">Your cart is empty</div>
                <?php endif; ?>
            </div>

            <hr style="border: none; border-top: 1px solid #ddd; margin: 10px 0;">

            <div class="summary-row">
                <span>Subtotal</span>
                <span>FCFA <?php echo number_format($subtotal); ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery fee</span>
                <span><?php echo $total_items_count > 0 ? 'FCFA ' . number_format($delivery_fee) : '—'; ?></span>
            </div>
            <hr style="border: none; border-top: 1px solid #ddd; margin: 10px 0;">
            <div class="summary-row total">
                <span>Total</span>
                <span>FCFA <?php echo number_format($total_amount); ?></span>
            </div>
            
            <div style="margin-top: 15px; display: flex; gap: 5px;">
                <input type="text" placeholder="Promo code (optional)" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="button" style="padding: 8px 15px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Apply</button>
            </div>

            <button type="submit" form="paymentForm" class="action-btn <?php echo $total_items_count === 0 ? 'disabled' : ''; ?>">Pay</button>
        </div>
    </div>
<?php include 'footer.php'; ?>
</div>
</body>
</html>