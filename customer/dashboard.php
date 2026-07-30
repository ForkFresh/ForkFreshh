<?php
require_once '../includes/db.php';
startSession();

$user   = getCurrentUser();
$userId = getCurrentUserId();
$pdo    = getDB();

/* ── Popular dishes (reuse orders / a dishes table if it exists, else seed static) ── */
$dishes = [
    ['name' => 'Ndolé with Plantain',   'price' => 2000, 'img' => 'ndole.jpg'],
    ['name' => 'Jollof Rice and Chicken','price' => 1500, 'img' => 'jollof.jpg'],
    ['name' => 'Achu Soup',             'price' => 2500, 'img' => 'achu.jpg'],
    ['name' => 'Fufu and Eru',          'price' => 1000, 'img' => 'fufu.jpg'],
];

/* ── Active subscription quick-peek ── */
$subStmt = $pdo->prepare(
    'SELECT id, plan_name, status FROM subscriptions WHERE user_id = ? AND status = "active" LIMIT 1'
);
$subStmt->execute([$userId]);
$activeSub = $subStmt->fetch();

/* ── Recent orders count ── */
$ordStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$ordStmt->execute([$userId]);
$orderCount = (int)$ordStmt->fetchColumn();

$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
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

  <!-- ======= SIDEBAR ======= -->
  <?php include 'partials/sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ======= MAIN ======= -->
  <div class="main-content">

    <!-- Top Bar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
          <i class="fa fa-bars"></i>
        </button>
        <div class="topbar-greeting">
          <h1>Hello, <?= $firstName ?> &#x1F44B;</h1>
          <p>What would you like to eat today?</p>
        </div>
      </div>

      <div class="topbar-search">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="dishSearch" placeholder="Search dishes, categories…" autocomplete="off">
      </div>

      <div class="topbar-right">
        <a href="notifications.php" title="Notifications" style="color:var(--text-mid);font-size:1.2rem;">
          <i class="fa fa-bell"></i>
        </a>
        <div class="topbar-avatar" title="<?= h($user['first_name'] ?? '') ?> <?= h($user['last_name'] ?? '') ?>">
          <?= $initials ?>
        </div>
      </div>
    </header>

    <!-- Page body -->
    <main class="page-body">

      <!-- ── Popular Dishes ── -->
      <div class="section-header">
        <h2>Popular Dishes</h2>
        <a href="dishes.php" class="view-all-link">View all</a>
      </div>

      <div class="dishes-grid" id="dishesGrid">
        <?php foreach ($dishes as $dish): ?>
        <div class="dish-card">
          <img
            src="assets/images/<?= h($dish['img']) ?>"
            alt="<?= h($dish['name']) ?>"
            onerror="this.onerror=null;this.src='assets/images/placeholder.jpg'"
          >
          <div class="dish-card-info">
            <h3><?= h($dish['name']) ?></h3>
            <p class="dish-price">FCFA <?= number_format($dish['price']) ?></p>
            <button class="btn-add-cart"
                    data-name="<?= h($dish['name']) ?>"
                    data-price="<?= $dish['price'] ?>"
                    style="margin-top:6px;width:100%;padding:7px;border:none;
                           background:var(--green-main);color:#fff;border-radius:6px;
                           font-size:.8rem;font-weight:600;cursor:pointer;">
              Add to cart
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ── Categories ── -->
      <div class="section-header">
        <h2>Categories</h2>
        <a href="categories.php" class="view-all-link">View all</a>
      </div>

      <div class="categories-grid">
        <a href="categories.php?cat=fresh" class="category-card">
          <img src="assets/images/fresh-food.jpg" alt="Fresh Food"
               onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
          <div class="cat-label active-cat">Fresh Food</div>
        </a>
        <a href="categories.php?cat=frozen" class="category-card">
          <img src="assets/images/frozen-food.jpg" alt="Frozen Food"
               onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
          <div class="cat-label" style="background:rgba(30,80,200,.72);">Frozen Food</div>
        </a>
        <a href="categories.php?cat=drinks" class="category-card">
          <img src="assets/images/drinks.jpg" alt="Drinks and Smoothies"
               onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
          <div class="cat-label" style="background:rgba(180,30,90,.72);">Drinks and Smoothies</div>
        </a>
      </div>

      <!-- ── Promo Banner ── -->
      <div class="promo-banner">
        <div class="promo-text">
          <h2>20% OFF</h2>
          <p>Use code below at checkout on your next order</p>
          <span class="promo-code">FORKFRESH20</span>
        </div>
        <img src="assets/images/promo-food.jpg" alt="Promo"
             onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
      </div>

      <!-- ── Quick subscription status strip ── -->
      <?php if ($activeSub): ?>
      <div style="background:var(--green-bg);border:1px solid var(--green-main);
                  border-radius:var(--radius);padding:14px 20px;
                  display:flex;align-items:center;justify-content:space-between;
                  margin-bottom:28px;">
        <div>
          <span style="font-size:.82rem;color:var(--text-light);">Active plan</span>
          <p style="font-weight:700;color:var(--green-dark);"><?= h($activeSub['plan_name']) ?></p>
        </div>
        <a href="manage-subscription.php"
           style="background:var(--green-main);color:#fff;padding:8px 18px;
                  border-radius:6px;font-size:.85rem;font-weight:600;">
          Manage
        </a>
      </div>
      <?php endif; ?>

    </main><!-- /page-body -->

    <!-- Footer -->
    <?php include 'partials/footer.php'; ?>

  </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script src="assets/js/app.js"></script>
<script>
/* Live dish search */
document.getElementById('dishSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#dishesGrid .dish-card').forEach(card => {
    const name = card.querySelector('h3').textContent.toLowerCase();
    card.style.display = name.includes(q) ? '' : 'none';
  });
});

/* Add to cart buttons */
document.querySelectorAll('.btn-add-cart').forEach(btn => {
  btn.addEventListener('click', function () {
    showToast(this.dataset.name + ' added to cart!', 'success');
  });
});
</script>
</body>
</html>
