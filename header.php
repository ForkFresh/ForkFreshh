
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>ForkFresh</title>
    <link rel="stylesheet" href="project.css">
</head>
<body>
<header class="header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
           <img src="./logo.png" alt="ForkFresh logo">
            
        </a>

        <nav class="nav" id="nav">
            <a href="index.php" class="<?= ($active ?? '') === 'home' ? 'active' : '' ?>">Home</a>
            <a href="categories.php" class="<?= ($active ?? '') === 'categories' ? 'active' : '' ?>">Categories</a>
            <a href="about.php" class="<?= ($active ?? '') === 'about' ? 'active' : '' ?>">About Us</a>
            <a href="contact.php" class="<?= ($active ?? '') === 'contact' ? 'active' : '' ?>">Contact</a>
        </nav>

        <div class="header-right">
            <div class="search-wrap">
                <input type="text" id="searchInput" placeholder="Search food, dishes...">
                <button type="button" id="searchBtn">🔍</button>
            </div>
            <a href="#" class="cart-btn" title="Cart">🛒</a>
            <a href="#" class="login-btn">Login</a>
            <button class="burger" id="burger" aria-label="Menu">☰</button>
        </div>
    </div>
</header>

