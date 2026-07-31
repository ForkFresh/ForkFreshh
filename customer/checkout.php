<?php
/**
 * ForkFresh – Checkout (Delivery Address) Page
 */
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$userId = getCurrentUserId();
$db     = getDB();

// Fetch cart
$stmt = $db->prepare('
    SELECT ci.product_id AS id, ci.quantity, p.name, p.price
    FROM cart_items ci JOIN products p ON ci.product_id=p.id
    WHERE ci.user_id=?
');
$stmt->execute([$userId]);
$cartItems   = $stmt->fetchAll();
$totalItems  = array_sum(array_column($cartItems, 'quantity'));
$subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$deliveryFee = $totalItems > 0 ? 1000.00 : 0.00;
$grandTotal  = $subtotal + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = trim($_POST['first_name']     ?? '');
    $lastName    = trim($_POST['last_name']      ?? '');
    $street      = trim($_POST['street_address'] ?? '');
    $apt         = trim($_POST['apartment_suite']?? '');
    $city        = trim($_POST['city']           ?? '');
    $country     = trim($_POST['country']        ?? 'Cameroon');
    $phone       = trim($_POST['phone_number']   ?? '');
    $isSaved     = isset($_POST['is_saved']) ? 1 : 0;

    if ($firstName && $lastName && $street && $city && $phone) {
        $ins = $db->prepare('INSERT INTO addresses (user_id,first_name,last_name,street_address,apartment_suite,city,country,phone_number,is_saved) VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute([$userId,$firstName,$lastName,$street,$apt,$city,$country,$phone,$isSaved]);
        $addressId = $db->lastInsertId();
        redirect(BASE_URL . 'customer/payment.php?address_id=' . $addressId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – ForkFresh</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#f7f5f0;font-family:'Segoe UI',Arial,sans-serif;color:#333;padding:10px;}
    .page-wrap{max-width:1100px;margin:20px auto;background:#fdfbf7;border:1px solid #e0d8c5;border-radius:10px;padding:28px;}
    .co-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
    .co-logo a{text-decoration:none;font-weight:900;font-size:1.3rem;color:#2e7d32;}
    .co-logo span{color:#f57c00;}
    .co-logo img{height:36px;width:auto;}
    .tagline{font-size:.75rem;color:#888;margin-bottom:18px;}
    .steps{display:flex;gap:24px;border-bottom:1px solid #ddd;padding-bottom:10px;margin-bottom:22px;}
    .step{font-weight:700;font-size:.88rem;color:#888;padding-bottom:8px;}
    .step.active{color:#2e7d32;border-bottom:2px solid #2e7d32;}
    .co-grid{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;}
    @media(max-width:860px){.co-grid{grid-template-columns:1fr;}}
    .form-2col{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
    @media(max-width:580px){.form-2col{grid-template-columns:1fr;}}
    .fg{margin-bottom:0;}
    .fg label{display:block;font-size:.82rem;font-weight:600;margin-bottom:4px;color:#555;}
    .fg input,.fg select{width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:.88rem;font-family:inherit;}
    .fg input:focus,.fg select:focus{outline:none;border-color:#2e7d32;}
    .phone-wrap{display:grid;grid-template-columns:1fr 70px;gap:10px;margin-bottom:14px;}
    .del-time{margin:16px 0;}
    .del-time label{display:block;font-weight:700;font-size:.88rem;margin-bottom:8px;}
    .sum-box{background:#faf8f3;border:1px solid #e0d8c5;border-radius:8px;padding:20px;}
    .sum-box h3{font-size:1rem;font-weight:700;margin-bottom:14px;}
    .sum-row{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:9px;color:#555;}
    .sum-row.total{font-weight:700;color:#000;font-size:.95rem;margin-top:10px;}
    .btn-next{display:block;width:100%;padding:12px;background:#2e7d32;color:#fff;border:none;border-radius:6px;font-weight:700;font-size:.95rem;cursor:pointer;margin-top:18px;text-align:center;text-decoration:none;transition:background .2s;}
    .btn-next:hover{background:#1b5e20;}
    .btn-next.disabled{background:#ccc;cursor:not-allowed;pointer-events:none;}
    .promo-row{display:flex;gap:6px;margin-top:14px;}
    .promo-row input{flex:1;padding:8px 10px;border:1px solid #ccc;border-radius:4px;font-size:.82rem;}
    .promo-row button{padding:8px 14px;background:#2e7d32;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.82rem;}
    .warn{background:#fff3cd;border:1px solid #ffeeba;color:#856404;padding:12px 14px;border-radius:6px;margin-bottom:16px;font-size:.85rem;}
    .back-link{font-size:.82rem;color:#2e7d32;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:18px;}
    .back-link:hover{text-decoration:underline;}
  </style>
</head>
<body>
<div class="page-wrap">
  <div class="co-header">
    <div class="co-logo">
      <a href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/images/IMG_8023.PNG" alt="ForkFresh"
             onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.2rem;color:#2e7d32\'>fork<span style=\'color:#f57c00\'>fresh</span></span>'">
      </a>
    </div>
  </div>
  <p class="tagline">AFRICAN DELICACIES, DELIVERED FRESH</p>
  <a href="cart.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Cart</a>

  <div class="steps">
    <div class="step active">1. Address</div>
    <div class="step">2. Payment</div>
    <div class="step">3. Review</div>
  </div>

  <div style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">Delivery Address</div>

  <div class="co-grid">
    <div>
      <?php if ($totalItems === 0): ?>
      <div class="warn"><i class="fa fa-triangle-exclamation"></i> Your cart is empty. <a href="<?= BASE_URL ?>categories.php">Go shopping</a></div>
      <?php endif; ?>

      <form method="POST" action="checkout.php" id="addressForm">
        <div class="form-2col">
          <div class="fg"><label>First Name *</label><input type="text" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>"></div>
          <div class="fg"><label>Last Name *</label><input type="text" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>"></div>
        </div>
        <div class="form-2col">
          <div class="fg"><label>Street Address *</label><input type="text" name="street_address" required value="<?= e($_POST['street_address'] ?? '') ?>"></div>
          <div class="fg"><label>Apartment / Suite</label><input type="text" name="apartment_suite" value="<?= e($_POST['apartment_suite'] ?? '') ?>"></div>
        </div>
        <div class="form-2col" style="margin-bottom:14px;">
          <div class="fg"><label>City *</label><input type="text" name="city" required value="<?= e($_POST['city'] ?? '') ?>"></div>
          <div class="fg"><label>Country</label><select name="country"><option value="Cameroon">Cameroon</option></select></div>
        </div>
        <div class="phone-wrap">
          <div class="fg"><label>Phone Number *</label>
            <input type="tel" name="phone_number" required maxlength="9" pattern="[0-9]{9}"
                   placeholder="6XXXXXXXX" value="<?= e($_POST['phone_number'] ?? '') ?>"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,9)">
          </div>
          <div class="fg"><label style="visibility:hidden;">+237</label>
            <input type="text" value="+237" disabled style="background:#eee;text-align:center;font-weight:700;">
          </div>
        </div>
        <div class="del-time">
          <label>Delivery Time</label>
          <div style="margin-bottom:8px;"><label><input type="radio" name="delivery_time_type" value="ASAP" checked> As soon as possible</label></div>
          <div style="display:flex;align-items:center;gap:10px;">
            <label><input type="radio" name="delivery_time_type" value="Schedule"> Schedule</label>
            <select name="scheduled_date" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;font-size:.85rem;">
              <option>Today</option><option>Tomorrow</option>
            </select>
          </div>
        </div>
        <label style="font-size:.85rem;"><input type="checkbox" name="is_saved" value="1"> Save this address for future use</label>
      </form>
    </div>

    <!-- Summary -->
    <div class="sum-box">
      <h3>Order Summary</h3>
      <?php foreach ($cartItems as $ci): ?>
      <div class="sum-row" style="font-size:.82rem;">
        <span><?= e($ci['name']) ?> ×<?= $ci['quantity'] ?></span>
        <span><?= formatPrice((float)$ci['price'] * $ci['quantity']) ?></span>
      </div>
      <?php endforeach; ?>
      <hr style="border:none;border-top:1px solid #ddd;margin:10px 0;">
      <div class="sum-row"><span>Delivery Fee</span><span><?= formatPrice($deliveryFee) ?></span></div>
      <hr style="border:none;border-top:1px solid #ddd;margin:10px 0;">
      <div class="sum-row total"><span>Total</span><span><?= formatPrice($grandTotal) ?></span></div>
      <div class="promo-row">
        <input type="text" placeholder="Promo code (optional)">
        <button type="button">Apply</button>
      </div>
      <?php if ($totalItems > 0): ?>
      <button type="submit" form="addressForm" class="btn-next">Proceed to Payment</button>
      <?php else: ?>
      <span class="btn-next disabled">Cart is Empty</span>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
