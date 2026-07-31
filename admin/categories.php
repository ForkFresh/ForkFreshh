<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Categories';
$ok = ''; $err = '';

if (isset($_GET['delete'])) { $db->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_GET['delete']]); redirect(BASE_URL.'admin/categories.php'); }
if (isset($_GET['toggle'])) { $db->prepare('UPDATE categories SET is_active=1-is_active WHERE id=?')->execute([(int)$_GET['toggle']]); redirect(BASE_URL.'admin/categories.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $img  = trim($_POST['image_url'] ?? '');
    $ord  = (int)($_POST['sort_order'] ?? 0);
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    if (!$name) { $err = 'Name is required.'; }
    else {
        if ($id) $db->prepare('UPDATE categories SET name=?,image_url=?,sort_order=? WHERE id=?')->execute([$name,$img,$ord,$id]);
        else     $db->prepare('INSERT INTO categories (name,slug,image_url,sort_order) VALUES (?,?,?,?)')->execute([$name,$slug,$img,$ord]);
        $ok = $id ? 'Category updated.' : 'Category added.';
    }
}
$categories = $db->query("SELECT c.*,(SELECT COUNT(*) FROM products WHERE category_id=c.id) AS product_count FROM categories c ORDER BY c.sort_order ASC")->fetchAll();
$editing = null;
if (isset($_GET['edit'])) { $es=$db->prepare('SELECT * FROM categories WHERE id=?'); $es->execute([(int)$_GET['edit']]); $editing=$es->fetch(); }
include 'partials/header.php';
?>
<?php if ($ok): ?><div class="alert-ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-err"><?= e($err) ?></div><?php endif; ?>

<div class="admin-table-wrap" style="margin-bottom:24px;">
  <div class="admin-table-head"><h2><?= $editing ? 'Edit Category' : 'Add Category' ?></h2><?php if ($editing): ?><a href="categories.php" class="btn-sm btn-outline-sm">Cancel</a><?php endif; ?></div>
  <div style="padding:20px;">
    <form method="POST" action="categories.php">
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
      <div class="form-grid-2">
        <div class="form-group"><label>Category Name *</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Image URL</label><input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="assets/images/cat.jpg"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? 0 ?>" min="0"></div>
      </div>
      <button type="submit" class="btn-sm btn-primary"><?= $editing ? 'Update' : 'Add Category' ?></button>
    </form>
  </div>
</div>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>All Categories</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
    <tr>
      <td><strong><?= e($c['name']) ?></strong></td>
      <td style="color:#888;font-size:.82rem;"><?= e($c['slug']) ?></td>
      <td><?= (int)$c['product_count'] ?></td>
      <td><?= (int)$c['sort_order'] ?></td>
      <td><a href="categories.php?toggle=<?= $c['id'] ?>" class="badge <?= $c['is_active']?'badge-green':'badge-red' ?>"><?= $c['is_active']?'Active':'Hidden' ?></a></td>
      <td style="display:flex;gap:6px;">
        <a href="categories.php?edit=<?= $c['id'] ?>" class="btn-sm btn-outline-sm">Edit</a>
        <a href="categories.php?delete=<?= $c['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete category?')">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'partials/footer.php'; ?>
