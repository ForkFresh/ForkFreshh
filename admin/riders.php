<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Riders';
$ok = ''; $err = '';

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare('UPDATE riders SET is_active = 1 - is_active WHERE id=?')->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/riders.php');
}
// Add rider
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $code  = 'RDR' . strtoupper(substr(uniqid(), -4));
    if (!$name || !$email || !$phone || strlen($pass) < 6) {
        $err = 'All fields required and password min 6 chars.';
    } else {
        $chk = $db->prepare('SELECT id FROM riders WHERE email=? LIMIT 1'); $chk->execute([$email]);
        if ($chk->fetch()) { $err = 'Email already exists.'; }
        else {
            $db->prepare('INSERT INTO riders (rider_code,name,email,phone,password_hash) VALUES (?,?,?,?,?)')
               ->execute([$code,$name,$email,$phone,password_hash($pass,PASSWORD_BCRYPT)]);
            $ok = "Rider $name added (code: $code).";
        }
    }
}

$riders = $db->query("SELECT r.*, (SELECT COUNT(*) FROM orders WHERE rider_id=r.id AND order_status='delivered') AS total_delivered FROM riders r ORDER BY r.created_at DESC")->fetchAll();
include 'partials/header.php';
?>
<?php if ($ok): ?><div class="alert-ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-err"><?= e($err) ?></div><?php endif; ?>

<!-- Add Rider -->
<div class="admin-table-wrap" style="margin-bottom:24px;">
  <div class="admin-table-head"><h2>Add New Rider</h2></div>
  <div style="padding:20px;">
    <form method="POST" action="riders.php">
      <div class="form-grid-2">
        <div class="form-group"><label>Full Name *</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone *</label><input type="tel" name="phone" required></div>
        <div class="form-group"><label>Password * (min 6)</label><input type="password" name="password" required></div>
      </div>
      <button type="submit" class="btn-sm btn-primary">Add Rider</button>
    </form>
  </div>
</div>

<!-- Riders table -->
<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>All Riders (<?= count($riders) ?>)</h2></div>
  <table>
    <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th><th>Rating</th><th>Delivered</th><th>Active</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($riders as $r): ?>
    <tr>
      <td><strong>#<?= e($r['rider_code']) ?></strong></td>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><span class="badge <?= match($r['status']){'online'=>'badge-green','busy'=>'badge-orange',default=>'badge-gray'} ?>"><?= e($r['status']) ?></span></td>
      <td><?= number_format((float)$r['rating'],1) ?> ★</td>
      <td><?= (int)$r['total_delivered'] ?></td>
      <td><span class="badge <?= $r['is_active']?'badge-green':'badge-red' ?>"><?= $r['is_active']?'Yes':'No' ?></span></td>
      <td><a href="riders.php?toggle=<?= $r['id'] ?>" class="btn-sm <?= $r['is_active']?'btn-danger':'btn-primary' ?>"><?= $r['is_active']?'Suspend':'Activate' ?></a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'partials/footer.php'; ?>
