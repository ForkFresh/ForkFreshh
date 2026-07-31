<?php
require_once dirname(__DIR__) . '/config/db.php';
requireRider();
$riderId = getCurrentRiderId();
$db      = getDB();

$riderStmt = $db->prepare('SELECT name, rider_code FROM riders WHERE id=? LIMIT 1');
$riderStmt->execute([$riderId]);
$rider = $riderStmt->fetch();

$filter = $_GET['status'] ?? 'all';
$sql = 'SELECT id, order_number, customer_name, customer_phone, dropoff_address, total_amount, order_status, placed_at, estimated_minutes FROM orders WHERE rider_id=?';
$params = [$riderId];
if ($filter !== 'all') { $sql .= ' AND order_status=?'; $params[] = $filter; }
$sql .= ' ORDER BY placed_at DESC';
$stmt = $db->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

// Update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $ns  = $_POST['new_status'] ?? '';
    $allowed = ['preparing','on_the_way','out_for_delivery','delivered'];
    if ($oid && in_array($ns, $allowed)) {
        $u = $db->prepare('UPDATE orders SET order_status=? WHERE id=? AND rider_id=?');
        $u->execute([$ns, $oid, $riderId]);
        $col = ($ns === 'delivered') ? ', delivered_at=NOW()' : '';
        if ($col) $db->prepare("UPDATE orders SET order_status=?, delivered_at=NOW() WHERE id=? AND rider_id=?")->execute([$ns, $oid, $riderId]);
    }
    redirect(BASE_URL . 'rider/my-orders.php?status=' . $filter);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>My Orders – ForkFresh Rider</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo"><a href="dashboard.php" style="text-decoration:none;"><span class="logo-fork">fork</span><span class="logo-fresh">fresh</span></a></div>
    <div class="sidebar-profile">
      <div class="profile-info" style="padding:12px 16px;">
        <p class="profile-name"><?= e($rider['name'] ?? '') ?></p>
        <p class="profile-id">#<?= e($rider['rider_code'] ?? '') ?></p>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-item"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
      <a href="my-orders.php"  class="nav-item active"><i class="fa-solid fa-bag-shopping"></i><span>My Orders</span></a>
      <a href="earnings.php"   class="nav-item"><i class="fa-solid fa-tags"></i><span>Earnings</span></a>
      <a href="profile.php"    class="nav-item"><i class="fa-solid fa-circle-user"></i><span>Profile</span></a>
      <a href="<?= BASE_URL ?>logout.php" class="nav-item logout-item"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="main-wrap">
    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-greeting"><h1 class="greeting-title">My Orders</h1><p class="greeting-sub">Manage your deliveries</p></div>
    </header>
    <div style="padding:20px 28px;">
      <!-- Filter bar -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
        <?php foreach (['all','assigned','on_the_way','delivered','cancelled'] as $s): ?>
        <a href="my-orders.php?status=<?= $s ?>"
           style="padding:7px 16px;border-radius:20px;font-size:.82rem;text-decoration:none;font-weight:600;
                  background:<?= $filter===$s?'#1a5c1a':'#fff' ?>;
                  color:<?= $filter===$s?'#fff':'#555' ?>;
                  border:1px solid <?= $filter===$s?'#1a5c1a':'#ddd' ?>;">
          <?= ucfirst(str_replace('_',' ',$s)) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($orders)): ?>
      <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:14px;border:1px solid #e8e8e8;">
        <p style="font-size:2.5rem;margin-bottom:10px;">📦</p>
        <p style="color:#888;">No orders found.</p>
      </div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($orders as $o):
          $sc = match($o['order_status']) { 'delivered'=>'#2e7d32','pending'=>'#f57c00','cancelled'=>'#e53935', default=>'#1565c0' };
          $nextStatus = match($o['order_status']) { 'assigned'=>'preparing','preparing'=>'on_the_way','on_the_way'=>'out_for_delivery','out_for_delivery'=>'delivered', default=>null };
          $nextLabel  = match($o['order_status']) { 'assigned'=>'Mark Preparing','preparing'=>'Out for Pick-up','on_the_way'=>'Out for Delivery','out_for_delivery'=>'Mark Delivered', default=>null };
        ?>
        <div style="background:#fff;border:1px solid #e8e8e8;border-radius:14px;padding:18px 20px;">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
              <p style="font-weight:700;font-size:.95rem;margin-bottom:4px;">#<?= e($o['order_number'] ?? $o['id']) ?></p>
              <p style="font-size:.83rem;color:#666;"><?= e($o['customer_name'] ?? '—') ?> · <?= e($o['customer_phone'] ?? '') ?></p>
              <p style="font-size:.83rem;color:#888;margin-top:3px;"><i class="fa-solid fa-location-dot" style="color:#e8652a;"></i> <?= e($o['dropoff_address'] ?? '—') ?></p>
              <p style="font-size:.78rem;color:#aaa;margin-top:4px;"><?= date('M j, Y g:i A', strtotime($o['placed_at'])) ?></p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
              <p style="font-weight:700;font-size:.95rem;"><?= formatPrice((float)$o['total_amount']) ?></p>
              <span style="background:<?= $sc ?>20;color:<?= $sc ?>;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:capitalize;"><?= e(str_replace('_',' ',$o['order_status'])) ?></span>
              <?php if ($nextStatus): ?>
              <form method="POST" action="my-orders.php?status=<?= $filter ?>">
                <input type="hidden" name="order_id"   value="<?= $o['id'] ?>">
                <input type="hidden" name="new_status" value="<?= $nextStatus ?>">
                <button type="submit" style="padding:7px 14px;background:#1a5c1a;color:#fff;border:none;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;"><?= $nextLabel ?></button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <footer style="background:rgb(3,85,3);color:#fff;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;font-size:.82rem;margin-top:auto;">
      <span style="font-weight:700;">fork<span style="color:#e8652a;">fresh</span></span>
      <span style="color:#ccc;">&copy; <?= date('Y') ?> ForkFresh</span>
    </footer>
  </div>
</div>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle')?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('visible'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });
</script>
</body>
</html>
