<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();
$userId  = getCurrentUserId();
$orderId = (int)($_GET['order_id'] ?? 0);

$stmt = getDB()->prepare('SELECT o.*, p.payment_method FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=? LIMIT 1');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . 'customer/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Order Confirmed – ForkFresh</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f4f4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
    .card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.1);max-width:480px;width:100%;padding:40px 36px;text-align:center;}
    .icon-wrap{width:80px;height:80px;background:#e8f5e9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:#2e7d32;}
    h1{font-size:1.4rem;font-weight:800;color:#1a1a1a;margin-bottom:8px;}
    p{color:#666;font-size:.9rem;line-height:1.6;margin-bottom:6px;}
    .order-num{font-size:1rem;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:8px 18px;border-radius:20px;display:inline-block;margin:12px 0;}
    .details{border:1px solid #eee;border-radius:10px;padding:16px 20px;margin:18px 0;text-align:left;}
    .d-row{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:8px;color:#444;}
    .d-row span:last-child{font-weight:600;}
    .btn-row{display:flex;gap:10px;justify-content:center;margin-top:24px;}
    .btn{padding:11px 22px;border-radius:8px;font-weight:700;font-size:.9rem;text-decoration:none;cursor:pointer;border:none;transition:background .2s;}
    .btn-green{background:#2e7d32;color:#fff;} .btn-green:hover{background:#1b5e20;}
    .btn-outline{background:#fff;color:#2e7d32;border:2px solid #2e7d32;} .btn-outline:hover{background:#f0f7f0;}
  </style>
</head>
<body>
<div class="card">
  <div class="icon-wrap"><i class="fa fa-circle-check"></i></div>
  <h1>Order Confirmed!</h1>
  <p>Thank you for your order. We've received it and will start preparing it shortly.</p>
  <div class="order-num"><?= e($order['order_number']) ?></div>
  <div class="details">
    <div class="d-row"><span>Order Total</span><span><?= formatPrice((float)$order['total_amount']) ?></span></div>
    <div class="d-row"><span>Payment Method</span><span><?= e($order['payment_method'] ?? 'N/A') ?></span></div>
    <div class="d-row"><span>Status</span><span style="text-transform:capitalize;"><?= e($order['order_status']) ?></span></div>
    <div class="d-row"><span>Placed At</span><span><?= date('M j, Y g:i A', strtotime($order['placed_at'])) ?></span></div>
  </div>
  <div class="btn-row">
    <a href="dashboard.php" class="btn btn-green">Go to Dashboard</a>
    <a href="<?= BASE_URL ?>categories.php" class="btn btn-outline">Order Again</a>
  </div>
</div>
</body>
</html>
