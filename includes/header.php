<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/db.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$cartCount   = 0;
if (!empty($_SESSION['user_id'])) {
    $cStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?');
    $cStmt->execute([$_SESSION['user_id']]);
    $cartCount = (int)$cStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ForkFresh – Authentic African Delicacies, Fresh &amp; Frozen, delivered fast across Cameroon.">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ForkFresh' : 'ForkFresh – African Delicacies Delivered Fresh' ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <div class="logo">
      <a href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/images/IMG_8023.PNG" alt="ForkFresh" onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.3rem;color:#2e7d32\'>fork<span style=\'color:#f57c00\'>fresh</span></span>'">
      </a>
    </div>
    <nav class="main-nav" id="mainNav" aria-label="Primary navigation">
      <ul>
        <li><a href="<?= BASE_URL ?>index.php"      class="<?= $currentPage==='index'      ? 'active':'' ?>">Home</a></li>
        <li><a href="<?= BASE_URL ?>categories.php" class="<?= $currentPage==='categories' ? 'active':'' ?>">Categories</a></li>
        <li><a href="<?= BASE_URL ?>about.php"      class="<?= $currentPage==='about'      ? 'active':'' ?>">About Us</a></li>
        <li><a href="<?= BASE_URL ?>contact.php"    class="<?= $currentPage==='contact'    ? 'active':'' ?>">Contact</a></li>
      </ul>
    </nav>
    <form class="header-search" action="<?= BASE_URL ?>categories.php" method="GET" role="search">
      <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q" placeholder="Search dishes…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search">
      <button type="submit" aria-label="Search"><i class="fa fa-arrow-right"></i></button>
    </form>
    <a href="<?= BASE_URL ?>customer/cart.php" class="cart-btn" title="Cart" aria-label="Cart">
      🛒<?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
    </a>
    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="<?= BASE_URL ?>customer/dashboard.php" class="login-btn">
        <i class="fa fa-user-circle"></i> <?= e(explode(' ', $_SESSION['user_name'])[0]) ?>
      </a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>login.php" class="login-btn">
        <i class="fa fa-user-circle"></i> Login
      </a>
    <?php endif; ?>
    <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
      <i class="fa fa-bars"></i>
    </button>
  </div>
</header>
