<?php
/**
 * ForkFresh – Payment Page
 */
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$userId    = getCurrentUserId();
$db        = getDB();
$addressId = (int)($_GET['address_id'] ?? 1);

// Fetch cart from DB
$stmt = $db->prepare('
    SELECT ci.product_id AS id, ci.quantity, p.name, p.price
    FROM cart_items ci JOIN products p ON ci.product_id=p.id
    WHERE ci.user_id=?
');
$stmt->execute([$userId]);
$cartItems      = $stmt->fetchAll();
$totalItemsCount = array_sum(array_column($cartItems, 'quantity'));
$subtotal        = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$deliveryFee     = $totalItemsCount > 0 ? 1000.00 : 0.00;
$totalAmount     = $subtotal + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod    = $_POST['payment_method']    ?? '';
    $cardholderName   = $_POST['cardholder_name']   ?? null;
    $cardNumber       = $_POST['card_number']        ?? null;
    $cardExpiry       = $_POST['card_expiry']        ?? null;
    $momoPhone        = $_POST['momo_phone_number']  ?? null;
    $momoCarrier      = $_POST['momo_carrier']       ?? null;
    $riderNote        = $_POST['rider_note']         ?? null;
    $cardMasked       = $cardNumber ? '****-****-****-' . substr(preg_replace('/\D/', '', $cardNumber), -4) : null;

    if (!empty($cartItems) && $paymentMethod) {
        // Create order
        $orderNumber = 'FF' . strtoupper(substr(uniqid(), -6));
        $ins = $db->prepare('
            INSERT INTO orders (order_number,user_id,address_id,subtotal,delivery_fee,total_amount,delivery_time_type,rider_note,order_status)
            VALUES (?,?,?,?,?,?,?,?,?)
        ');
        $ins->execute([$orderNumber, $userId, $addressId, $subtotal, $deliveryFee, $totalAmount, 'ASAP', $riderNote, 'pending']);
        $orderId = $db->lastInsertId();

        // Insert order items
        $itemStmt = $db->prepare('INSERT INTO order_items (order_id,product_id,quantity,unit_price,total_price) VALUES (?,?,?,?,?)');
        foreach ($cartItems as $item) {
            $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price'], $item['price']*$item['quantity']]);
        }

        // Insert payment
        $payStmt = $db->prepare('INSERT INTO payments (order_id,payment_method,cardholder_name,card_number_masked,card_expiry,momo_phone_number,momo_carrier,payment_status,paid_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
        $payStmt->execute([$orderId, $paymentMethod, $cardholderName, $cardMasked, $cardExpiry, $momoPhone, $momoCarrier, 'Successful']);

        // Clear cart
        $db->prepare('DELETE FROM cart_items WHERE user_id=?')->execute([$userId]);

        redirect(BASE_URL . 'customer/order-confirmation.php?order_id=' . $orderId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment – ForkFresh</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#f7f5f0;font-family:'Segoe UI',Arial,sans-serif;color:#333;padding:10px;}
    .page-wrap{max-width:1100px;margin:20px auto;background:#fdfbf7;border:1px solid #e0d8c5;border-radius:10px;padding:28px;}
    .co-logo img{height:36px;width:auto;}
    .tagline{font-size:.75rem;color:#888;margin-bottom:18px;}
    .steps{display:flex;gap:24px;border-bottom:1px solid #ddd;padding-bottom:10px;margin-bottom:22px;}
    .step{font-weight:700;font-size:.88rem;color:#888;padding-bottom:8px;}
    .step.active{color:#2e7d32;border-bottom:2px solid #2e7d32;}
    .co-grid{display:flex;gap:28px;align-items:flex-start;}
    .pay-col{flex:2;}
    @media(max-width:800px){.co-grid{flex-direction:column;} .sum-col{width:100%;}}
    .pay-card{background:#fff;border:1px solid #e0d8c5;border-radius:8px;padding:18px;margin-bottom:14px;}
    .pay-card label{display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-weight:700;margin-bottom:12px;}
    .fg{margin-bottom:12px;}
    .fg label{display:block;font-size:.82rem;font-weight:600;margin-bottom:4px;color:#555;}
    .fg input,.fg select{width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:.88rem;font-family:inherit;}
    .fg input:focus{outline:none;border-color:#2e7d32;}
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .sum-col{width:340px;flex-shrink:0;}
    .sum-box{background:#faf8f3;border:1px solid #e0d8c5;border-radius:8px;padding:20px;}
    .sum-box h3{font-size:1rem;font-weight:700;margin-bottom:14px;}
    .sum-row{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:9px;color:#555;}
    .sum-row.total{font-weight:700;color:#000;font-size:.95rem;margin-top:10px;}
    .btn-pay{display:block;width:100%;padding:12px;background:#104813;color:#fff;border:none;border-radius:6px;font-weight:700;font-size:.95rem;cursor:pointer;margin-top:18px;transition:background .2s;}
    .btn-pay:hover{background:#0b3410;}
    .btn-pay.disabled{background:#ccc;cursor:not-allowed;pointer-events:none;}
    .promo-row{display:flex;gap:6px;margin-top:14px;}
    .promo-row input{flex:1;padding:8px 10px;border:1px solid #ccc;border-radius:4px;font-size:.82rem;}
    .promo-row button{padding:8px 14px;background:#2e7d32;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.82rem;}
    .back-link{font-size:.82rem;color:#2e7d32;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:18px;}
    .back-link:hover{text-decoration:underline;}
  </style>
</head>
<body>
<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
    <div class="co-logo">
      <a href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/images/IMG_8023.PNG" alt="ForkFresh"
             onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.2rem;color:#2e7d32\'>fork<span style=\'color:#f57c00\'>fresh</span></span>'">
      </a>
    </div>
  </div>
  <p class="tagline">AFRICAN DELICACIES, DELIVERED FRESH</p>
  <a href="checkout.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Address</a>

  <div class="steps">
    <div class="step">1. Address</div>
    <div class="step active">2. Payment</div>
    <div class="step">3. Review</div>
  </div>

  <div style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">Payment Methods</div>

  <div class="co-grid">
    <div class="pay-col">
      <form method="POST" action="payment.php?address_id=<?= $addressId ?>" id="paymentForm">
        <!-- Card -->
        <div class="pay-card">
          <label><span><input type="radio" name="payment_method" value="Credit/Debit Card" checked style="margin-right:8px;"> Credit / Debit Card</span> <span style="font-size:1.3rem;">💳</span></label>
          <div class="fg"><label>Cardholder Name</label><input type="text" name="cardholder_name" placeholder="Name on card"></div>
          <div class="fg"><label>Card Number</label><input type="text" name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19"></div>
          <div class="two-col">
            <div class="fg"><label>Expiry (MM/YY)</label><input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5"></div>
            <div class="fg"><label>CVV</label><input type="text" name="cvv" placeholder="CVV" maxlength="4"></div>
          </div>
          <label style="font-size:.83rem;"><input type="checkbox" name="save_card" value="1"> Save this card for future use</label>
        </div>
        <!-- MoMo -->
        <div class="pay-card">
          <label><span><input type="radio" name="payment_method" value="Mobile Money" style="margin-right:8px;"> Mobile Money</span> <span style="font-size:1.1rem;">📱</span></label>
          <div class="two-col">
            <div class="fg"><label>Phone Number</label><input type="tel" name="momo_phone_number" placeholder="6XXXXXXXX" maxlength="9" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,9)"></div>
            <div class="fg"><label>Carrier</label><select name="momo_carrier"><option value="" disabled selected>Select</option><option value="MTN">MTN</option><option value="Orange">Orange</option></select></div>
          </div>
        </div>
        <!-- Rider note -->
        <div class="pay-card">
          <label style="font-size:.88rem;"><input type="checkbox" id="noteToggle"> Note for rider</label>
          <div id="noteBox" style="display:none;margin-top:10px;">
            <input type="text" name="rider_note" placeholder="Add a note for the rider (optional)">
          </div>
        </div>
      </form>
    </div>

    <!-- Summary -->
    <div class="sum-col">
      <div class="sum-box">
        <h3>Order Summary</h3>
        <div style="max-height:140px;overflow-y:auto;margin-bottom:10px;">
          <?php if ($totalItemsCount > 0): ?>
          <?php foreach ($cartItems as $item): ?>
          <div class="sum-row" style="font-size:.82rem;"><span><?= e($item['name']) ?> ×<?= $item['quantity'] ?></span><span><?= formatPrice((float)$item['price']*$item['quantity']) ?></span></div>
          <?php endforeach; ?>
          <?php else: ?>
          <p style="font-size:.83rem;color:#888;text-align:center;padding:10px 0;">Cart is empty</p>
          <?php endif; ?>
        </div>
        <hr style="border:none;border-top:1px solid #ddd;margin:8px 0;">
        <div class="sum-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
        <div class="sum-row"><span>Delivery fee</span><span><?= $totalItemsCount > 0 ? formatPrice($deliveryFee) : '—' ?></span></div>
        <hr style="border:none;border-top:1px solid #ddd;margin:8px 0;">
        <div class="sum-row total"><span>Total</span><span><?= formatPrice($totalAmount) ?></span></div>
        <div class="promo-row"><input type="text" placeholder="Promo code (optional)"><button type="button">Apply</button></div>
        <button type="submit" form="paymentForm" class="btn-pay <?= $totalItemsCount===0?'disabled':'' ?>">Pay Now</button>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('noteToggle').addEventListener('change', function(){
  document.getElementById('noteBox').style.display = this.checked ? 'block' : 'none';
});
</script>
</body>
</html>
