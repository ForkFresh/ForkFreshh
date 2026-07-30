<?php
$pageTitle = "About Us";
$active = "about";
require "header.php";
?>

<section class="page-banner">
    <div class="container">
        <h1>About Us</h1>
        <p>Learn more about ForkFresh</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-grid">
            <div class="about-text">
                <h2>About ForkFresh</h2>
                <p>
                    ForkFresh is a Cameroonian food delivery platform dedicated to bringing you the best
                    African Delicacies, Fresh, and Frozen. We offer meal plans and natural drinks —
                    Delivered fast and fresh at your doorstep.
                </p>
                <ul class="check-list">
                    <li><span>✓</span> Fresh & Natural Ingredients</li>
                    <li><span>✓</span> Hygienic Preparation</li>
                    <li><span>✓</span> Fast & Reliable Delivery</li>
                    <li><span>✓</span> 100% Cameroonian</li>
                </ul>
            </div>
            <div class="about-imgs">
                <div class="img-box">🍲</div>
                <div class="img-box">🥗</div>
                <div class="img-box">🥘</div>
                <div class="img-box">🥤</div>
            </div>
        </div>

        <div class="stats">
            <div class="stat"><div class="num">10K+</div><div class="lbl">Happy Customers</div></div>
            <div class="stat"><div class="num">500+</div><div class="lbl">Dishes Delivered</div></div>
            <div class="stat"><div class="num">20K+</div><div class="lbl">Delivery Partners</div></div>
            <div class="stat"><div class="num">20+</div><div class="lbl">Cities Covered</div></div>
        </div>

        <div class="mv-row">
            <div class="mv-card">
                <h3>🚀 Our Mission</h3>
                <p>To make healthy African Meals accessible, affordable and convenient for everyone.</p>
            </div>
            <div class="mv-card">
                <h3>💚 Our Vision</h3>
                <p>To be the leading food delivery brand in Cameroon, going in for quality, trust and innovation.</p>
            </div>
        </div>
    </div>
</section>

<?php require "footer.php"; ?>