<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db        = getDB();
$pageTitle = 'Orders';

$statusFilter = $_GET['status'] ?? 'all';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $db->prepare('UPDATE orders SET order_status=? WHERE id=?')
       ->execute([trim($_POST['new_status']), (int)$_POST['order_id']]);
    redirect(BASE_URL . 'admin/orders.php?status=' . $statusFilter);
}

// Assign rider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    $db->prepare('UPDATE orders SET rider_id=?, order_status="assigned", assigned_at=NOW() WHERE id=?')
       ->execute([(int)$_POST['rider_id'], (int)$_POST['order_id']]);
    redirect(BASE_URL . 'admin/orders.php');
}

$sql = "SELECT o.*, u.first_name, u.last_name, r.name AS rider_name
        FROM orders o
        LEFT JOIN users  u ON u.id = o.user_id
        LEFT JOIN riders r ON r.id = o.rider_id";
$params = [];
if ($statusFilter !== 'all') { $sql .= ' WHERE o.order_status=?'; $params[] = $statusFilter; }
$sql .= ' ORDER BY o.placed_at DESC';
$stmt = $db->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$riders = $db->query("SELECT id, name, rider_code FROM riders WHERE is_active=1 AND status='online'")->fetchAll();

include 'partials/header.php';
?>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
  <?php foreach (['all','pending','assigned','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
  <a href="orders.php?status=<?= $s ?>"
     class="btn-sm <?= $statusFilter===$s?'btn-primary':'btn-outline-sm' ?>">
    <?= ucfirst(str_replace('_',' ',$s)) ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>Orders (<?= count($orders) ?>)</h2></div>
  <table>
    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Rider</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o):
      $bc = match($o['order_status']) { 'delivered'=>'badge-green','pending'=>'badge-orange','cancelled'=>'badge-red', default=>'badge-blue' };
    ?>
    <tr>
      <td><strong><?= e($o['order_number'] ?? '#'.$o['id']) ?></strong></td>
      <td><?= e(($o['first_name'] ?? '').' '.($o['last_name'] ?? '')) ?></td>
      <td><?= formatPrice((float)$o['total_amount']) ?></td>
      <td>
        <form method="POST" action="orders.php?status=<?= $statusFilter ?>" style="display:inline;">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <select name="new_status" onchange="this.form.submit()" style="border:1px solid #ddd;border-radius:6px;padding:4px 8px;font-size:.78rem;cursor:pointer;background:#fafafa;">
            <?php foreach (['pending','assigned','preparing','on_the_way','out_for_delivery','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $o['order_status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
      <td>
        <?php if ($o['rider_name']): ?>
          <span style="font-size:.83rem;"><?= e($o['rider_name']) ?></span>
        <?php else: ?>
          <form method="POST" action="orders.php" style="display:flex;gap:4px;">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <input type="hidden" name="assign_rider" value="1">
            <select name="rider_id" style="border:1px solid #ddd;border-radius:6px;padding:4px 6px;font-size:.78rem;">
              <option value="">Assign…</option>
              <?php foreach ($riders as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn-sm btn-primary" style="padding:4px 10px;">Go</button>
          </form>
        <?php endif; ?>
      </td>
      <td style="font-size:.8rem;color:#888;"><?= date('M j, g:ia', strtotime($o['placed_at'])) ?></td>
      <td>
        <?php if (!empty($o['rider_note'])): ?>
          <span title="<?= e($o['rider_note']) ?>" style="cursor:help;color:#888;font-size:.82rem;"><i class="fa fa-note-sticky"></i> Note</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'partials/footer.php'; ?>
