<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Categories';

$slug   = trim($_GET['slug'] ?? $_GET['cat'] ?? '');
$search = trim($_GET['q'] ?? '');

// All categories for filter bar
$allCats = $pdo->query("SELECT id, name, slug FROM categories WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();

// Build product query
$params = [];
$sql = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1";

if ($slug !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $slug;
}
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.is_popular DESC, p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Section title
$sectionTitle = 'All Products';
if ($slug !== '') {
    foreach ($allCats as $c) {
        if ($c['slug'] === $slug) { $sectionTitle = $c['name']; break; }
    }
}
if ($search !== '') $sectionTitle = 'Search: ' . $search;

require_once 'includes/header.php';
?>

<section class="page-banner" style="background:#fff8e1;padding:28px 0;">
  <div class="container">
    <h1 style="font-size:1.6rem;font-weight:800;"><?= e($sectionTitle) ?></h1>
  </div>
</section>

<section class="section">
  <div class="container">
    <!-- Filter bar -->
    <div class="filter-bar" style="margin-bottom:20px;">
      <a href="categories.php" class="filter-chip <?= $slug===''&&$search==='' ? 'active':'' ?>">All</a>
      <?php foreach ($allCats as $c): ?>
      <a href="categories.php?slug=<?= urlencode($c['slug']) ?>"
         class="filter-chip <?= $slug===$c['slug'] ? 'active':'' ?>">
        <?= e($c['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search bar -->
    <form method="GET" action="categories.php" style="margin-bottom:24px;display:flex;gap:8px;max-width:400px;">
      <?php if ($slug !== ''): ?>
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
      <?php endif; ?>
      <input type="search" name="q" value="<?= e($search) ?>"
             placeholder="Search products…"
             style="flex:1;padding:10px 14px;border:1px solid #e0e0e0;border-radius:8px;font-size:.9rem;">
      <button type="submit"
              style="padding:10px 18px;background:#2e7d32;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
        Search
      </button>
    </form>

    <?php if (count($products) === 0): ?>
      <p style="text-align:center;color:#888;padding:50px 0;">No products found.</p>
    <?php else: ?>
    <div class="prod-grid">
      <?php foreach ($products as $p): ?>
      <div class="prod-card">
        <div class="thumb">
          <img src="<?= e($p['image_url'] ?: 'assets/images/placeholder.jpg') ?>"
               alt="<?= e($p['name']) ?>"
               onerror="this.src='https://placehold.co/200x150/f57c00/fff?text=<?= urlencode($p['name']) ?>'">
        </div>
        <div class="body">
          <h3><?= e($p['name']) ?></h3>
          <div class="price"><?= number_format((float)$p['price'], 0) ?> FCFA</div>
          <p class="desc"><?= e($p['description'] ?? '') ?></p>
          <?php if (!empty($p['cat_name'])): ?>
          <small class="cat-label"><?= e($p['cat_name']) ?></small>
          <?php endif; ?>
          <form method="POST" action="customer/add-to-cart.php" style="margin-top:10px;">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="redirect" value="<?= e(BASE_URL . 'categories.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
            <button type="submit" class="btn-add-cart-pub">
              <i class="fa fa-cart-plus"></i> Add to Cart
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
.prod-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
.prod-card { background:#fff; border:1px solid #eee; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.07); display:flex; flex-direction:column; }
.prod-card .thumb { background:#fff3e0; width:100%; aspect-ratio:4/3; overflow:hidden; }
.prod-card .thumb img { width:100%; height:100%; object-fit:cover; }
.prod-card .body { padding:14px; display:flex; flex-direction:column; flex:1; }
.prod-card h3 { font-size:.95rem; margin-bottom:6px; color:#222; font-weight:600; }
.prod-card .price { color:#2e7d32; font-weight:700; font-size:.95rem; margin-bottom:6px; }
.prod-card .desc { font-size:.82rem; color:#666; flex:1; line-height:1.5; }
.prod-card .cat-label { display:inline-block; margin-top:8px; color:#999; font-size:.75rem; }
.filter-bar { display:flex; flex-wrap:wrap; gap:8px; }
.filter-chip { display:inline-block; padding:7px 16px; border:1px solid #ccc; border-radius:20px; font-size:.82rem; color:#555; text-decoration:none; transition:all .2s; }
.filter-chip.active,.filter-chip:hover { background:#2e7d32; color:#fff; border-color:#2e7d32; }
.btn-add-cart-pub { width:100%; padding:9px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-size:.85rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .2s; }
.btn-add-cart-pub:hover { background:#1b5e20; }
@media(max-width:768px){ .prod-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:480px){ .prod-grid{grid-template-columns:1fr;} }
</style>

<?php require_once 'includes/footer.php'; ?>
