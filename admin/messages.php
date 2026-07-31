<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Messages';

// Mark as read
if (isset($_GET['read'])) { $db->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([(int)$_GET['read']]); redirect(BASE_URL.'admin/messages.php'); }
if (isset($_GET['delete'])) { $db->prepare('DELETE FROM contact_messages WHERE id=?')->execute([(int)$_GET['delete']]); redirect(BASE_URL.'admin/messages.php'); }
if (isset($_GET['read_all'])) { $db->query('UPDATE contact_messages SET is_read=1'); redirect(BASE_URL.'admin/messages.php'); }

$filter  = $_GET['filter'] ?? 'all';
$sql     = 'SELECT * FROM contact_messages';
if ($filter === 'unread') $sql .= ' WHERE is_read=0';
$sql    .= ' ORDER BY created_at DESC';
$messages = $db->query($sql)->fetchAll();
$unread  = (int)$db->query('SELECT COUNT(*) FROM contact_messages WHERE is_read=0')->fetchColumn();

// View single message
$viewing = null;
if (isset($_GET['view'])) { $vs=$db->prepare('SELECT * FROM contact_messages WHERE id=?'); $vs->execute([(int)$_GET['view']]); $viewing=$vs->fetch(); if ($viewing && !$viewing['is_read']) $db->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([$viewing['id']]); }
include 'partials/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <div style="display:flex;gap:8px;">
    <a href="messages.php?filter=all"    class="btn-sm <?= $filter==='all'?'btn-primary':'btn-outline-sm' ?>">All (<?= count($messages) ?>)</a>
    <a href="messages.php?filter=unread" class="btn-sm <?= $filter==='unread'?'btn-primary':'btn-outline-sm' ?>">Unread (<?= $unread ?>)</a>
  </div>
  <?php if ($unread > 0): ?><a href="messages.php?read_all=1" class="btn-sm btn-outline-sm">Mark All Read</a><?php endif; ?>
</div>

<?php if ($viewing): ?>
<div class="admin-table-wrap" style="margin-bottom:20px;">
  <div class="admin-table-head"><h2>Message from <?= e($viewing['name']) ?></h2><a href="messages.php" class="btn-sm btn-outline-sm">← Back</a></div>
  <div style="padding:24px;">
    <p style="font-size:.82rem;color:#888;margin-bottom:4px;">From: <strong><?= e($viewing['email']) ?></strong> | <?= date('M j, Y g:i A', strtotime($viewing['created_at'])) ?></p>
    <?php if ($viewing['subject']): ?><p style="font-weight:700;margin-bottom:10px;"><?= e($viewing['subject']) ?></p><?php endif; ?>
    <p style="line-height:1.7;color:#444;"><?= nl2br(e($viewing['message'])) ?></p>
    <div style="margin-top:18px;display:flex;gap:8px;">
      <a href="mailto:<?= e($viewing['email']) ?>?subject=Re: <?= e($viewing['subject'] ?? 'Your enquiry') ?>" class="btn-sm btn-primary"><i class="fa fa-reply"></i> Reply via Email</a>
      <a href="messages.php?delete=<?= $viewing['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>Contact Messages</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($messages as $m): ?>
    <tr style="<?= !$m['is_read']?'font-weight:600;background:#fffde7;':'' ?>">
      <td><?= e($m['name']) ?></td>
      <td><?= e($m['email']) ?></td>
      <td><?= e($m['subject'] ?: '(no subject)') ?></td>
      <td style="font-size:.8rem;color:#888;"><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
      <td><span class="badge <?= $m['is_read']?'badge-gray':'badge-orange' ?>"><?= $m['is_read']?'Read':'New' ?></span></td>
      <td style="display:flex;gap:6px;">
        <a href="messages.php?view=<?= $m['id'] ?>" class="btn-sm btn-primary">View</a>
        <?php if (!$m['is_read']): ?><a href="messages.php?read=<?= $m['id'] ?>" class="btn-sm btn-outline-sm">Mark Read</a><?php endif; ?>
        <a href="messages.php?delete=<?= $m['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete?')">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'partials/footer.php'; ?>
