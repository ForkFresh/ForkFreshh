<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$user   = getCurrentUser();
$userId = getCurrentUserId();
$db     = getDB();

// Popular dishes from DB
$dishes = $db->query("SELECT id, name, price, image_url FROM products WHERE is_available=1 AND is_popular=1 ORDER BY id LIMIT 4")->fetchAll();
if (empty($dishes)) {
    $dishes = $db->query("SELECT id, name, price, image_url FROM products WHERE is_available=1 ORDER BY id LIMIT 4")->fetchAll();
}

// Active subscription
$subStmt = $db->prepare('SELECT id, plan_name, status FROM subscriptions WHERE user_id=? AND status="active" LIMIT 1');
$subStmt->execute([$userId]);
$activeSub = $subStmt->fetch();

// Order count
$ordStmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
$ordStmt->execute([$userId]);
$orderCount = (int)$ordStmt->fetchColumn();

// Cart count
$crtStmt = $db->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?');
$crtStmt->execute([$userId]);
$cartCount = (int)$crtStmt->fetchColumn();

$initials  = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$firstName = h($user['first_name'] ?? 'Guest');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – ForkFresh</title>
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
          <h1>Hello, <?= $firstName ?> 👋</h1>
          <p>What would you like to eat today?</p>
        </div>
      </div>
      <div class="topbar-search">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="dishSearch" placeholder="Search dishes, categories…" autocomplete="off">
      </div>
      <div class="topbar-right">
        <a href="cart.php" title="Cart" style="color:var(--text-mid);font-size:1.2rem;position:relative;">
          <i class="fa fa-cart-shopping"></i>
          <?php if ($cartCount > 0): ?><span style="position:absolute;top:-6px;right:-8px;background:var(--orange);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;"><?= $cartCount ?></span><?php endif; ?>
        </a>
        <a href="notifications.php" title="Notifications" style="color:var(--text-mid);font-size:1.2rem;"><i class="fa fa-bell"></i></a>
        <div class="topbar-avatar" title="<?= h($user['first_name'] ?? '') ?> <?= h($user['last_name'] ?? '') ?>"><?= $initials ?></div>
      </div>
    </header>

    <main class="page-body">
      <!-- Quick stats -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:16px;">
          <p style="font-size:.78rem;color:var(--text-light);margin-bottom:4px;">Total Orders</p>
          <p style="font-size:1.4rem;font-weight:700;"><?= $orderCount ?></p>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:16px;">
          <p style="font-size:.78rem;color:var(--text-light);margin-bottom:4px;">Cart Items</p>
          <p style="font-size:1.4rem;font-weight:700;"><?= $cartCount ?></p>
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:16px;">
          <p style="font-size:.78rem;color:var(--text-light);margin-bottom:4px;">Subscription</p>
          <p style="font-size:.95rem;font-weight:700;color:<?= $activeSub ? 'var(--green-main)' : 'var(--text-light)' ?>;"><?= $activeSub ? h($activeSub['plan_name']) : 'None' ?></p>
        </div>
      </div>

      <!-- Popular Dishes -->
      <div class="section-header"><h2>Popular Dishes</h2><a href="<?= BASE_URL ?>categories.php" class="view-all-link">View all</a></div>
      <div class="dishes-grid" id="dishesGrid">
        <?php foreach ($dishes as $dish): ?>
        <div class="dish-card">
          <img src="<?= h($dish['image_url'] ?? '') ?>" alt="<?= h($dish['name']) ?>"
               onerror="this.src='https://placehold.co/200x110/eee/999?text=Food'">
          <div class="dish-card-info">
            <h3><?= h($dish['name']) ?></h3>
            <p class="dish-price"><?= fcfa((float)$dish['price']) ?></p>
            <form method="POST" action="add-to-cart.php" style="margin-top:6px;">
              <input type="hidden" name="product_id" value="<?= (int)$dish['id'] ?>">
              <input type="hidden" name="redirect"   value="<?= h(BASE_URL . 'customer/dashboard.php') ?>">
              <button type="submit" style="width:100%;padding:7px;border:none;background:var(--green-main);color:#fff;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">Add to cart</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Categories -->
      <div class="section-header" style="margin-top:24px;"><h2>Categories</h2><a href="<?= BASE_URL ?>categories.php" class="view-all-link">View all</a></div>
      <div class="categories-grid">
        <a href="<?= BASE_URL ?>categories.php?slug=fresh-food" class="category-card">
          <img src="<?= BASE_URL ?>assets/images/fresh.jpg" alt="Fresh Food" onerror="this.src='https://placehold.co/300x110/2e7d32/fff?text=Fresh+Food'">
          <div class="cat-label active-cat">Fresh Food</div>
        </a>
        <a href="<?= BASE_URL ?>categories.php?slug=frozen-food" class="category-card">
          <img src="<?= BASE_URL ?>assets/images/frozen.jpg" alt="Frozen Food" onerror="this.src='https://placehold.co/300x110/1565c0/fff?text=Frozen+Food'">
          <div class="cat-label" style="background:rgba(21,101,192,.75);">Frozen Food</div>
        </a>
        <a href="<?= BASE_URL ?>categories.php?slug=drinks" class="category-card">
          <img src="<?= BASE_URL ?>assets/images/drinks.jpg" alt="Drinks" onerror="this.src='https://placehold.co/300x110/880e4f/fff?text=Drinks'">
          <div class="cat-label" style="background:rgba(136,14,79,.75);">Drinks &amp; Smoothies</div>
        </a>
      </div>

      <!-- Promo banner -->
      <div class="promo-banner" style="margin-top:24px;">
        <div class="promo-text">
          <h2>20% OFF</h2>
          <p>Use code below at checkout on your next order</p>
          <span class="promo-code">FORKFRESH20</span>
        </div>
        <img src="<?= BASE_URL ?>assets/images/jollof.jpg" alt="Promo" onerror="this.src='https://placehold.co/180x120/2e7d32/fff?text=Deal'">
      </div>

      <!-- Subscription strip -->
      <?php if ($activeSub): ?>
      <div style="background:var(--green-bg);border:1px solid var(--green-main);border-radius:var(--radius);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;margin-top:24px;">
        <div><span style="font-size:.82rem;color:var(--text-light);">Active plan</span><p style="font-weight:700;color:var(--green-dark);"><?= h($activeSub['plan_name']) ?></p></div>
        <a href="manage-subscription.php" style="background:var(--green-main);color:#fff;padding:8px 18px;border-radius:6px;font-size:.85rem;font-weight:600;text-decoration:none;">Manage</a>
      </div>
      <?php else: ?>
      <div style="background:#fff3e0;border:1px solid var(--orange);border-radius:var(--radius);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;margin-top:24px;">
        <div><p style="font-weight:600;">No active subscription</p><p style="font-size:.82rem;color:var(--text-light);">Subscribe to a meal plan for daily deliveries.</p></div>
        <a href="meal-plans.php" style="background:var(--orange);color:#fff;padding:8px 18px;border-radius:6px;font-size:.85rem;font-weight:600;text-decoration:none;">Browse Plans</a>
      </div>
      <?php endif; ?>
    </main>

    <?php include 'partials/footer.php'; ?>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
<script>
document.getElementById('dishSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#dishesGrid .dish-card').forEach(card => {
    card.style.display = card.querySelector('h3').textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
