<?php
require_once dirname(__DIR__) . '/config/db.php';
requireRider();
$riderId = getCurrentRiderId();
$db      = getDB();
$ok = ''; $err = '';

$riderStmt = $db->prepare('SELECT * FROM riders WHERE id=? LIMIT 1');
$riderStmt->execute([$riderId]);
$rider = $riderStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $vehicle = trim($_POST['vehicle'] ?? 'motorcycle');
    $pass    = $_POST['new_password'] ?? '';
    if (!$name || !$phone) { $err = 'Name and phone are required.'; }
    else {
        $db->prepare('UPDATE riders SET name=?,phone=?,vehicle_type=? WHERE id=?')->execute([$name,$phone,$vehicle,$riderId]);
        if ($pass !== '') {
            if (strlen($pass) < 6) { $err = 'Password min 6 chars.'; }
            else $db->prepare('UPDATE riders SET password_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_BCRYPT),$riderId]);
        }
        if (!$err) { $ok = 'Profile updated.'; $riderStmt->execute([$riderId]); $rider = $riderStmt->fetch(); }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>My Profile – ForkFresh Rider</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head><body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo"><a href="dashboard.php" style="text-decoration:none;"><span class="logo-fork">fork</span><span class="logo-fresh">fresh</span></a></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
      <a href="my-orders.php" class="nav-item"><i class="fa-solid fa-bag-shopping"></i><span>My Orders</span></a>
      <a href="profile.php"   class="nav-item active"><i class="fa-solid fa-circle-user"></i><span>Profile</span></a>
      <a href="<?= BASE_URL ?>logout.php?role=rider" class="nav-item logout-item"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="main-wrap">
    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-greeting"><h1 class="greeting-title">My Profile</h1></div>
    </header>
    <div class="profile-page-wrap">
      <?php if ($ok): ?><div style="background:#e8f5e9;color:#2e7d32;padding:12px 16px;border-radius:8px;margin-bottom:16px;"><?= e($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div style="background:#fdecea;color:#c62828;padding:12px 16px;border-radius:8px;margin-bottom:16px;"><?= e($err) ?></div><?php endif; ?>
      <div class="profile-form-card">
        <div class="avatar-upload-area">
          <div class="avatar-upload-ring">
            <img id="profilePicPreview"
                 src="<?= e($rider['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($rider['name']).'&background=1a5c1a&color=fff&size=120') ?>"
                 alt="Profile picture">
          </div>
          <p class="avatar-hint">#<?= e($rider['rider_code']) ?></p>
        </div>
        <div class="form-divider"></div>
        <form method="POST" action="profile.php">
          <div class="form-row">
            <div class="form-group"><label class="form-label"><i class="fa-regular fa-user"></i> Full Name</label>
              <input type="text" name="name" class="form-input" value="<?= e($rider['name']) ?>" required></div>
            <div class="form-group"><label class="form-label"><i class="fa-solid fa-id-badge"></i> Rider ID</label>
              <input type="text" class="form-input form-input--readonly" value="#<?= e($rider['rider_code']) ?>" readonly></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label"><i class="fa-regular fa-envelope"></i> Email</label>
              <input type="email" class="form-input form-input--readonly" value="<?= e($rider['email']) ?>" readonly></div>
            <div class="form-group"><label class="form-label"><i class="fa-solid fa-phone"></i> Phone</label>
              <input type="tel" name="phone" class="form-input" value="<?= e($rider['phone']) ?>" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label"><i class="fa-solid fa-motorcycle"></i> Vehicle Type</label>
              <select name="vehicle" class="form-input form-select">
                <option value="motorcycle" <?= $rider['vehicle_type']==='motorcycle'?'selected':'' ?>>Motorcycle</option>
                <option value="bicycle"    <?= $rider['vehicle_type']==='bicycle'?'selected':'' ?>>Bicycle</option>
                <option value="car"        <?= $rider['vehicle_type']==='car'?'selected':'' ?>>Car</option>
              </select></div>
            <div class="form-group"><label class="form-label"><i class="fa-solid fa-star"></i> Rating</label>
              <input type="text" class="form-input form-input--readonly" value="<?= number_format((float)$rider['rating'],1) ?> ★" readonly></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label"><i class="fa-solid fa-lock"></i> New Password</label>
              <input type="password" name="new_password" class="form-input" placeholder="Leave blank to keep current"></div>
          </div>
          <div class="form-actions">
            <a href="dashboard.php" class="btn-cancel-profile">Cancel</a>
            <button type="submit" class="btn-save-profile"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle')?.addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('visible');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('visible');});
</script>
</body></html>
