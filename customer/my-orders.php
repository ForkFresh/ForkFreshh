<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();
$userId = getCurrentUserId();
$user   = getCurrentUser();
$db     = getDB();

$stmt = $db->prepare('SELECT id, order_number, total_amount, order_status, placed_at FROM orders WHERE user_id=? ORDER BY placed_at DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>My Orders – ForkFresh</title>
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
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fa fa-bars"></i></button>
        <div class="topbar-greeting"><h1>My Orders</h1><p>All your past and current orders</p></div>
      </div>
      <div class="topbar-right"><div class="topbar-avatar"><?= $initials ?></div></div>
    </header>
    <main class="page-body">
      <?php if (empty($orders)): ?>
      <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:var(--radius);border:1px solid var(--border);">
        <p style="font-size:2.5rem;margin-bottom:12px;">📦</p>
        <p style="color:var(--text-light);margin-bottom:18px;">No orders yet.</p>
        <a href="<?= BASE_URL ?>categories.php" style="background:var(--green-main);color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Start Shopping</a>
      </div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($orders as $o):
          $statusColor = match($o['order_status']) {
            'delivered'  => '#2e7d32',
            'pending'    => '#f57c00',
            'cancelled'  => '#e53935',
            default      => '#1565c0',
          };
        ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
          <div>
            <p style="font-weight:700;font-size:.95rem;"><?= e($o['order_number'] ?? '#' . $o['id']) ?></p>
            <p style="font-size:.82rem;color:var(--text-light);margin-top:3px;"><?= date('M j, Y g:i A', strtotime($o['placed_at'])) ?></p>
          </div>
          <div style="text-align:center;">
            <p style="font-weight:700;"><?= formatPrice((float)$o['total_amount']) ?></p>
          </div>
          <div>
            <span style="background:<?= $statusColor ?>20;color:<?= $statusColor ?>;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;text-transform:capitalize;">
              <?= e($o['order_status']) ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>
    <?php include 'partials/footer.php'; ?>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
