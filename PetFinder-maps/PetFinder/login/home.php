<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetFinder 🐾 — Home</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="nav">
    <div class="logo">PetFinder 🐾</div>
    <ul class="nav-links">
      <li><a href="home.php" class="active">Home</a></li>
      <li><a href="pets.php">Browse Pets</a></li>
      <li><a href="profile.php">My Profile</a></li>
      <li><a href="logout.php" class="logout-link">Logout</a></li>
    </ul>
  </nav>

  <header class="hero">
    <div>
      <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 🐾</h1>
      <h1 style="font-size:clamp(1.6rem,4vw,2.6rem); margin-top:8px;">Find Your Perfect Companion</h1>
      <p>Browse thousands of adoptable pets from shelters and families near you.<br>Every pet deserves a loving home.</p>
      <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a class="btn" href="pets.php">Browse Pets</a>
      </div>
    </div>
    <!-- Adoption steps -->
    <div class="steps" style="margin-top:48px;">
      <div class="step"><div class="step-num">1</div><div class="step-label">Browse Pets</div></div>
      <div class="step"><div class="step-num">2</div><div class="step-label">Contact Owner</div></div>
      <div class="step"><div class="step-num">3</div><div class="step-label">Meet & Greet</div></div>
      <div class="step"><div class="step-num">4</div><div class="step-label">Adopt & Love</div></div>
    </div>
  </header>

  <main>
    <div class="section">
      <div class="section-title">Why PetFinder?</div>
      <div class="section-sub">Everything you need to find, adopt, and connect with pet lovers.</div>
      <div class="features">
        <div class="feature-card">
          <div class="feature-icon">🐶</div>
          <h3>Adopt</h3>
          <p>Search dogs, cats, birds and more from local shelters and loving families ready to rehome.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔍</div>
          <h3>Smart Filters</h3>
          <p>Filter by species, age, gender and location to find exactly the pet that fits your life.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3>Community</h3>
          <p>Share stories, ask questions and connect with thousands of fellow pet lovers.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🏡</div>
          <h3>List a Pet</h3>
          <p>Have a pet that needs a new home? List them for free and find the perfect family.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📸</div>
          <h3>Pet Profiles</h3>
          <p>Every pet has a full profile with photos, personality traits and care information.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">❤️</div>
          <h3>Save Favorites</h3>
          <p>Track pets you love from your personal profile and never lose a great match.</p>
        </div>
      </div>
    </div>

    <!-- Stats section -->
    <div style="background:rgba(255,255,255,.15);backdrop-filter:blur(8px);padding:48px 24px;text-align:center;margin:0 0 0 0;">
      <div style="max-width:900px;margin:0 auto;">
        <div style="font-family:'Playfair Display',serif;font-size:1.9rem;font-weight:800;color:white;margin-bottom:32px;">Pets Finding Homes Every Day</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:24px;">
          <div><div style="font-size:2.4rem;font-weight:900;color:white;">500+</div><div style="color:rgba(255,255,255,.8);font-weight:700;">Pets Listed</div></div>
          <div><div style="font-size:2.4rem;font-weight:900;color:white;">1.2K</div><div style="color:rgba(255,255,255,.8);font-weight:700;">Happy Adopters</div></div>
          <div><div style="font-size:2.4rem;font-weight:900;color:white;">98%</div><div style="color:rgba(255,255,255,.8);font-weight:700;">Success Rate</div></div>
          <div><div style="font-size:2.4rem;font-weight:900;color:white;">24/7</div><div style="color:rgba(255,255,255,.8);font-weight:700;">Community Support</div></div>
        </div>
      </div>
    </div>

    <div class="section" style="text-align:center;padding:60px 24px;">
      <div class="section-title">Ready to find your best friend?</div>
      <div class="section-sub">Thousands of pets are waiting for a loving home right now.</div>
      <a class="btn" href="pets.php" style="font-size:1.1rem;padding:16px 40px;">Browse All Pets →</a>
    </div>
  </main>

  <footer class="footer">
    <p>© 2025 PetFinder 🐾 — Built with ♥ for animals everywhere</p>
  </footer>
</body>
</html>
