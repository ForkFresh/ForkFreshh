<?php
$pageTitle = "Categories";
$active = "categories";
require "db.php";
require "header.php";

$slug   = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';
$search = isset($_GET['q'])   ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

$allCats = mysqli_query($conn, "SELECT * FROM categories ORDER BY id");

$sql = "SELECT p.*, c.name AS cat_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1";

if ($slug !== '')   $sql .= " AND c.slug = '$slug'";
if ($search !== '') $sql .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
$sql .= " ORDER BY p.is_popular DESC, p.name";

$products = mysqli_query($conn, $sql);

$title = "All Products";
if ($slug !== '') {
    $r = mysqli_query($conn, "SELECT name FROM categories WHERE slug='$slug' LIMIT 1");
    if ($row = mysqli_fetch_assoc($r)) $title = $row['name'];
}
if ($search !== '') $title = "Search: " . htmlspecialchars($search);
?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2><?= $title ?></h2>
        </div>

        <div class="filter-bar">
            <a href="categories.php" class="<?= $slug === '' && $search === '' ? 'active' : '' ?>">All</a>
            <?php
            mysqli_data_seek($allCats, 0);
            while ($c = mysqli_fetch_assoc($allCats)):
            ?>
            <a href="categories.php?cat=<?= urlencode($c['slug']) ?>"
               class="<?= $slug === $c['slug'] ? 'active' : '' ?>">
                <?= htmlspecialchars($c['name']) ?>
            </a>
            <?php endwhile; ?>
        </div>

        <?php if (mysqli_num_rows($products) === 0): ?>
            <p style="text-align:center; color:#888; padding:50px 0;">No products found.</p>
        <?php else: ?>
        <div class="prod-grid">
            <?php while ($p = mysqli_fetch_assoc($products)): ?>
            <div class="prod-card">
                <div class="thumb">
                    <?php if (!empty($p['image'])): ?>
                        <img src="images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php endif; ?>
                </div>
                <div class="body">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="price"><?= number_format($p['price'], 0) ?> FCFA</div>
                    <p class="desc"><?= htmlspecialchars($p['description']) ?></p>
                    <?php if (!empty($p['cat_name'])): ?>
                    <small class="cat-label"><?= htmlspecialchars($p['cat_name']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require "footer.php"; ?>