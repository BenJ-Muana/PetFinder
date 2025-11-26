<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!Doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pet Finder 🐾</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
  <nav class="nav">
    <div class="logo">Pet Finder 🐾</div>
    <ul class="nav-links">
      <li><a href="index.html">Home</a></li>
      <li><a href="pets.html">Find Pets</a></li>
    </ul>
  </nav>

  <header class="hero">
    <div class="hero-inner">
      <h1>Find your next furry friend</h1>
      <p>Browse adoptable pets, learn about them, and start the adoption process.</p>
      <a class="btn" href="pets.html">Browse Pets</a>
    </div>
  </header>

  <main class="container">
    <section class="features">
      <article class="feature-card">
        <h3>Adopt</h3>
        <p>Search for dogs, cats and more from local shelters.</p>
      </article>
      <article class="feature-card">
        <h3>Filter</h3>
        <p>Filter by species, age, size and distance.</p>
      </article>
      <article class="feature-card">
        <h3>Support</h3>
        <p>Get tips for preparing your home and care guides.</p>
      </article>
    </section>
  </main>

  <footer class="footer">
    <p>© 2025 Pet Finder 🐾 — Built with ♥</p>
  </footer>
    <p><a href="logout.php">Logout</a></p>
  <script src="assets/js/script.js"></script>
</body>
</html>
