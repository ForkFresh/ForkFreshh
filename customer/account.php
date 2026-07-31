<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();
$userId = getCurrentUserId();
$user   = getCurrentUser();
$db     = getDB();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name']  ?? '');
    $phone = trim($_POST['phone']      ?? '');
    $pass  = $_POST['new_password']    ?? '';

    if ($first && $last) {
        $db->prepare('UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?')
           ->execute([$first, $last, $phone, $userId]);
        $_SESSION['user_name'] = $first . ' ' . $last;
        if ($pass !== '') {
            if (strlen($pass) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
                   ->execute([password_hash($pass, PASSWORD_BCRYPT), $userId]);
            }
        }
        if (!$error) {
            $success = 'Profile updated successfully.';
            $user = getCurrentUser();
        }
    }
}
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Account – ForkFresh</title>
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
        <div class="topbar-greeting"><h1>My Account</h1><p>Manage your profile details</p></div>
      </div>
      <div class="topbar-right"><div class="topbar-avatar"><?= $initials ?></div></div>
    </header>
    <main class="page-body" style="max-width:600px;">
      <?php if ($success): ?><div style="background:#e8f5e9;color:#2e7d32;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.9rem;"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div style="background:#fdecea;color:#c62828;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.9rem;"><?= e($error)   ?></div><?php endif; ?>
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;">
        <form method="POST" action="account.php">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
              <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">First Name</label>
              <input type="text" name="first_name" value="<?= h($user['first_name'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
            </div>
            <div>
              <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Last Name</label>
              <input type="text" name="last_name" value="<?= h($user['last_name'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
            </div>
          </div>
          <div style="margin-bottom:16px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Email (read-only)</label>
            <input type="email" value="<?= h($user['email'] ?? '') ?>" disabled style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;background:#f4f4f4;">
          </div>
          <div style="margin-bottom:16px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Phone</label>
            <input type="tel" name="phone" value="<?= h($user['phone'] ?? '') ?>" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
          </div>
          <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
          <p style="font-size:.88rem;font-weight:700;margin-bottom:12px;">Change Password</p>
          <div style="margin-bottom:16px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">New Password (leave blank to keep current)</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
          </div>
          <button type="submit" style="padding:12px 28px;background:var(--green-main);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.95rem;cursor:pointer;">Save Changes</button>
        </form>
      </div>
    </main>
    <?php include 'partials/footer.php'; ?>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
