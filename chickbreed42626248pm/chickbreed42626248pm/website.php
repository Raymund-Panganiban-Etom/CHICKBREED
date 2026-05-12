<?php
// website.php - FarmConnect Marketplace Landing Page
// No backend required – static HTML/CSS/JS with dummy data and smooth animations
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FarmConnect – Find Local Chicken Sellers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ---------- RESET & GLOBAL ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fffaf2;
            color: #2d2a24;
            overflow-x: hidden;
        }

        /* ----- RED & YELLOW THEME VARIABLES ----- */
        :root {
            --red: #C62828;
            --red-dark: #B71C1C;
            --yellow: #F9A825;
            --yellow-dark: #F57F17;
            --cream: #fff5eb;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.04);
            --shadow-md: 0 8px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 12px 28px rgba(198,40,40,0.15);
        }

        /* smooth scroll behaviour */
        html {
            scroll-behavior: smooth;
        }

        /* ----- TYPOGRAPHY ----- */
        h1, h2, h3 {
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--red), var(--yellow));
            border-radius: 4px;
        }

        /* ----- BUTTONS / LINKS ----- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 60px;
            font-weight: 600;
            transition: all 0.25s ease;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
            background: white;
            color: var(--red);
            border: 1px solid #ffe0b2;
        }
        .btn-primary {
            background: linear-gradient(95deg, var(--red), var(--yellow));
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(198,40,40,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(198,40,40,0.3);
            background: linear-gradient(95deg, var(--red-dark), var(--yellow-dark));
        }
        .btn-outline {
            border: 1px solid var(--red);
            background: transparent;
            color: var(--red);
        }
        .btn-outline:hover {
            background: var(--red);
            color: white;
            transform: translateY(-2px);
        }

        /* ----- NAVBAR (glassmorphic) ----- */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 1rem 2rem;
        }
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--red);
        }
        .logo i {
            font-size: 2rem;
            color: var(--yellow);
        }
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            color: #3e2c1f;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: var(--red);
        }
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }

        /* ----- HERO SECTION (animated) ----- */
        .hero {
            background: linear-gradient(135deg, rgba(198,40,40,0.85), rgba(249,168,37,0.85)), url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDMiIGQ9Ik0wIDBoNDB2NDBIMHoiLz48L3N2Zz4=');
            background-size: cover;
            background-position: center;
            padding: 5rem 2rem;
            text-align: center;
            color: white;
        }
        .hero-content {
            max-width: 700px;
            margin: 0 auto;
            animation: fadeUp 0.8s ease-out;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        .search-bar {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 60px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .search-bar input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            font-size: 1rem;
            outline: none;
        }
        .search-bar button {
            background: var(--red);
            border: none;
            padding: 0 1.8rem;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .search-bar button:hover {
            background: var(--red-dark);
        }

        /* ----- CONTAINER & CARDS ----- */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-title h2:after {
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
        }

        /* Breed Gallery Grid */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .filter-select {
            padding: 0.6rem 1rem;
            border-radius: 60px;
            border: 1px solid #ffe0b2;
            background: white;
            font-weight: 500;
        }
        .breed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 2rem;
        }
        .breed-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .breed-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }
        .breed-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .breed-card:hover img {
            transform: scale(1.02);
        }
        .breed-info {
            padding: 1.2rem;
        }
        .breed-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
        }
        .price {
            font-weight: 700;
            color: var(--red);
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }
        .btn-buy {
            background: var(--yellow);
            color: #3e2c1f;
            width: 100%;
            padding: 0.6rem;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            transition: 0.2s;
        }
        .btn-buy:hover {
            background: var(--yellow-dark);
            transform: scale(0.98);
        }

        /* Seller Profiles */
        .sellers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
        }
        .seller-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }
        .seller-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .seller-img {
            height: 160px;
            background: #f0ede8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--red);
        }
        .seller-details {
            padding: 1.2rem;
        }
        .farm-name {
            font-weight: 700;
            font-size: 1.2rem;
        }
        .map-placeholder {
            background: #fff3e0;
            height: 100px;
            border-radius: 16px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #b34e1a;
            gap: 0.3rem;
        }

        /* Footer */
        footer {
            background: #1f1b16;
            color: #e2dcd5;
            padding: 2.5rem 2rem;
            margin-top: 3rem;
        }
        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        .footer-col h4 {
            color: var(--yellow);
            margin-bottom: 1rem;
        }
        .social-links a {
            color: #e2dcd5;
            margin-right: 1rem;
            font-size: 1.4rem;
            transition: 0.2s;
        }
        .social-links a:hover {
            color: var(--yellow);
        }

        /* ----- ANIMATIONS ----- */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s cubic-bezier(0.2, 0.9, 0.4, 1.1), transform 0.8s ease;
        }
        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
            }
            .nav-links {
                justify-content: center;
            }
            .container {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            
            <span><img src="Pic/Copilot_20260427_022306.png" alt="" height="150px" width="215px"></span>
        </div>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#">Marketplace</a>
            <a href="#">Sellers</a>
            <a href="#">Contact</a>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-outline" style="text-decoration: none;">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </div>
</nav>

<!-- HERO SECTION with search -->
<section class="hero">
    <div class="hero-content">
        <h1>Fresh chicken, right from the farm</h1>
        <p>Connect with local poultry sellers near you – free range, organic, and farm fresh.</p>
        <div class="search-bar">
            <input type="text" placeholder="📍 Find nearby chicken sellers... (demo)">
            <button><i class="fas fa-search"></i> Search</button>
        </div>
    </div>
</section>

<!-- BREED GALLERY with filters (static dummy data, animated) -->
<div class="container">
    <div class="section-title">
        <h2>Popular Breeds</h2>
        <p style="margin-top: 0.5rem; color: #6b4c3b;">Find your preferred chicken breed at the best price</p>
    </div>
    <div class="filter-bar">
        <select id="breedFilter" class="filter-select">
            <option value="all">All breeds</option>
            <option value="rhode">Rhode Island Red</option>
            <option value="leghorn">White Leghorn</option>
            <option value="sussex">Sussex</option>
        </select>
        <select id="priceFilter" class="filter-select">
            <option value="all">All prices</option>
            <option value="low">Under ₱300</option>
            <option value="mid">₱300 – ₱600</option>
            <option value="high">Above ₱600</option>
        </select>
        <select id="locationFilter" class="filter-select">
            <option value="all">All locations</option>
            <option value="north">North Luzon</option>
            <option value="south">South Luzon</option>
        </select>
    </div>
    <div class="breed-grid" id="breedGrid"></div>
</div>

<!-- SELLER PROFILES SECTION -->
<div class="container" style="background: var(--cream); border-radius: 48px;">
    <div class="section-title">
        <h2>Trusted Sellers Near You</h2>
        <p>Verified poultry farms with real location maps</p>
    </div>
    <div class="sellers-grid" id="sellersGrid"></div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-content">
        <div class="footer-col">
            <h4>Chickbreed</h4>
            <p>Connecting local farmers & buyers since 2026.</p>
        </div>
        <div class="footer-col">
            <h4>Quick links</h4>
            <p><a href="#" style="color:#e2dcd5; text-decoration:none;">Marketplace</a></p>
            <p><a href="#" style="color:#e2dcd5; text-decoration:none;">How it works</a></p>
            <p><a href="#" style="color:#e2dcd5; text-decoration:none;">Privacy policy</a></p>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p>📞 +63 912 345 6789</p>
            <p>✉️ hello@farmconnect.ph</p>
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
    <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem;">© 2026 Chickbreed – Fresh from the coop</div>
</footer>

<script>
    // ---------- DUMMY DATA FOR BREEDS ----------
    const breedsData = [
        { id: 1, name: "Rhode Island Red", price: 520, location: "north", type: "rhode", image: "https://placehold.co/400x300/f9a825/ffffff?text=Rhode+Island+Red" },
        { id: 2, name: "White Leghorn", price: 380, location: "south", type: "leghorn", image: "https://placehold.co/400x300/c62828/ffffff?text=White+Leghorn" },
        { id: 3, name: "Sussex", price: 650, location: "north", type: "sussex", image: "https://placehold.co/400x300/f9a825/ffffff?text=Sussex" },
        { id: 4, name: "Plymouth Rock", price: 450, location: "south", type: "rhode", image: "https://placehold.co/400x300/c62828/ffffff?text=Plymouth+Rock" },
        { id: 5, name: "Orpington", price: 720, location: "north", type: "sussex", image: "https://placehold.co/400x300/f9a825/ffffff?text=Orpington" },
        { id: 6, name: "Australorp", price: 490, location: "south", type: "leghorn", image: "https://placehold.co/400x300/c62828/ffffff?text=Australorp" }
    ];

    // Function to render breed cards with filters
    function renderBreeds() {
        const breedFilter = document.getElementById('breedFilter').value;
        const priceFilter = document.getElementById('priceFilter').value;
        const locationFilter = document.getElementById('locationFilter').value;

        let filtered = breedsData.filter(breed => {
            if (breedFilter !== 'all' && breed.type !== breedFilter) return false;
            if (locationFilter !== 'all' && breed.location !== locationFilter) return false;
            if (priceFilter === 'low' && breed.price >= 300) return false;
            if (priceFilter === 'mid' && (breed.price < 300 || breed.price > 600)) return false;
            if (priceFilter === 'high' && breed.price <= 600) return false;
            return true;
        });

        const grid = document.getElementById('breedGrid');
        grid.innerHTML = filtered.map(breed => `
            <div class="breed-card fade-up">
                <img src="${breed.image}" alt="${breed.name}">
                <div class="breed-info">
                    <h3>${breed.name}</h3>
                    <div class="price">₱${breed.price}</div>
                    <button class="btn-buy">Buy <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        `).join('');
        // re-attach observer for new cards
        observeFadeElements();
    }

    // ---------- SELLER PROFILES (with map placeholder) ----------
    const sellersData = [
        { farm: "Green Valley Poultry", owner: "Maria Santos", location: "Laguna", map: "📍 Calauan, Laguna", contact: "0917 123 4567" },
        { farm: "Golden Rooster Farm", owner: "Jun Reyes", location: "Bulacan", map: "📍 Bustos, Bulacan", contact: "0922 987 6543" },
        { farm: "Fresh Coop Agri", owner: "John Dela Cruz", location: "Pampanga", map: "📍 San Fernando, Pampanga", contact: "0945 567 8901" }
    ];

    function renderSellers() {
        const sellersContainer = document.getElementById('sellersGrid');
        sellersContainer.innerHTML = sellersData.map(seller => `
            <div class="seller-card fade-up">
                <div class="seller-img"><i class="fas fa-tractor fa-3x"></i></div>
                <div class="seller-details">
                    <div class="farm-name">${seller.farm}</div>
                    <p>👤 ${seller.owner}</p>
                    <div class="map-placeholder">
                        <i class="fas fa-map-marker-alt"></i> ${seller.map}
                    </div>
                    <button class="btn btn-outline" style="width:100%; margin-top: 8px;"><i class="fas fa-phone-alt"></i> Contact ${seller.contact}</button>
                </div>
            </div>
        `).join('');
        observeFadeElements();
    }

    // ---------- SCROLL ANIMATION (Intersection Observer) ----------
    function observeFadeElements() {
        const elements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        elements.forEach(el => observer.observe(el));
    }

    // ---------- INITIAL LOAD & EVENT LISTENERS ----------
    window.addEventListener('DOMContentLoaded', () => {
        renderBreeds();
        renderSellers();

        // filter event listeners
        document.getElementById('breedFilter').addEventListener('change', renderBreeds);
        document.getElementById('priceFilter').addEventListener('change', renderBreeds);
        document.getElementById('locationFilter').addEventListener('change', renderBreeds);
    });

    // re-run observer after dynamic update
    function observeFadeElements() {
        const elements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -20px 0px" });
        elements.forEach(el => observer.observe(el));
    }

    // add microinteraction on search button alert
    document.querySelector('.search-bar button')?.addEventListener('click', () => {
        alert('🐔 Demo: In full version, you would see nearby sellers based on your location.');
    });
</script>
</body>
</html>