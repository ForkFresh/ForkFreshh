<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Dashboard';

$totalOrders    = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRiders    = (int)$db->query("SELECT COUNT(*) FROM riders")->fetchColumn();
$totalRevenue   = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status='delivered'")->fetchColumn();

$pendingOrders  = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
$unreadMessages = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();

$recentOrders = $db->query("
    SELECT o.id, o.order_number, o.order_status, o.total_amount, o.placed_at,
           u.first_name, u.last_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.placed_at DESC LIMIT 8
")->fetchAll();

include 'partials/header.php';
?>

<!-- Stat cards -->
<div class="stat-cards">
  <div class="stat-card-a">
    <div class="sc-icon sc-green"><i class="fa fa-receipt"></i></div>
    <div class="sc-value"><?= $totalOrders ?></div>
    <div class="sc-label">Total Orders</div>
  </div>
  <div class="stat-card-a">
    <div class="sc-icon sc-blue"><i class="fa fa-users"></i></div>
    <div class="sc-value"><?= $totalCustomers ?></div>
    <div class="sc-label">Customers</div>
  </div>
  <div class="stat-card-a">
    <div class="sc-icon sc-orange"><i class="fa fa-motorcycle"></i></div>
    <div class="sc-value"><?= $totalRiders ?></div>
    <div class="sc-label">Riders</div>
  </div>
  <div class="stat-card-a">
    <div class="sc-icon sc-purple"><i class="fa fa-money-bill-wave"></i></div>
    <div class="sc-value" style="font-size:1.2rem;"><?= formatPrice($totalRevenue) ?></div>
    <div class="sc-label">Total Revenue</div>
  </div>
</div>

<!-- Alert row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
  <div style="background:#fff3e0;border:1px solid #ffe0b2;border-radius:var(--radius);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
    <div><p style="font-weight:700;color:#e65100;"><?= $pendingOrders ?> Pending Orders</p><p style="font-size:.82rem;color:#888;">Awaiting assignment</p></div>
    <a href="orders.php?status=pending" class="btn-sm btn-primary">View</a>
  </div>
  <div style="background:#fce4ec;border:1px solid #f8bbd0;border-radius:var(--radius);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
    <div><p style="font-weight:700;color:#880e4f;"><?= $unreadMessages ?> Unread Messages</p><p style="font-size:.82rem;color:#888;">Customer enquiries</p></div>
    <a href="messages.php" class="btn-sm btn-primary">View</a>
  </div>
</div>

<!-- Recent orders table -->
<div class="admin-table-wrap">
  <div class="admin-table-head">
    <h2>Recent Orders</h2>
    <a href="orders.php" class="btn-sm btn-outline-sm">View All</a>
  </div>
  <table>
    <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($recentOrders as $o):
      $bc = match($o['order_status']) { 'delivered'=>'badge-green','pending'=>'badge-orange','cancelled'=>'badge-red', default=>'badge-blue' };
    ?>
    <tr>
      <td><strong><?= e($o['order_number'] ?? '#'.$o['id']) ?></strong></td>
      <td><?= e(($o['first_name'] ?? '').' '.($o['last_name'] ?? '')) ?></td>
      <td><?= formatPrice((float)$o['total_amount']) ?></td>
      <td><span class="badge <?= $bc ?>"><?= e($o['order_status']) ?></span></td>
      <td style="color:#888;font-size:.8rem;"><?= date('M j, Y', strtotime($o['placed_at'])) ?></td>
      <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn-sm btn-outline-sm">Details</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'partials/footer.php'; ?>
