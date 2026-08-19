<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'About Us';

$gallery = $pdo->query("SELECT image_url, alt_text FROM about_gallery ORDER BY sort_order ASC LIMIT 3")->fetchAll();
while (count($gallery) < 3) {
    $n = count($gallery) + 1;
    $gallery[] = ['image_url' => "https://placehold.co/300x300/2e7d32/fff?text=Dish+{$n}", 'alt_text' => "African dish {$n}"];
}
require_once 'includes/header.php';
?>
<section class="about-section" aria-labelledby="about-heading">
  <div class="container">
    <div class="about-grid">
      <div class="about-text">
        <h1 id="about-heading">About ForkFresh</h1>
        <p>ForkFresh is a Cameroonian food delivery platform dedicated to bringing you the best African Delicacies, Fresh, and Frozen. We offer meal plans and natural drinks – Delivered fast and fresh at your doorstep.</p>
        <ul class="feature-list" aria-label="Key features">
          <li class="feature-item"><i class="fa fa-gem"></i> Fresh &amp; Natural Ingredients</li>
          <li class="feature-item"><i class="fa fa-gem"></i> Hygienic Preparation</li>
          <li class="feature-item"><i class="fa fa-gem"></i> Fast &amp; Reliable Delivery</li>
          <li class="feature-item"><i class="fa fa-gem"></i> 100% Cameroonian</li>
        </ul>
      </div>
      <div class="about-collage" aria-label="Gallery of our dishes">
        <img class="collage-img collage-img-1" src="<?= e($gallery[0]['image_url']) ?>" alt="<?= e($gallery[0]['alt_text']) ?>"
            >
        <img class="collage-img collage-img-2" src="<?= e($gallery[1]['image_url']) ?>" alt="<?= e($gallery[1]['alt_text']) ?> ">
        <img class="collage-img collage-img-3" src="<?= e($gallery[2]['image_url']) ?>" alt="<?= e($gallery[2]['alt_text']) ?>"
            >
      </div>
    </div>

    <div class="stats-bar" aria-label="ForkFresh by the numbers">
      <div class="stat-item"><div class="stat-num" data-target="10000" data-suffix="K+">10K+</div><div class="stat-label">Happy Customers</div></div>
      <div class="stat-item"><div class="stat-num" data-target="500" data-suffix="+">500+</div><div class="stat-label">Dishes Delivered</div></div>
      <div class="stat-item"><div class="stat-num" data-target="20000" data-suffix="K+">20K+</div><div class="stat-label">Delivery Partners</div></div>
      <div class="stat-item"><div class="stat-num" data-target="20" data-suffix="+">20+</div><div class="stat-label">Cities Covered</div></div>
    </div>

    <div class="mv-grid" id="meal-plans">
      <div class="mv-card">
        <i class="fa fa-rocket"></i>
        <div><h3>Our Mission</h3><p>To make healthy African Meals accessible, affordable and convenient for everyone.</p></div>
      </div>
      <div class="mv-card">
        <i class="fa fa-handshake"></i>
        <div><h3>Our Vision</h3><p>To be the leading food delivery brand in Cameroon, going in for quality, trust and innovation.</p></div>
      </div>
    </div>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?>
