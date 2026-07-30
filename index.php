<?php
$pageTitle = "Home";
$active = "home";
require "db.php";
require "header.php";

$cats = mysqli_query($conn, "SELECT * FROM categories ORDER BY id");
$popular = mysqli_query($conn, "SELECT * FROM products WHERE is_popular = 1 AND is_active = 1 ORDER BY id LIMIT 6");
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <h1>Authentic African <span class="accent">Delicacies</span>, Delivered Fresh to Your Doorstep.</h1>
            <p class="subtitle">Fresh ingredients. Rich flavors. Fast delivery. 100% Convenience.</p>
            <div class="hero-btns">
                <a href="categories.php" class="btn btn-green">Order Now</a>
                <a href="categories.php?cat=meal-plans" class="btn btn-outline">Explore Meal Plans</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-plate"></div>
            <div class="float-item"></div>
            <div class="float-item"></div>
            <div class="float-item"></div>
            <div class="float-item"></div>
            <div class="fresh-badge">100% Fresh & Hygienic</div>
        </div>
    </div>
</section>

<div class="container">
    <div class="features">
        <div class="feature"> Fast Delivery Across Cameroon</div>
        <div class="feature"> Fresh & Natural Quality Ingredients</div>
        <div class="feature"> Secure Payment Safe & Easy</div>
        <div class="feature">Healthy Meals Made with Love</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Shop by Category</h2>
            <a href="categories.php">View all →</a>
        </div>
        <div class="cat-grid">
            <?php while ($c = mysqli_fetch_assoc($cats)): ?>
            <a href="categories.php?cat=<?= urlencode($c['slug']) ?>" class="cat-card">
                <div class="thumb">
                    <?php if (!empty($c['images_URL'])): ?>
                        <img src="<?= htmlspecialchars($c['images_URL']) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
                    <?php else: ?>
                        <span><?= $c['icon'] ?></span>
                    <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($c['name']) ?></h3>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="section" style="background:#FAFAFA;">
    <div class="container">
        <div class="section-head">
            <h2>Popular Dishes</h2>
            <a href="categories.php">View all →</a>
        </div>
        <div class="prod-grid">
            <?php while ($p = mysqli_fetch_assoc($popular)): ?>
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
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<?php require "footer.php"; ?>