<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection using mysqli
$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch all available pets
$query = "
    SELECT p.*, u.email as user_email
    FROM pets p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'available'  
    ORDER BY p.created_at DESC
";

$result = mysqli_query($connection, $query);
$pets = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pets[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pet Finder 🐾 — Pets</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root{
      --primary: #7C98B3;
      --secondary: #E8B4C4;
      --accent: #A8D5E2;
      --dark-text: #2C3E50;
      --deep: #5d7a94;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f5f5;
      min-height: 100vh;
    }

    /* ==================== NAVIGATION ==================== */
    .nav {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
    }

    .nav-links {
      display: flex;
      list-style: none;
      gap: 2rem;
      align-items: center;
    }

    .nav-links li {
      list-style: none;
    }

    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      transition: opacity 0.3s;
    }

    .nav-links a:hover {
      opacity: 0.8;
    }

    /* ==================== CONTAINER ==================== */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    /* ==================== SEARCH BAR ==================== */
    .search-bar {
      position: relative;
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .search-bar input {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid #e0f7fa;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .search-bar input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(124, 152, 179, 0.1);
    }

    .search-bar select {
      padding: 12px 16px;
      border: 2px solid #e0f7fa;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      background: white;
      min-width: 200px;
    }

    .search-bar select:focus {
      outline: none;
      border-color: var(--primary);
    }

    /* ==================== PET GRID ==================== */
    .pets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 24px;
    }

    .pet-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .pet-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }

    .pet-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .pet-info {
      padding: 20px;
    }

    .pet-info h3 {
      color: var(--dark-text);
      font-size: 1.5rem;
      margin: 0 0 16px 0;
    }

    .pet-details {
      margin-bottom: 16px;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .pet-details p {
      margin: 6px 0;
      color: var(--dark-text);
      font-size: 0.95rem;
    }

    .pet-description {
      color: #555;
      line-height: 1.6;
      margin: 16px 0;
      font-size: 0.95rem;
    }

    .pet-meta {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid #e8ebed;
    }

    .pet-meta p {
      margin: 8px 0;
      color: var(--dark-text);
      font-size: 0.9rem;
    }

    /* ==================== STATUS BADGE ==================== */
    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-badge.available {
      background: linear-gradient(135deg, #27ae60, #2ecc71);
      color: white;
    }

    .status-badge.adopted {
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      color: white;
    }

    /* ==================== PET ACTIONS ==================== */
    .pet-actions {
      display: flex;
      gap: 10px;
      margin-top: 16px;
      padding-top: 16px;
      border-top: 2px solid #e8ebed;
    }

    .btn-action {
      flex: 1;
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-adopted {
      background: linear-gradient(135deg, #27ae60, #2ecc71);
      color: white;
    }

    .btn-adopted:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(46, 204, 113, 0.4);
    }

    .btn-delete {
      background: linear-gradient(135deg, #e74c3c, #c0392b);
      color: white;
    }

    .btn-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
    }

    /* ==================== FLOATING ACTION BUTTON ==================== */
    .fab-container {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 999;
    }

    .fab-button {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px 28px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(124, 152, 179, 0.4);
      transition: all 0.3s ease;
    }

    .fab-button:hover {
      transform: translateY(-4px) scale(1.05);
      box-shadow: 0 14px 36px rgba(124, 152, 179, 0.5);
    }

    /* ==================== MODAL ==================== */
    .add-pet-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(8px);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      padding: 20px;
    }

    .add-pet-modal.show {
      display: flex;
    }

    .add-pet-modal-content {
      background: white;
      border-radius: 20px;
      width: 100%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
      padding: 30px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 20px 20px 0 0;
      position: relative;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.8rem;
    }

    .modal-close-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .modal-body {
      padding: 30px;
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark-text);
      font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e8ebed;
      border-radius: 10px;
      font-size: 1rem;
      font-family: inherit;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .image-upload-area {
      border: 2px dashed #cbd5e0;
      border-radius: 10px;
      padding: 30px;
      text-align: center;
      cursor: pointer;
    }

    .modal-footer {
      padding: 20px 30px;
      background: #f8f9fa;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .btn-cancel {
      padding: 12px 24px;
      background: transparent;
      border: 2px solid #95a5a6;
      color: #7f8c8d;
      border-radius: 10px;
      cursor: pointer;
    }

    .btn-submit {
      padding: 12px 32px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none;
      color: white;
      border-radius: 10px;
      cursor: pointer;
    }

    /* ==================== FOOTER ==================== */
    .footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 2rem;
      margin-top: 3rem;
    }

    /* ==================== NOTIFICATIONS ==================== */
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @media (max-width: 768px) {
      .pets-grid {
        grid-template-columns: 1fr;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <!-- NAVIGATION -->
  <nav class="nav">
    <div class="logo">Pet Finder 🐾</div>
    <ul class="nav-links">
      <li><a href="home.php">Home</a></li>
      <li><a href="pets.php">Find Pets</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <!-- SUCCESS/ERROR NOTIFICATIONS -->
  <?php if (isset($_SESSION['success'])): ?>
  <div style="position: fixed; top: 80px; right: 20px; background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(46, 204, 113, 0.4); z-index: 9999; animation: slideInRight 0.5s ease;">
    ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
  </div>
  <script>
    setTimeout(() => document.querySelector('[style*="slideInRight"]')?.remove(), 5000);
  </script>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
  <div style="position: fixed; top: 80px; right: 20px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(231, 76, 60, 0.4); z-index: 9999; animation: slideInRight 0.5s ease;">
    ❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
  </div>
  <script>
    setTimeout(() => document.querySelector('[style*="slideInRight"]')?.remove(), 5000);
  </script>
  <?php endif; ?>

  <!-- MAIN CONTENT -->
  <main class="container">
    <!-- SEARCH BAR -->
    <section class="search-bar">
      <input id="search" placeholder="Search by name, breed or location..." />
      <select id="species">
        <option value="">All species</option>
        <option value="Dog">Dog</option>
        <option value="Cat">Cat</option>
        <option value="Rabbit">Rabbit</option>
        <option value="Bird">Bird</option>
        <option value="Other">Other</option>
      </select>
    </section>

    <!-- PET GRID -->
    <section id="pets-grid" class="pets-grid">
      <?php if (empty($pets)): ?>
        <div style="text-align: center; padding: 60px 20px; grid-column: 1 / -1;">
          <div style="font-size: 4rem;">🐾</div>
          <h2>No pets available yet</h2>
          <p>Be the first to list a pet for adoption!</p>
        </div>
      <?php else: ?>
        <?php foreach ($pets as $pet): ?>
          <article class="pet-card">
            <?php if ($pet['image_path']): ?>
              <img src="<?php echo htmlspecialchars($pet['image_path']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" class="pet-image">
            <?php else: ?>
              <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); height: 200px; display: flex; align-items: center; justify-content: center; font-size: 4rem;">🐾</div>
            <?php endif; ?>
            
            <div class="pet-info">
              <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                <span class="status-badge <?php echo $pet['status']; ?>">
                  <?php echo $pet['status'] === 'adopted' ? '✅ Adopted' : '💚 Available'; ?>
                </span>
              </div>
              
              <div class="pet-details">
                <p><strong>Species:</strong> <?php echo htmlspecialchars($pet['species']); ?></p>
                <?php if ($pet['breed']): ?>
                  <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                <?php endif; ?>
                <?php if ($pet['age']): ?>
                  <p><strong>Age:</strong> <?php echo htmlspecialchars($pet['age']); ?> years</p>
                <?php endif; ?>
              </div>
              
              <p class="pet-description"><?php echo nl2br(htmlspecialchars($pet['description'])); ?></p>
              
              <div class="pet-meta">
                <p><strong>📍 Location:</strong> <?php echo htmlspecialchars($pet['location']); ?></p>
                <p><strong>📧 Contact:</strong> <?php echo htmlspecialchars($pet['contact_email']); ?></p>
              </div>
              
              <?php if ($pet['user_id'] == $_SESSION['user_id']): ?>
                <div class="pet-actions">
                  <?php if ($pet['status'] === 'available'): ?>
                    <button class="btn-action btn-adopted" onclick="markAsAdopted(<?php echo $pet['id']; ?>)">
                      ✅ Mark as Adopted
                    </button>
                  <?php endif; ?>
                  <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name'], ENT_QUOTES); ?>')">
                    🗑️ Delete
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <!-- ADD PET MODAL -->
  <div class="add-pet-modal" id="addPetModal">
    <div class="add-pet-modal-content">
      <div class="modal-header">
        <h2>🐾 Set Pet for Adoption</h2>
        <button class="modal-close-btn" onclick="closeAddPetModal()">×</button>
      </div>

      <form id="addPetForm" method="POST" action="add_pet_handler.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="form-group">
            <label>Pet Name *</label>
            <input type="text" name="pet_name" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Species *</label>
              <select name="species" required>
                <option value="">Select</option>
                <option value="Dog">Dog</option>
                <option value="Cat">Cat</option>
                <option value="Rabbit">Rabbit</option>
                <option value="Bird">Bird</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label>Breed</label>
              <input type="text" name="breed">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Age (years)</label>
              <input type="number" name="age" min="0" max="50" step="0.5">
            </div>

            <div class="form-group">
              <label>Gender</label>
              <select name="gender">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Description *</label>
            <textarea name="description" required></textarea>
          </div>

          <div class="form-group">
            <label>Location *</label>
            <input type="text" name="location" required>
          </div>

          <div class="form-group">
            <label>Contact Email *</label>
            <input type="email" name="contact_email" required>
          </div>

          <div class="form-group">
            <label>Pet Photo</label>
            <div class="image-upload-area" onclick="document.getElementById('petImage').click()">
              <div style="font-size: 3rem;">📷</div>
              <p>Click to upload image</p>
            </div>
            <input type="file" id="petImage" name="pet_image" accept="image/*" style="display: none;">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeAddPetModal()">Cancel</button>
          <button type="submit" class="btn-submit">List Pet</button>
        </div>
      </form>
    </div>
  </div>

  <!-- FLOATING ACTION BUTTON -->
  <div class="fab-container">
    <button class="fab-button" onclick="openAddPetModal()">
      <span>🐾</span>
      <span>Add Pet</span>
    </button>
  </div>

  <footer class="footer">
    <p>© 2025 Pet Finder 🐾</p>
  </footer>

  <script>
    function openAddPetModal() {
      document.getElementById('addPetModal').classList.add('show');
    }

    function closeAddPetModal() {
      document.getElementById('addPetModal').classList.remove('show');
    }

    function markAsAdopted(petId) {
      if (confirm('Mark this pet as adopted?')) {
        window.location.href = 'mark_adopted.php?id=' + petId;
      }
    }

    function confirmDelete(petId, petName) {
      if (confirm('Delete "' + petName + '"? This cannot be undone!')) {
        window.location.href = 'delete_pet.php?id=' + petId;
      }
    }

    // Close modal on outside click
    document.getElementById('addPetModal').addEventListener('click', function(e) {
      if (e.target === this) closeAddPetModal();
    });
  </script>
</body>
</html>