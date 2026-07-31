<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Customers';

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id=?')->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/customers.php');
}

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count FROM users u WHERE 1';
$params = [];
if ($search !== '') { $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)'; $p="%$search%"; $params=[$p,$p,$p]; }
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $db->prepare($sql); $stmt->execute($params);
$customers = $stmt->fetchAll();
include 'partials/header.php';
?>

<form method="GET" action="customers.php" style="display:flex;gap:8px;max-width:400px;margin-bottom:20px;">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name or email…"
         style="flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:8px;font-size:.88rem;">
  <button type="submit" class="btn-sm btn-primary">Search</button>
  <?php if ($search): ?><a href="customers.php" class="btn-sm btn-outline-sm">Clear</a><?php endif; ?>
</form>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>Customers (<?= count($customers) ?>)</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
    <tr>
      <td><strong><?= e($c['first_name'].' '.$c['last_name']) ?></strong></td>
      <td><?= e($c['email']) ?></td>
      <td><?= e($c['phone'] ?? '—') ?></td>
      <td><?= (int)$c['order_count'] ?></td>
      <td><span class="badge <?= $c['is_active']?'badge-green':'badge-red' ?>"><?= $c['is_active']?'Active':'Suspended' ?></span></td>
      <td style="font-size:.8rem;color:#888;"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
      <td><a href="customers.php?toggle=<?= $c['id'] ?>" class="btn-sm <?= $c['is_active']?'btn-danger':'btn-primary' ?>"><?= $c['is_active']?'Suspend':'Activate' ?></a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'partials/footer.php'; ?>
