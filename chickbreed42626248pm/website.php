<?php
// website.php – Modern marketing landing page for Chickbreed
// No session required – static page with links to register/login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Chickbreed – Raising Quality Poultry</title>
    <link rel="stylesheet" href="website.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
          <span>Chickbreed</span>
        </div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How it works</a>
            <a href="#showcase">Marketplace</a>
            <a href="register.php" class="btn btn-outline">Register</a>
            <a href="login.php" class="btn btn-primary">Login</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content fade-up">
        <h1><img src="Pic/Copilot_20260427_022306.png" alt="" height="160px " width="auto"></h1>
        <p>The easiest way to buy and sell chickens locally. List your flock, find nearby sellers, and connect instantly.</p>
        <div class="hero-buttons">
            <a href="sell.php" class="btn btn-primary btn-large"><i class="fas fa-plus-circle"></i> Start Selling</a>
            <a href="buy.php" class="btn btn-outline btn-large"><i class="fas fa-search"></i> Find Sellers</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<div class="container" id="features">
    <div class="section-title fade-up">
        <h2>Everything you need</h2>
    </div>
    <div class="features-grid">
        <div class="feature-card fade-up">
            <div class="feature-icon"><i class="fas fa-chicken"></i></div>
            <h3>List your chickens</h3>
            <p>Add photos, description, price, and location – reach buyers in your area.</p>
        </div>
        <div class="feature-card fade-up">
            <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
            <h3>Find nearby sellers</h3>
            <p>GPS‑powered search shows sellers within 20km. No more long drives.</p>
        </div>
        <div class="feature-card fade-up">
            <div class="feature-icon"><i class="fas fa-comments"></i></div>
            <h3>Easy communication</h3>
            <p>In‑app messaging, accept/ignore inquiries, and direct contact info sharing.</p>
        </div>
        <div class="feature-card fade-up">
            <div class="feature-icon"><i class="fas fa-search"></i></div>
            <h3>Smart search engine</h3>
            <p>Filter by breed, location, price – find exactly what you need.</p>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="container" id="how-it-works">
    <div class="section-title fade-up">
        <h2>How it works</h2>
    </div>
    <div class="steps-grid">
        <div class="step fade-up">
            <div class="step-number">1</div>
            <h3>Create an account</h3>
            <p>Sign up as a buyer or seller – it's free.</p>
        </div>
        <div class="step fade-up">
            <div class="step-number">2</div>
            <h3>List or search</h3>
            <p>Sellers list their chickens; buyers search by location and breed.</p>
        </div>
        <div class="step fade-up">
            <div class="step-number">3</div>
            <h3>Connect & trade</h3>
            <p>Send messages, negotiate, and arrange pickup or delivery.</p>
        </div>
    </div>
</div>

<!-- Animated Counters -->
<div class="container counter-section fade-up">
    <div class="counter-grid" id="counterGrid">
        <div class="counter-item">
            <h3><span class="counter" data-target="1520">0</span>+</h3>
            <p>Active Users</p>
        </div>
        <div class="counter-item">
            <h3><span class="counter" data-target="842">0</span>+</h3>
            <p>Listings Sold</p>
        </div>
        <div class="counter-item">
            <h3><span class="counter" data-target="124">0</span></h3>
            <p>Trusted Sellers</p>
        </div>
        <div class="counter-item">
            <h3><span class="counter" data-target="98">0</span>%</h3>
            <p>Satisfaction Rate</p>
        </div>
    </div>
</div>

<!-- Product Showcase (Chicken Breeds) -->
<div class="container" id="showcase">
    <div class="section-title fade-up">
        <h2>Popular breeds on Chickbreed</h2>
        <p style="margin-top: 0.5rem;">See what others are buying and selling</p>
    </div>
    <div class="product-grid">
        <div class="product-card fade-up">
            <img src="Pic/Dekalb_White_024_HG_612_3709.width-610.jpg" alt="Dekalb">
            <div class="product-info">
                <h4>Dekalb</h4>
                <p>Good for large egg production</p>
                <div class="product-price">₱350 – ₱650</div>
            </div>
        </div>
        <div class="product-card fade-up">
            <img src="Pic/White-Leghorn-Chicken-Hen-Surrey-and-West-Sussex-Point-of-Lay-POL-scaled.jpg" alt="White Leghorn">
            <div class="product-info">
                <h4>White Leghorn</h4>
                <p>Excellent egg layers</p>
                <div class="product-price">250 PHP</div>
            </div>
        </div>
        <div class="product-card fade-up">
            <img src="Pic/broiler.jpg" alt="Broiler">
            <div class="product-info">
                <h4>Broiler</h4>
                <p>Meat Production</p>
                <div class="product-price">₱124 - ₱150</div>
            </div>
        </div>
        <div class="product-card fade-up">
            <img src="Pic/texas.jpg" alt="Texas">
            <div class="product-info">
                <h4>Texas</h4>
                <p>Fighter</p>
                <div class="product-price">₱750 - ₱5k</div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="container">
    <div class="cta fade-up">
        <h2>Ready to start trading?</h2>
        <p>Join hundreds of local poultry farmers and buyers.</p>
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="register.php" class="btn btn-outline" style="border-color:white; color:white;">Register Now</a>
            <a href="buy.php" class="btn btn-primary" style="background:white; color:var(--red);">Explore Marketplace</a>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="footer-col">
            <h4>Chickbreed</h4>
            <p>Raising Quality Poultry</p>
        </div>
        <div class="footer-col">
            <h4>Quick links</h4>
            <p><a href="#features" style="color:#e2dcd5; text-decoration:none;">Features</a></p>
            <p><a href="#how-it-works" style="color:#e2dcd5; text-decoration:none;">How it works</a></p>
            <p><a href="sell.php" style="color:#e2dcd5; text-decoration:none;">Start Selling</a></p>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p>📞 +63 994 863 8249</p>
            <p>✉️ raymundetom59@gmail.com</p>
            <p>Developer: Raymund P. Etom</p>
        </div>
        <div class="footer-col">
            <h4>Follow us</h4>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </div>
    <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem;">© 2026 Chickbreed – Your trusted poultry marketplace</div>
</footer>

<script src="website.js"></script>
</body>
</html>