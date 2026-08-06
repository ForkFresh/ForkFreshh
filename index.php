<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Home – Authentic African Delicacies Delivered Fresh';

$hero = $pdo->query("SELECT image_url FROM hero_settings ORDER BY id DESC LIMIT 1")->fetch();
$heroImg = $hero ? $hero['image_url'] : 'assets/images/IMG_8063.PNG';

$categories = $pdo->query("SELECT id, name, slug, image_url FROM categories WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();

$products = $pdo->query("
    SELECT p.id, p.name, p.slug, p.price, p.image_url, c.name AS cat_name
    FROM   products p
    JOIN   categories c ON c.id = p.category_id
    WHERE  p.is_featured = 1 AND p.is_available = 1
    ORDER  BY p.id ASC LIMIT 6
")->fetchAll();

require_once 'includes/header.php';
?>

<!-- HERO -->
<section class="hero" aria-label="Hero banner">
  <div class="container">
    <div class="hero-text">
      <h1>Authentic African<br><span class="highlight">Delicacies,</span><br>Delivered Fresh<br>to Your Doorstep.</h1>
      <p class="sub">Fresh ingredients. Rich flavors,<br>Fast delivery. 100% Convenience.</p>
      <div class="hero-actions">
        <a href="categories.php" class="btn btn-green">Order Now</a>
        <a href="login.php" class="btn btn-outline">Explore Meal Plans</a>
      </div>
    </div>
    <div clas ls="hero-visual">
      <img class="hero-main-img"
           src="<?= e($heroImg) ?>" m 
           alt="Authentic African dish"
           >

      <div class="hero-badge" aria-label="100% Fresh and Hygienic">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        100% Fresh<br>&amp; Hygienic
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<section class="trust-bar" aria-label="Trust features">
  <div class="container">
    <div class="trust-item"><i class="fa fa-truck-fast"></i><div><strong>Fast Delivery</strong> Across Cameroon</div></div>
    <div class="trust-item"><i class="fa fa-leaf"></i><div><strong>Fresh &amp; Natural</strong> Quality Ingredients</div></div>
    <div class="trust-item"><i class="fa fa-shield-halved"></i><div><strong>Secure Payment</strong> Safe &amp; Easy</div></div>
    <div class="trust-item"><i class="fa fa-heart"></i><div><strong>Healthy Meals</strong> Made with Love</div></div>
  </div>
</section>

<!-- CATEGORIES SECTION
 DEF of some terms:
 * lazy loading is a web design and programming technique that delays loading non-critical resource like images or scripts until the user actually needs them
 * URL Slug is the specific human-readable part of a web address that appears after the domain name, identifying a unique page, it improves search optimization  -->
<section class="categories-section">
  <div class="container">
    <h2 class="section-title">Shop by Category</h2>
    <?php if ($categories): ?>
    <div class="cat-grid" role="list" aria-label="Food categories">
      <?php foreach ($categories as $cat): ?>
      <a href="categories.php?slug=<?= urlencode($cat['slug']) ?>" class="cat-card" role="listitem" aria-label="<?= e($cat['name']) ?>">
        <img src="<?= e($cat['image_url']) ?>" alt="<?= e($cat['name']) ?>" loading="lazy"
             onerror="this.src='https://placehold.co/180x120/2e7d32/fff?text=<?= urlencode($cat['name']) ?>'">
        <span class="cat-label"><?= e($cat['name']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:black;">No categories found.</p>
    <?php endif; ?>
  </div>
</section>

<!-- POPULAR DISHES -->
<section class="products-section" id="popular-dishes">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Popular Dishes</h2>
      <a href="categories.php" class="view-all">View all <i class="fa fa-arrow-right"></i></a>
    </div>
    <?php if ($products): ?>
    <div class="product-grid" role="list" aria-label="Popular dishes">
      <?php foreach ($products as $prod): ?>
      <article class="product-card" role="listitem">
        <a href="categories.php?slug=<?= urlencode($prod['slug'] ?? '') ?>" tabindex="-1" aria-hidden="true">
          <img src="<?= e($prod['image_url'] ?? '') ?>" alt="<?= e($prod['name']) ?>" loading="lazy"
               onerror="this.src='https://placehold.co/200x120/f57c00/fff?text=<?= urlencode($prod['name']) ?>'">
        </a>
        <div class="product-body">
          <p class="product-name"><a href="categories.php"><?= e($prod['name']) ?></a></p>
          <p class="product-price"><?= formatPrice((float)$prod['price']) ?></p>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:black;">No featured products found.</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
