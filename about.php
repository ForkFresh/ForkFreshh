<?php
/**
 * ForkFresh – About Us Page
 * Collage images are pulled from the about_gallery table in the database.
 */

require_once 'includes/db.php';
$pageTitle = 'About Us';

// ── Fetch about collage images (3 images ordered by sort_order) ───────────────
$gallery = $pdo->query("
    SELECT image_url, alt_text
    FROM   about_gallery
    ORDER  BY sort_order ASC
    LIMIT  3
")->fetchAll();

// Pad to exactly 3 entries so layout never breaks
while (count($gallery) < 3) {
    $n = count($gallery) + 1;
    $gallery[] = [
        'image_url' => "https://placehold.co/300x300/2e7d32/fff?text=Dish+{$n}",
        'alt_text'  => "African dish {$n}",
    ];
}

require_once 'includes/header.php';
?>

<!-- ============================================================
     ABOUT MAIN SECTION
     ============================================================ -->
<section class="about-section" aria-labelledby="about-heading">
  <div class="container">

    <!-- ── Top grid: text + collage ── -->
    <div class="about-grid">

      <!-- Left: text -->
      <div class="about-text">
        <h1 id="about-heading">About ForkFresh</h1>
        <p>
          ForkFresh is a Cameroonian food delivery platform dedicated to bringing you
          the best African Delicacies, Fresh, and Frozen. We offer meal plans and
          natural drinks – Delivered fast and fresh at your doorstep.
        </p>

        <ul class="feature-list" aria-label="Key features">
          <li class="feature-item">
            <i class="fa fa-gem" aria-hidden="true"></i>
            Fresh &amp; Natural Ingredients
          </li>
          <li class="feature-item">
            <i class="fa fa-gem" aria-hidden="true"></i>
            Hygienic Preparation
          </li>
          <li class="feature-item">
            <i class="fa fa-gem" aria-hidden="true"></i>
            Fast &amp; Reliable Delivery
          </li>
          <li class="feature-item">
            <i class="fa fa-gem" aria-hidden="true"></i>
            100% Cameroonian
          </li>
        </ul>
      </div>

      <!-- Right: collage images from DB ──────────────────────────────────
           about_gallery table stores image_url + alt_text for each photo.
           CSS positions them as an overlapping mosaic (3 photos).
      ─────────────────────────────────────────────────────────────────── -->
      <div class="about-collage" aria-label="Gallery of our dishes">

        <img
          class="collage-img collage-img-1"
          src="<?= e($gallery[0]['image_url']) ?>"
          alt="<?= e($gallery[0]['alt_text']) ?>"
         
        >

        <img
          class="collage-img collage-img-2"
          src="<?= e($gallery[1]['image_url']) ?>"
          alt="<?= e($gallery[1]['alt_text']) ?>"
       >

        <img
          class="collage-img collage-img-3"
          src="<?= e($gallery[2]['image_url']) ?>"
          alt="<?= e($gallery[2]['alt_text']) ?>"
        >

      </div>
    </div><!-- /.about-grid -->

    <!-- Stats bar-->

    <div class="stats-bar" aria-label="ForkFresh by the numbers">
      <div class="stat-item">
        <div class="stat-num" data-target="10000" data-suffix="K+">10K +</div>
        <div class="stat-label">Happy Customers</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="500" data-suffix="+">500+</div>
        <div class="stat-label">Dishes Delivered</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="20000" data-suffix="K+">20K+</div>
        <div class="stat-label">Delivery Partners</div>
      </div>
      <div class="stat-item">
        <div class="stat-num" data-target="20" data-suffix="+">20+</div>
        <div class="stat-label">Cities Covered</div>
      </div>
    </div>

    <!-- Mission & Vision-->
    <div class="mv-grid" id="meal-plans">

      <div class="mv-card">
        <i class="fa fa-rocket"></i>
        <div>
          <h3>Our Mission</h3>
          <p>To make healthy African Meals accessible, affordable and convenient for everyone.</p>
        </div>
      </div>

      <div class="mv-card">
        <i class="fa fa-handshake"></i>
        <div>
          <h3>Our Vision</h3>
          <p>
            To be the leading food delivery brand in Cameroon, going in for
            quality, trust and innovation.
          </p>
        </div>
      </div>

    </div><!-- /.mv-grid -->

  </div><!-- /.container -->
</section>

<?php require 'includes/footer.php'; ?>
