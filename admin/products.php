<?php
require_once dirname(__DIR__) . '/config/db.php';
requireAdmin();
$db = getDB();
$pageTitle = 'Products';
$ok = ''; $err = '';

// Delete
if (isset($_GET['delete'])) {
    $db->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect(BASE_URL . 'admin/products.php');
}
// Toggle availability
if (isset($_GET['toggle'])) {
    $db->prepare('UPDATE products SET is_available = 1 - is_available WHERE id=?')->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/products.php');
}
// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name']        ?? '');
    $catId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price']    ?? 0);
    $desc  = trim($_POST['description'] ?? '');
    $img   = trim($_POST['image_url']   ?? '');
    $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();
    $feat  = isset($_POST['is_featured']) ? 1 : 0;

    if (!$name || !$catId || $price <= 0) {
        $err = 'Name, category and price are required.';
    } else {
        if ($id) {
            $db->prepare('UPDATE products SET name=?,category_id=?,price=?,description=?,image_url=?,is_featured=? WHERE id=?')
               ->execute([$name,$catId,$price,$desc,$img,$feat,$id]);
        } else {
            $db->prepare('INSERT INTO products (name,slug,category_id,price,description,image_url,is_featured) VALUES (?,?,?,?,?,?,?)')
               ->execute([$name,$slug,$catId,$price,$desc,$img,$feat]);
        }
        $ok = $id ? 'Product updated.' : 'Product added.';
    }
}

$products = $db->query("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC")->fetchAll();
$cats     = $db->query("SELECT id,name FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
$editing  = null;
if (isset($_GET['edit'])) {
    $es = $db->prepare('SELECT * FROM products WHERE id=?'); $es->execute([(int)$_GET['edit']]); $editing = $es->fetch();
}
include 'partials/header.php';
?>
<?php if ($ok): ?><div class="alert-ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-err"><?= e($err) ?></div><?php endif; ?>

<!-- Add/Edit form -->
<div class="admin-table-wrap" style="margin-bottom:24px;">
  <div class="admin-table-head"><h2><?= $editing ? 'Edit Product' : 'Add New Product' ?></h2><?php if ($editing): ?><a href="products.php" class="btn-sm btn-outline-sm">Cancel</a><?php endif; ?></div>
  <div style="padding:20px;">
    <form method="POST" action="products.php">
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
      <div class="form-grid-2">
        <div class="form-group"><label>Product Name *</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Category *</label>
          <select name="category_id">
            <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($editing['category_id'] ?? 0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Price (FCFA) *</label><input type="number" name="price" min="0" step="100" value="<?= $editing['price'] ?? '' ?>" required></div>
        <div class="form-group"><label>Image URL</label><input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="assets/images/dish.jpg"></div>
      </div>
      <div class="form-group"><label>Description</label><textarea name="description"><?= e($editing['description'] ?? '') ?></textarea></div>
      <label style="font-size:.85rem;"><input type="checkbox" name="is_featured" value="1" <?= !empty($editing['is_featured'])?'checked':'' ?>> Mark as Featured</label>
      <br><br>
      <button type="submit" class="btn-sm btn-primary"><?= $editing ? 'Update Product' : 'Add Product' ?></button>
    </form>
  </div>
</div>

<!-- Products table -->
<div class="admin-table-wrap">
  <div class="admin-table-head"><h2>All Products (<?= count($products) ?>)</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Featured</th><th>Available</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><strong><?= e($p['name']) ?></strong></td>
      <td><?= e($p['cat_name'] ?? '—') ?></td>
      <td><?= formatPrice((float)$p['price']) ?></td>
      <td><?= $p['is_featured'] ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-gray">No</span>' ?></td>
      <td><a href="products.php?toggle=<?= $p['id'] ?>" class="badge <?= $p['is_available']?'badge-green':'badge-red' ?>"><?= $p['is_available']?'Yes':'No' ?></a></td>
      <td style="display:flex;gap:6px;align-items:center;">
        <a href="products.php?edit=<?= $p['id'] ?>" class="btn-sm btn-outline-sm">Edit</a>
        <a href="products.php?delete=<?= $p['id'] ?>" class="btn-sm btn-danger"
           onclick="return confirm('Delete this product?')">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'partials/footer.php'; ?>
