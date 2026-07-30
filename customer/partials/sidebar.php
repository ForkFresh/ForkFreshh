<?php
/* Determine active page for nav highlight */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function navClass(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <img src="assets/images/forkfresh-logo.png"
         alt="ForkFresh"
         onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.2rem;color:var(--green-dark)\'>fork<span style=\'color:var(--orange)\'>fresh</span></span>'">
  </div>

  <nav>
    <!-- Home (pill button) -->
    <a href="dashboard.php"
       class="sidebar-home-btn <?= navClass('dashboard', $currentPage) ?>">
      <i class="fa fa-home nav-icon"></i> Home
    </a>

    <!-- 1. My Orders -->
    <a href="my-orders.php" class="<?= navClass('my-orders', $currentPage) ?>">
      <i class="fa fa-receipt nav-icon"></i> My Orders
    </a>

    <!-- 2. Subscriptions -->
    <a href="manage-subscription.php" class="<?= navClass('manage-subscription', $currentPage) ?>">
      <i class="fa fa-rotate nav-icon"></i> My Subscription
    </a>

    <!-- 3. Meal Plans -->
    <a href="meal-plans.php" class="<?= navClass('meal-plans', $currentPage) ?>">
      <i class="fa fa-bowl-food nav-icon"></i> Meal Plans
    </a>

    <!-- 4. Categories -->
    <a href="categories.php" class="<?= navClass('categories', $currentPage) ?>">
      <i class="fa fa-th-large nav-icon"></i> Categories
    </a>

    <!-- 5. Cart -->
    <a href="cart.php" class="<?= navClass('cart', $currentPage) ?>">
      <i class="fa fa-cart-shopping nav-icon"></i> Cart
    </a>

    <!-- 6. Dishes -->
    <a href="dishes.php" class="<?= navClass('dishes', $currentPage) ?>">
      <i class="fa fa-utensils nav-icon"></i> Dishes
    </a>

    <!-- 7. Account -->
    <a href="account.php" class="<?= navClass('account', $currentPage) ?>">
      <i class="fa fa-circle-user nav-icon"></i> Account
    </a>

    <!-- Logout -->
    <a href="logout.php" class="logout-link">
      <i class="fa fa-right-from-bracket nav-icon"></i> Logout
    </a>
  </nav>

</aside>
