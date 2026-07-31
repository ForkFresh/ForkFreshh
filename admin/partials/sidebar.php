<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function adminNav(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-logo">
    <a href="dashboard.php" style="text-decoration:none;">
      <span style="font-weight:900;font-size:1.2rem;color:#2e7d32;">fork</span><span style="font-weight:900;font-size:1.2rem;color:#f57c00;">fresh</span>
    </a>
    <span style="font-size:.7rem;color:#aaa;display:block;margin-top:2px;">Admin Panel</span>
  </div>
  <nav class="admin-nav">
    <a href="dashboard.php" class="admin-nav-item <?= adminNav('dashboard', $currentPage) ?>"><i class="fa fa-gauge-high"></i><span>Dashboard</span></a>
    <a href="orders.php"    class="admin-nav-item <?= adminNav('orders',    $currentPage) ?>"><i class="fa fa-receipt"></i><span>Orders</span></a>
    <a href="products.php"  class="admin-nav-item <?= adminNav('products',  $currentPage) ?>"><i class="fa fa-box"></i><span>Products</span></a>
    <a href="categories.php" class="admin-nav-item <?= adminNav('categories',$currentPage) ?>"><i class="fa fa-tags"></i><span>Categories</span></a>
    <a href="customers.php" class="admin-nav-item <?= adminNav('customers', $currentPage) ?>"><i class="fa fa-users"></i><span>Customers</span></a>
    <a href="riders.php"    class="admin-nav-item <?= adminNav('riders',    $currentPage) ?>"><i class="fa fa-motorcycle"></i><span>Riders</span></a>
    <a href="messages.php"  class="admin-nav-item <?= adminNav('messages',  $currentPage) ?>"><i class="fa fa-envelope"></i><span>Messages</span></a>
    <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item admin-logout"><i class="fa fa-right-from-bracket"></i><span>Logout</span></a>
  </nav>
</aside>
