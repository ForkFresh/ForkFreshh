<?php
/**
 * ForkFresh – Cart Page (Customer)
 */
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$userId = getCurrentUserId();
$user   = getCurrentUser();
$db     = getDB();

// Handle quantity update / remove
$action = $_GET['action'] ?? '';
$itemId = (int)($_GET['id'] ?? 0);
if ($action === 'remove' && $itemId > 0) {
    $db->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?')->execute([$userId, $itemId]);
    redirect(BASE_URL . 'customer/cart.php');
}
if ($action === 'update' && $itemId > 0) {
    $qty = (int)($_GET['qty'] ?? 0);
    if ($qty > 0) {
        $db->prepare('UPDATE cart_items SET quantity=? WHERE user_id=? AND product_id=?')->execute([$qty, $userId, $itemId]);
    } else {
        $db->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?')->execute([$userId, $itemId]);
    }
    redirect(BASE_URL . 'customer/cart.php');
}

// Fetch cart items
$stmt = $db->prepare('
    SELECT ci.product_id AS id, ci.quantity, p.name, p.price, p.image_url
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
');
$stmt->execute([$userId]);
$cartItems  = $stmt->fetchAll();
$itemCount  = count($cartItems);
$subtotal   = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$deliveryFee = $itemCount > 0 ? 1000 : 0;
$total       = $subtotal + $deliveryFee;

$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cart – ForkFresh</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-wrapper">
  <?php include 'partials/sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fa fa-bars"></i></button>
        <div class="topbar-greeting">
          <h1>My Cart</h1>
          <p><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></p>
        </div>
      </div>
      <div class="topbar-right">
        <a href="<?= BASE_URL ?>index.php" style="color:var(--text-mid);font-size:.85rem;"><i class="fa fa-store"></i> Shop</a>
        <div class="topbar-avatar"><?= $initials ?></div>
      </div>
    </header>

    <main class="page-body">
      <div class="cart-layout">
        <!-- Items -->
        <div class="cart-items-col">
          <?php if ($itemCount === 0): ?>
          <div class="cart-empty">
            <div style="font-size:3rem;margin-bottom:14px;">🛒</div>
            <p style="color:#777;margin-bottom:18px;">Your cart is currently empty.</p>
            <a href="<?= BASE_URL ?>categories.php" class="btn-green-sm">Start Shopping</a>
          </div>
          <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
          <div class="cart-item-card">
            <img src="<?= e($item['image_url'] ?? '') ?>" alt="<?= e($item['name']) ?>"
                 onerror="this.src='https://placehold.co/70x55/eee/999?text=Food'">
            <div class="cart-item-info">
              <h4><?= e($item['name']) ?></h4>
              <p><?= formatPrice((float)$item['price']) ?></p>
            </div>
            <div class="qty-row">
              <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity']-1 ?>" class="qty-btn">−</a>
              <span><?= $item['quantity'] ?></span>
              <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity']+1 ?>" class="qty-btn">+</a>
            </div>
            <span class="item-total"><?= formatPrice((float)$item['price'] * $item['quantity']) ?></span>
            <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="remove-btn" title="Remove">×</a>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Summary -->
        <div class="cart-summary-col">
          <h3>Order Summary</h3>
          <div class="sum-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
          <div class="sum-row"><span>Delivery fee</span><span><?= $itemCount > 0 ? formatPrice($deliveryFee) : '—' ?></span></div>
          <hr style="border:none;border-top:1px solid #e0e0e0;margin:12px 0;">
          <div class="sum-row sum-total"><span>Total</span><span><?= formatPrice($total) ?></span></div>
          <?php if ($itemCount > 0): ?>
          <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
          <?php else: ?>
          <button class="btn-checkout disabled" disabled>Cart is Empty</button>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <?php include 'partials/footer.php'; ?>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
<style>
.cart-layout { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
.cart-items-col { display:flex; flex-direction:column; gap:12px; }
.cart-item-card { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:14px; display:flex; align-items:center; gap:14px; }
.cart-item-card img { width:70px; height:55px; object-fit:cover; border-radius:6px; flex-shrink:0; }
.cart-item-info { flex:1; }
.cart-item-info h4 { font-size:.9rem; font-weight:600; margin-bottom:3px; }
.cart-item-info p  { font-size:.82rem; color:#777; }
.qty-row { display:flex; align-items:center; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.qty-btn { padding:6px 12px; text-decoration:none; color:#333; font-size:1rem; font-weight:700; background:#f4f4f4; transition:background .2s; }
.qty-btn:hover { background:#e0e0e0; }
.qty-row span { padding:6px 12px; font-size:.9rem; font-weight:600; }
.item-total { font-size:.9rem; font-weight:700; min-width:80px; text-align:right; }
.remove-btn { color:#ccc; font-size:1.2rem; font-weight:700; text-decoration:none; padding:4px 8px; transition:color .2s; }
.remove-btn:hover { color:#e53935; }
.cart-empty { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:50px 20px; text-align:center; }
.cart-summary-col { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:20px; position:sticky; top:80px; }
.cart-summary-col h3 { font-size:1rem; font-weight:700; margin-bottom:16px; }
.sum-row { display:flex; justify-content:space-between; font-size:.88rem; margin-bottom:10px; color:#555; }
.sum-total { font-weight:700; color:#000; font-size:.95rem; }
.btn-checkout { display:block; width:100%; padding:12px; background:#1a6e1a; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:700; cursor:pointer; margin-top:16px; text-align:center; text-decoration:none; transition:background .2s; }
.btn-checkout:hover { background:#145214; }
.btn-checkout.disabled { background:#ccc; cursor:not-allowed; pointer-events:none; }
.btn-green-sm { display:inline-block; padding:10px 22px; background:#2e7d32; color:#fff; border-radius:8px; font-weight:600; text-decoration:none; font-size:.9rem; }
@media(max-width:768px){ .cart-layout{grid-template-columns:1fr;} }
</style>
</body>
</html>
