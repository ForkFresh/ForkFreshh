<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function navClass(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="<?= BASE_URL ?>index.php" style="display:flex;align-items:center;gap:6px;text-decoration:none;">
      <img src="<?= BASE_URL ?>assets/images/IMG_8023.PNG" alt="ForkFresh" style="width:90px;"
           onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.1rem;color:var(--green-dark)\'>fork<span style=\'color:var(--orange)\'>fresh</span></span>'">
    </a>
    <span style="font-size:.7rem;color:var(--text-light);display:block;margin-top:2px;">Customer Portal</span>
  </div>
  <nav>
    <a href="dashboard.php"            class="sidebar-home-btn <?= navClass('dashboard', $currentPage) ?>"><i class="fa fa-home nav-icon"></i> Home</a>
    <a href="my-orders.php"            class="<?= navClass('my-orders',             $currentPage) ?>"><i class="fa fa-receipt nav-icon"></i> My Orders</a>
    <a href="manage-subscription.php"  class="<?= navClass('manage-subscription',   $currentPage) ?>"><i class="fa fa-rotate nav-icon"></i> My Subscription</a>
    <a href="meal-plans.php"           class="<?= navClass('meal-plans',             $currentPage) ?>"><i class="fa fa-bowl-food nav-icon"></i> Meal Plans</a>
    <a href="<?= BASE_URL ?>categories.php" class=""><i class="fa fa-th-large nav-icon"></i> Browse Food</a>
    <a href="cart.php"                 class="<?= navClass('cart',                   $currentPage) ?>"><i class="fa fa-cart-shopping nav-icon"></i> Cart</a>
    <a href="account.php"              class="<?= navClass('account',                $currentPage) ?>"><i class="fa fa-circle-user nav-icon"></i> Account</a>
    <a href="<?= BASE_URL ?>logout.php" class="logout-link"><i class="fa fa-right-from-bracket nav-icon"></i> Logout</a>
  </nav>
</aside>
