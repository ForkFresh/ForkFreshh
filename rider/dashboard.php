<?php
/**
 * ForkFresh – Rider Dashboard (PHP, auth-protected)
 */
require_once dirname(__DIR__) . '/config/db.php';
requireRider();

$riderId = getCurrentRiderId();
$db      = getDB();

$riderStmt = $db->prepare('SELECT * FROM riders WHERE id=? LIMIT 1');
$riderStmt->execute([$riderId]);
$rider = $riderStmt->fetch();

if (!$rider) {
    session_destroy();
    redirect(BASE_URL . 'login.php?role=rider');
}

// Today's stats
$today = date('Y-m-d');
$statsStmt = $db->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(order_status = 'delivered')                                            AS completed,
        SUM(order_status IN ('assigned','preparing','on_the_way','out_for_delivery')) AS in_progress,
        SUM(CASE WHEN order_status='delivered' THEN total_amount ELSE 0 END)       AS earnings
    FROM orders
    WHERE rider_id=? AND DATE(placed_at)=?
");
$statsStmt->execute([$riderId, $today]);
$stats = $statsStmt->fetch();

// Next/active order
$nextStmt = $db->prepare("
    SELECT * FROM orders
    WHERE rider_id=? AND order_status IN ('assigned','preparing','on_the_way','out_for_delivery')
    ORDER BY placed_at DESC LIMIT 1
");
$nextStmt->execute([$riderId]);
$nextOrder = $nextStmt->fetch();

// Recent assigned orders
$recentStmt = $db->prepare("
    SELECT id, order_number, customer_name, dropoff_address, order_status, total_amount, placed_at
    FROM orders WHERE rider_id=? ORDER BY placed_at DESC LIMIT 10
");
$recentStmt->execute([$riderId]);
$recentOrders = $recentStmt->fetchAll();

$initials = strtoupper(substr($rider['name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rider Dashboard – ForkFresh</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <a href="<?= BASE_URL ?>index.php" style="text-decoration:none;">
        <span class="logo-fork">fork</span><span class="logo-fresh">fresh</span>
      </a>
    </div>
    <div class="sidebar-profile">
      <div class="profile-avatar-wrap">
        <img src="<?= e($rider['avatar_url'] ?: BASE_URL . 'assets/images/IMG_8023.PNG') ?>"
             alt="<?= e($rider['name']) ?>"
             class="profile-avatar"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rider['name']) ?>&background=1a5c1a&color=fff&size=72'">
        <span class="profile-status-dot" style="background:<?= $rider['status']==='online'?'#4caf50':($rider['status']==='busy'?'#e8652a':'#9e9e9e') ?>;"></span>
      </div>
      <div class="profile-info">
        <p class="profile-name"><?= e($rider['name']) ?></p>
        <p class="profile-id">Rider ID: #<?= e($rider['rider_code']) ?></p>
        <p class="profile-online">
          <span class="online-dot" style="background:<?= $rider['status']==='online'?'#4caf50':'#9e9e9e' ?>;"></span>
          <?= ucfirst(e($rider['status'])) ?>
        </p>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"         class="nav-item active"   data-page="dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
      <a href="my-orders.php"         class="nav-item"          data-page="orders"><i class="fa-solid fa-bag-shopping"></i><span>My Orders</span></a>
      <a href="earnings.php"          class="nav-item"          data-page="earnings"><i class="fa-solid fa-tags"></i><span>Earnings</span></a>
      <a href="profile.php"           class="nav-item"          data-page="profile"><i class="fa-solid fa-circle-user"></i><span>Profile</span></a>
      <a href="<?= BASE_URL ?>logout.php?role=rider" class="nav-item logout-item"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- MAIN -->
  <div class="main-wrap">
    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-greeting">
        <h1 class="greeting-title">Good <?= date('G') < 12 ? 'morning' : (date('G') < 17 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $rider['name'])[0]) ?>! 👋</h1>
        <p class="greeting-sub">Here's your overview for today.</p>
      </div>
      <div class="topbar-right">
        <button class="notif-btn" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notif-badge">1</span></button>
        <div class="status-dropdown">
          <span class="status-online-dot" style="background:<?= $rider['status']==='online'?'#4caf50':'#9e9e9e' ?>;"></span>
          <span><?= ucfirst(e($rider['status'])) ?></span>
          <i class="fa-solid fa-chevron-down"></i>
        </div>
      </div>
    </header>

    <!-- DASHBOARD PAGE -->
    <section id="page-dashboard" class="page-section">

      <!-- Stat cards -->
      <section class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon-wrap"><i class="fa-solid fa-bag-shopping"></i></div>
          <div class="stat-body">
            <p class="stat-label">Today's Deliveries</p>
            <p class="stat-value"><?= (int)($stats['total'] ?? 0) ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon-wrap green"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-body">
            <p class="stat-label">Completed</p>
            <p class="stat-value"><?= (int)($stats['completed'] ?? 0) ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon-wrap orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <div class="stat-body">
            <p class="stat-label">In Progress</p>
            <p class="stat-value"><?= (int)($stats['in_progress'] ?? 0) ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon-wrap wallet"><i class="fa-solid fa-wallet"></i></div>
          <div class="stat-body">
            <p class="stat-label">Today's Earnings</p>
            <p class="stat-value earnings-val">FCFA <?= number_format((float)($stats['earnings'] ?? 0)) ?></p>
          </div>
        </div>
      </section>

      <!-- Bottom grid -->
      <section class="bottom-grid">
        <!-- Earnings chart -->
        <div class="card chart-card">
          <div class="chart-header">
            <div><p class="card-label">Today's Earnings</p><p class="card-big-val">FCFA <?= number_format((float)($stats['earnings'] ?? 0)) ?></p></div>
            <p class="chart-change positive"><i class="fa-solid fa-caret-up"></i> Live data</p>
          </div>
          <div class="chart-area"><canvas id="earningsChart"></canvas></div>
        </div>

        <!-- Donut -->
        <div class="card donut-card">
          <p class="card-title">Today's Deliveries</p>
          <div class="donut-wrap">
            <div class="donut-canvas-wrap">
              <canvas id="donutChart" width="160" height="160"></canvas>
              <div class="donut-center-label">
                <?php
                $total     = max(1, (int)($stats['total'] ?? 1));
                $completed = (int)($stats['completed'] ?? 0);
                $pct       = round($completed / $total * 100);
                ?>
                <span class="donut-pct"><?= $pct ?>%</span>
                <span class="donut-sub">Completed</span>
              </div>
            </div>
            <div class="donut-legend">
              <?php
              $inProgress = (int)($stats['in_progress'] ?? 0);
              $cPct = $total > 0 ? round($completed/$total*100) : 0;
              $iPct = $total > 0 ? round($inProgress/$total*100) : 0;
              ?>
              <div class="legend-row"><span class="legend-dot completed-dot"></span><span class="legend-text">Completed</span><span class="legend-count"><?= $completed ?> (<?= $cPct ?>%)</span></div>
              <div class="legend-row"><span class="legend-dot inprogress-dot"></span><span class="legend-text">In Progress</span><span class="legend-count"><?= $inProgress ?> (<?= $iPct ?>%)</span></div>
            </div>
          </div>
          <a href="my-orders.php" class="view-all-link">View all orders <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <!-- Next order -->
        <div class="card next-order-card">
          <div class="next-order-left">
            <p class="card-title">Next Order</p>
            <?php if ($nextOrder): ?>
            <p class="next-order-id">#<?= e($nextOrder['order_number'] ?? $nextOrder['id']) ?></p>
            <p class="pickup-badge"><span class="pickup-dot"></span> Pick up in ~<?= (int)($nextOrder['estimated_minutes'] ?? 30) ?> min</p>
            <div class="route-info">
              <div class="route-point">
                <p class="route-sub">Restaurant</p>
                <p class="route-name"><?= e($nextOrder['restaurant_name'] ?? 'ForkFresh Kitchen') ?></p>
              </div>
              <i class="fa-solid fa-arrow-right route-arrow"></i>
              <div class="route-point">
                <p class="route-sub">Drop-off</p>
                <p class="route-name"><?= e($nextOrder['dropoff_address'] ?? 'N/A') ?></p>
              </div>
            </div>
            <a href="my-orders.php" class="btn-view-details">View Details</a>
            <?php else: ?>
            <p style="color:#888;font-size:.88rem;margin-top:12px;">No active orders right now.</p>
            <p style="color:#aaa;font-size:.82rem;margin-top:6px;">New orders will appear here.</p>
            <?php endif; ?>
          </div>
          <div class="next-order-map">
            <svg viewBox="0 0 220 180" xmlns="http://www.w3.org/2000/svg" class="mini-map-svg">
              <rect width="220" height="180" fill="#e8e0d0"/>
              <rect x="0" y="30" width="220" height="8" fill="#f5f0e8"/><rect x="0" y="75" width="220" height="7" fill="#fff"/><rect x="0" y="120" width="220" height="8" fill="#f5f0e8"/>
              <rect x="30" y="0" width="7" height="180" fill="#f5f0e8"/><rect x="75" y="0" width="8" height="180" fill="#fff"/><rect x="130" y="0" width="7" height="180" fill="#f5f0e8"/>
              <rect x="5" y="5" width="22" height="22" rx="2" fill="#d4c9b0"/><rect x="40" y="5" width="30" height="22" rx="2" fill="#cfc3a5"/><rect x="85" y="5" width="40" height="22" rx="2" fill="#d4c9b0"/>
              <rect x="5" y="42" width="22" height="28" rx="2" fill="#d4c9b0"/><rect x="85" y="87" width="40" height="28" rx="2" fill="#e0d5c0"/>
              <polyline points="55,120 55,75 130,75 130,30 175,30 200,30" fill="none" stroke="#1a5c1a" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
              <g transform="translate(48,112)"><path d="M0,-18 C-7,-18 -12,-12 -12,-6 C-12,3 0,16 0,16 C0,16 12,3 12,-6 C12,-12 7,-18 0,-18 Z" fill="#e8652a"/><circle cx="0" cy="-6" r="5" fill="white"/></g>
              <g transform="translate(196,22)"><path d="M0,-14 C-5,-14 -9,-9 -9,-4 C-9,3 0,12 0,12 C0,12 9,3 9,-4 C9,-9 5,-14 0,-14 Z" fill="#1a5c1a"/><circle cx="0" cy="-4" r="4" fill="white"/></g>
            </svg>
          </div>
        </div>

        <!-- Performance -->
        <div class="card perf-card">
          <p class="card-title">Performance <span class="perf-week">(Overall)</span></p>
          <div class="perf-stats">
            <div class="perf-item"><p class="perf-label">Acceptance Rate</p><p class="perf-value">98%</p></div>
            <div class="perf-item"><p class="perf-label">On-time Delivery</p><p class="perf-value">96%</p></div>
            <div class="perf-item"><p class="perf-label">Customer Rating</p><p class="perf-value"><?= number_format((float)$rider['rating'], 1) ?> <span class="star">★</span></p></div>
          </div>
          <a href="earnings.php" class="view-all-link perf-link">View full performance <i class="fa-solid fa-chevron-right"></i></a>
        </div>
      </section>

      <!-- Recent Orders Table -->
      <?php if (!empty($recentOrders)): ?>
      <div style="padding:0 28px 28px;">
        <div style="background:#fff;border:1px solid #e8e8e8;border-radius:14px;overflow:hidden;">
          <div style="padding:18px 20px;border-bottom:1px solid #e8e8e8;font-weight:700;font-size:.95rem;">Recent Orders</div>
          <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <thead style="background:#f9f9f9;">
              <tr>
                <th style="padding:10px 16px;text-align:left;color:#888;font-weight:600;">Order #</th>
                <th style="padding:10px 16px;text-align:left;color:#888;font-weight:600;">Customer</th>
                <th style="padding:10px 16px;text-align:left;color:#888;font-weight:600;">Address</th>
                <th style="padding:10px 16px;text-align:left;color:#888;font-weight:600;">Amount</th>
                <th style="padding:10px 16px;text-align:left;color:#888;font-weight:600;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o):
                $sc = match($o['order_status']) { 'delivered'=>'#2e7d32','pending'=>'#f57c00','cancelled'=>'#e53935', default=>'#1565c0' };
              ?>
              <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:11px 16px;font-weight:600;">#<?= e($o['order_number'] ?? $o['id']) ?></td>
                <td style="padding:11px 16px;"><?= e($o['customer_name'] ?? '—') ?></td>
                <td style="padding:11px 16px;color:#666;"><?= e($o['dropoff_address'] ?? '—') ?></td>
                <td style="padding:11px 16px;font-weight:600;"><?= formatPrice((float)$o['total_amount']) ?></td>
                <td style="padding:11px 16px;"><span style="background:<?= $sc ?>20;color:<?= $sc ?>;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:capitalize;"><?= e($o['order_status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- Footer -->
      <footer>
        <div>
          <div class="footer-logo">
            <img src="<?= BASE_URL ?>assets/images/IMG_8063.PNG" alt="ForkFresh"
                 onerror="this.style.display='none'">
          </div>
          <p>Your favourite go-to food delivery platform.</p>
        </div>
        <div><h3>Categories</h3><p>About Us</p><p>Terms &amp; Conditions</p></div>
        <div><h3>Support</h3><p>Contact Us</p></div>
        <div><h3>Account</h3><p><a href="profile.php" style="color:#ccc;text-decoration:none;">My Profile</a></p><p><a href="<?= BASE_URL ?>logout.php" style="color:#ccc;text-decoration:none;">Logout</a></p></div>
        <div><h3>Follow Us</h3><p>Facebook</p><p>TikTok</p><p>Instagram</p></div>
      </footer>

    </section><!-- /#page-dashboard -->
  </div><!-- /.main-wrap -->
</div><!-- /.layout -->

<script src="script.js"></script>
<script>
// Override the static RIDER_ID with the real PHP session value
const RIDER_ID = <?= $riderId ?>;
// Re-draw charts with live PHP data
const completedPct = <?= max(0, min(100, $pct)) ?> / 100;
const inProgPct    = <?= $total > 0 ? round($inProgress/$total*100) : 0 ?> / 100;
document.addEventListener('DOMContentLoaded', () => {
    drawDonutChartLive(completedPct, inProgPct);
});
function drawDonutChartLive(cPct, iPct) {
    const canvas = document.getElementById('donutChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const size = 160;
    canvas.width  = size * dpr; canvas.height = size * dpr;
    canvas.style.width = size + 'px'; canvas.style.height = size + 'px';
    ctx.scale(dpr, dpr);
    const cx = size/2, cy = size/2, outerR = 70, innerR = 50;
    ctx.beginPath(); ctx.arc(cx,cy,outerR,0,Math.PI*2); ctx.fillStyle='#ebebeb'; ctx.fill();
    const segs = [];
    if (cPct > 0) segs.push({v:cPct,   color:'#1a5c1a'});
    if (iPct > 0) segs.push({v:iPct,   color:'#e8652a'});
    const rest = 1 - cPct - iPct;
    if (rest > 0)  segs.push({v:rest,   color:'#e0e0e0'});
    let start = -Math.PI/2;
    segs.forEach(s => {
        const end = start + s.v * Math.PI * 2;
        ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,outerR,start,end);
        ctx.arc(cx,cy,innerR,end,start,true); ctx.closePath();
        ctx.fillStyle = s.color; ctx.fill();
        start = end;
    });
    ctx.beginPath(); ctx.arc(cx,cy,innerR,0,Math.PI*2); ctx.fillStyle='#fff'; ctx.fill();
}
</script>
</body>
</html>
