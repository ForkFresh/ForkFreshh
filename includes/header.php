<?php
// Start session and load DB if not already loaded
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/db.php';

// Determine current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ForkFresh – Authentic African Delicacies, Fresh &amp; Frozen, delivered fast to your doorstep across Cameroon.">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ForkFresh' : 'ForkFresh – African Delicacies Delivered Fresh' ?></title>


  <!-- Main stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <div class="container header-inner">

    <!-- Logo -->
    <div class="logo">
        <img src="assets/images/IMG_8023.PNG" alt="ForkFresh">
    </div>

    <!-- Primary navigation -->
    <nav class="main-nav" id="mainNav" aria-label="Primary navigation">
      <ul>
        <li><a href="<?= BASE_URL ?>index.php"     class="<?= $currentPage === 'index'     ? 'active' : '' ?>">Home</a></li>
        <li><a href="<?= BASE_URL ?>categories.php" class="<?= $currentPage === 'categories' ? 'active' : '' ?>">Categories</a></li>
        <li><a href="<?= BASE_URL ?>about.php"      class="<?= $currentPage === 'about'      ? 'active' : '' ?>">About Us</a></li>
        <li><a href="<?= BASE_URL ?>contact.php"    class="<?= $currentPage === 'contact'    ? 'active' : '' ?>">Contact</a></li>
      </ul>
    </nav>

    <!-- Search bar -->
    <form class="header-search" action="<?= BASE_URL ?>categories.php" method="GET" role="search">
      <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q"
             placeholder="Search food, dishes, e.g.…"
             value="<?= e($_GET['q'] ?? '') ?>"
             aria-label="Search food or dishes">
      <button type="submit" aria-label="Search"><i class="fa fa-arrow-right"></i></button>
    </form>

    <!-- Cart icon -->
      <a href="#" class="cart-btn" title="Cart">🛒</a>
     

    <!-- Login -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="<?= BASE_URL ?>account.php" class="login-btn">
        <i class="fa fa-user-circle"></i> <?= e($_SESSION['user_name']) ?>
      </a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>login.php" class="login-btn">
        <i class="fa fa-user-circle"></i> Login
      </a>
    <?php endif; ?>

  </div>
</header>
