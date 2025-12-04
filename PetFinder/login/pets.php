<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
  <link rel="stylesheet" href="style.css" />
   <style>
    :root{
      --primary: #7C98B3;
      --secondary: #E8B4C4;
      --accent: #A8D5E2;
      --dark-text: #2C3E50;
      --deep: #5d7a94;
    }

    /* ==================== ADD PET MODAL ==================== */
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
      animation: fadeInBg 0.3s ease;
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
      animation: modalSlideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      position: relative;
    }

    @keyframes modalSlideIn {
      0% {
        opacity: 0;
        transform: scale(0.9) translateY(-30px);
      }
      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
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
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .modal-header p {
      margin: 8px 0 0 0;
      opacity: 0.9;
      font-size: 0.95rem;
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
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .modal-close-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: rotate(90deg);
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
      font-size: 0.95rem;
    }

    .form-group label .required {
      color: #e74c3c;
      margin-left: 4px;
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
      color: var(--dark-text);
      background: #f8f9fa;
      transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--primary);
      background: white;
      box-shadow: 0 0 0 4px rgba(124, 152, 179, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
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
      background: #f8f9fa;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .image-upload-area:hover {
      border-color: var(--primary);
      background: #f0f4f8;
    }

    .image-upload-area.drag-over {
      border-color: var(--primary);
      background: rgba(124, 152, 179, 0.1);
    }

    .upload-icon {
      font-size: 3rem;
      margin-bottom: 12px;
    }

    .upload-text {
      color: #7f8c8d;
      font-size: 0.95rem;
    }

    .upload-text strong {
      color: var(--primary);
    }

    .image-preview {
      margin-top: 16px;
      display: none;
    }

    .image-preview.show {
      display: block;
    }

    .image-preview img {
      width: 100%;
      max-height: 200px;
      object-fit: cover;
      border-radius: 10px;
      border: 2px solid #e8ebed;
    }

    .remove-image {
      margin-top: 8px;
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .remove-image:hover {
      background: #c0392b;
    }

    .modal-footer {
      padding: 20px 30px;
      background: #f8f9fa;
      border-radius: 0 0 20px 20px;
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
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-cancel:hover {
      background: #ecf0f1;
      border-color: #7f8c8d;
      color: #2c3e50;
    }

    .btn-submit {
      padding: 12px 32px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none;
      color: white;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(124, 152, 179, 0.3);
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(124, 152, 179, 0.4);
    }

    .btn-submit:active {
      transform: translateY(0);
    }


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
      box-shadow: 
        0 8px 24px rgba(124, 152, 179, 0.4),
        0 4px 12px rgba(0, 0, 0, 0.15);
      transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      animation: fabPulse 2s ease-in-out infinite;
    }

    @keyframes fabPulse {
      0%, 100% {
        box-shadow: 
          0 8px 24px rgba(124, 152, 179, 0.4),
          0 4px 12px rgba(0, 0, 0, 0.15);
      }
      50% {
        box-shadow: 
          0 12px 32px rgba(124, 152, 179, 0.5),
          0 6px 16px rgba(0, 0, 0, 0.2);
      }
    }

    .fab-button:hover {
      transform: translateY(-4px) scale(1.05);
      box-shadow: 
        0 14px 36px rgba(124, 152, 179, 0.5),
        0 8px 20px rgba(0, 0, 0, 0.2);
      animation: none;
    }

    .fab-icon {
      font-size: 1.3rem;
      animation: fabIconBounce 1.5s ease-in-out infinite;
    }

    @keyframes fabIconBounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-3px); }
    }

    .fab-button::before {
      content: 'List your pet for adoption!';
      position: absolute;
      right: 100%;
      top: 50%;
      transform: translateY(-50%);
      margin-right: 12px;
      padding: 8px 16px;
      background: rgba(44, 62, 80, 0.95);
      color: white;
      border-radius: 8px;
      font-size: 0.85rem;
      white-space: nowrap;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      letter-spacing: 0;
      text-transform: none;
    }

    .fab-button:hover::before {
      opacity: 1;
    }

    @media (max-width: 768px) {
      .add-pet-modal-content {
        max-width: 95%;
      }

      .modal-header {
        padding: 20px;
      }

      .modal-header h2 {
        font-size: 1.4rem;
      }

      .modal-body {
        padding: 20px;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .fab-container {
        bottom: 20px;
        right: 20px;
      }

      .fab-button {
        padding: 14px 24px;
        font-size: 0.9rem;
      }

      .fab-button::before {
        display: none;
      }
    }

    .add-pet-modal-content::-webkit-scrollbar {
      width: 8px;
    }

    .add-pet-modal-content::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
    }

    .add-pet-modal-content::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 10px;
    }

    .pets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 24px;
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
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

    .status-badge.pending {
      background: linear-gradient(135deg, #f39c12, #f1c40f);
      color: white;
    }

    .status-badge.adopted {
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      color: white;
    }


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

    .btn-action:active {
      transform: translateY(0);
    }


@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}


.search-bar {
    position: relative;
}
  </style>
</head>
<body>
  <nav class="nav">
    <div class="logo">Pet Finder 🐾</div>
    <ul class="nav-links">
      <li><a href="home.php">Home</a></li>
      <li><a href="pets.php">Find Pets</a></li>
    </ul>
  </nav>

  <?php if (isset($_SESSION['success'])): ?>
  <div style="position: fixed; top: 80px; right: 20px; background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(46, 204, 113, 0.4); z-index: 9999; animation: slideInRight 0.5s ease;">
    ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
  </div>
  <script>
    setTimeout(function() {
      document.querySelector('[style*="slideInRight"]')?.remove();
    }, 5000);
  </script>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
  <div style="position: fixed; top: 80px; right: 20px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(231, 76, 60, 0.4); z-index: 9999; animation: slideInRight 0.5s ease;">
    ❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
  </div>
  <script>
    setTimeout(function() {
      document.querySelector('[style*="slideInRight"]')?.remove();
    }, 5000);
  </script>
  <?php endif; ?>

  <main class="container">
    <section class="search-bar">
      <input id="search" placeholder="Search by name, breed or location..." />
      <select id="species">
        <option value="">All species</option>
        <option value="Dog">Dog</option>
        <option value="Cat">Cat</option>
        <option value="Other">Other</option>
      </select>
    </section>

    <section id="pets-grid" class="pets-grid" aria-live="polite">
      <?php if (empty($pets)): ?>
        <div style="text-align: center; padding: 60px 20px; color: #7f8c8d; grid-column: 1 / -1;">
          <div style="font-size: 4rem; margin-bottom: 20px;">🐾</div>
          <h2 style="color: var(--dark-text); margin-bottom: 10px;">No pets available yet</h2>
          <p>Be the first to list a pet for adoption!</p>
        </div>
      <?php else: ?>
        <?php foreach ($pets as $pet): ?>
          <article class="pet-card">
            <?php if ($pet['image_path']): ?>
              <img src="<?php echo htmlspecialchars($pet['image_path']); ?>" 
                   alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                   class="pet-image">
            <?php else: ?>
              <div class="pet-image-placeholder" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; height: 200px; font-size: 4rem;">
                🐾
              </div>
            <?php endif; ?>
            
            <div class="pet-info">
              <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                
                <!-- Status Badge -->
                <?php if ($pet['status'] === 'adopted'): ?>
                  <span class="status-badge adopted">✅ Adopted</span>
                <?php elseif ($pet['status'] === 'pending'): ?>
                  <span class="status-badge pending">⏳ Pending</span>
                <?php else: ?>
                  <span class="status-badge available">💚 Available</span>
                <?php endif; ?>
              </div>
              
              <div class="pet-details">
                <p><strong>Species:</strong> <?php echo htmlspecialchars($pet['species']); ?></p>
                <?php if ($pet['breed']): ?>
                  <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed']); ?></p>
                <?php endif; ?>
                <?php if ($pet['age']): ?>
                  <p><strong>Age:</strong> <?php echo htmlspecialchars($pet['age']); ?> years</p>
                <?php endif; ?>
                <?php if ($pet['gender']): ?>
                  <p><strong>Gender:</strong> <?php echo htmlspecialchars($pet['gender']); ?></p>
                <?php endif; ?>
              </div>
              
              <p class="pet-description"><?php echo nl2br(htmlspecialchars($pet['description'])); ?></p>
              
              <div class="pet-meta">
                <p><strong>📍 Location:</strong> <?php echo htmlspecialchars($pet['location']); ?></p>
                <p><strong>📧 Contact:</strong> <?php echo htmlspecialchars($pet['contact_email']); ?></p>
                <p style="color: #95a5a6; font-size: 0.85rem; margin-top: 10px;">
                  Posted by: User #<?php echo htmlspecialchars($pet['user_id']); ?> • 
                  <?php echo date('M j, Y', strtotime($pet['created_at'])); ?>
                </p>
              </div>
              
              <!-- Action Buttons (only show if user is the owner) -->
              <?php if ($pet['user_id'] == $_SESSION['user_id']): ?>
                <div class="pet-actions">
                  <?php if ($pet['status'] === 'available'): ?>
                    <button class="btn-action btn-adopted" onclick="markAsAdopted(<?php echo $pet['id']; ?>)">
                      ✅ Mark as Adopted
                    </button>
                  <?php endif; ?>
                  
                  <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name'], ENT_QUOTES); ?>')">
                    🗑️ Delete Listing
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <div class="add-pet-modal" id="addPetModal">
    <div class="add-pet-modal-content">
      <div class="modal-header">
        <h2>🐾 Set Pet for Adoption</h2>
        <p>Fill in the details below to list your pet for adoption</p>
        <button class="modal-close-btn" onclick="closeAddPetModal()">×</button>
      </div>

      <form id="addPetForm" method="POST" action="add_pet_handler.php" enctype="multipart/form-data">
        <div class="modal-body">

          <div class="form-group">
            <label for="petName">Pet Name <span class="required">*</span></label>
            <input type="text" id="petName" name="pet_name" placeholder="e.g., Buddy, Luna, Max" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="petSpecies">Species <span class="required">*</span></label>
              <select id="petSpecies" name="species" required>
                <option value="">Select species</option>
                <option value="Dog">Dog</option>
                <option value="Cat">Cat</option>
                <option value="Rabbit">Rabbit</option>
                <option value="Bird">Bird</option>
                <option value="Hamster">Hamster</option>
                <option value="Guinea Pig">Guinea Pig</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label for="petBreed">Breed</label>
              <input type="text" id="petBreed" name="breed" placeholder="e.g., Golden Retriever">
            </div>
          </div>


          <div class="form-row">
            <div class="form-group">
              <label for="petAge">Age (years)</label>
              <input type="number" id="petAge" name="age" min="0" max="50" step="0.5" placeholder="e.g., 2">
            </div>

            <div class="form-group">
              <label for="petGender">Gender</label>
              <select id="petGender" name="gender">
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Unknown">Unknown</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="petDescription">Description <span class="required">*</span></label>
            <textarea id="petDescription" name="description" placeholder="Tell us about your pet's personality, behavior, health status, and why you're putting them up for adoption..." required></textarea>
          </div>

          <div class="form-group">
            <label for="petLocation">Location <span class="required">*</span></label>
            <input type="text" id="petLocation" name="location" placeholder="e.g., Cebu City, Philippines" required>
          </div>

          <div class="form-group">
            <label for="contactEmail">Contact Email <span class="required">*</span></label>
            <input type="email" id="contactEmail" name="contact_email" placeholder="your.email@example.com" required>
          </div>

          <div class="form-group">
            <label>Pet Photo</label>
            <div class="image-upload-area" id="imageUploadArea">
              <div class="upload-icon">📷</div>
              <div class="upload-text">
                <strong>Click to upload</strong> or drag and drop<br>
                <small>PNG, JPG, or GIF (max 5MB)</small>
              </div>
              <input type="file" id="petImage" name="pet_image" accept="image/*" style="display: none;">
            </div>
            <div class="image-preview" id="imagePreview">
              <img id="previewImg" src="" alt="Pet preview">
              <button type="button" class="remove-image" onclick="removeImage()">Remove Image</button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeAddPetModal()">Cancel</button>
          <button type="submit" class="btn-submit">List Pet for Adoption</button>
        </div>
      </form>
    </div>
  </div>

  <div class="fab-container">
    <button class="fab-button" onclick="openAddPetModal()">
      <span class="fab-icon">🐾</span>
      <span>Set for Adoption</span>
    </button>
  </div>

  <footer class="footer">
    <p>© 2025 Pet Finder 🐾</p>
  </footer>

  <script>

    function openAddPetModal() {
      document.getElementById('addPetModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function closeAddPetModal() {
      document.getElementById('addPetModal').classList.remove('show');
      document.body.style.overflow = 'auto';
      document.getElementById('addPetForm').reset();
      document.getElementById('imagePreview').classList.remove('show');
    }

    // Close modal when clicking outside
    document.getElementById('addPetModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeAddPetModal();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeAddPetModal();
      }
    });

    const imageUploadArea = document.getElementById('imageUploadArea');
    const petImageInput = document.getElementById('petImage');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    imageUploadArea.addEventListener('click', () => petImageInput.click());

    petImageInput.addEventListener('change', function(e) {
const file = e.target.files[0];
if (file) {
if (file.size > 5 * 1024 * 1024) {
alert('File is too large! Please choose an image under 5MB.');
return;
}
const reader = new FileReader();
reader.onload = function(e) {
previewImg.src = e.target.result;
imagePreview.classList.add('show');
};
reader.readAsDataURL(file);
}
});

imageUploadArea.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('drag-over');
});

imageUploadArea.addEventListener('dragleave', function() {
  this.classList.remove('drag-over');
});

imageUploadArea.addEventListener('drop', function(e) {
  e.preventDefault();
  this.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    petImageInput.files = e.dataTransfer.files;
    petImageInput.dispatchEvent(new Event('change'));
  }
});

function removeImage() {
  petImageInput.value = '';
  imagePreview.classList.remove('show');
  previewImg.src = '';
}

document.getElementById('addPetForm').addEventListener('submit', function(e) {

  const petName = document.getElementById('petName').value.trim();
  const species = document.getElementById('petSpecies').value;
  const description = document.getElementById('petDescription').value.trim();
  const location = document.getElementById('petLocation').value.trim();
  const contactEmail = document.getElementById('contactEmail').value.trim();
  
  if (!petName || !species || !description || !location || !contactEmail) {
    e.preventDefault();
    alert('Please fill in all required fields!');
    return false;
  }
  
  return true;
});

function markAsAdopted(petId) {
  if (confirm('Mark this pet as adopted? This will remove it from available listings.')) {
    window.location.href = 'mark_adopted.php?id=' + petId;
  }
}

function confirmDelete(petId, petName) {
  if (confirm('Are you sure you want to delete "' + petName + '"?\n\nThis action cannot be undone!')) {
    window.location.href = 'delete_pet.php?id=' + petId;
  }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const speciesFilter = document.getElementById('species');
    const petsGrid = document.getElementById('pets-grid');
    const petCards = Array.from(document.querySelectorAll('.pet-card'));

    function filterPets() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedSpecies = speciesFilter.value.toLowerCase();

        let visibleCount = 0;

        petCards.forEach(card => {
            const petName = card.querySelector('.pet-info h3').textContent.toLowerCase();
            const petSpecies = card.querySelector('.pet-details p strong').nextSibling.textContent.toLowerCase().trim();
            
            let petBreed = '';
            const breedElement = card.querySelector('.pet-details p:nth-child(2)');
            if (breedElement && breedElement.textContent.includes('Breed:')) {
                petBreed = breedElement.textContent.replace('Breed:', '').toLowerCase().trim();
            }
            
            const locationElement = card.querySelector('.pet-meta p:first-child');
            const petLocation = locationElement ? locationElement.textContent.toLowerCase() : '';
            
            const descriptionElement = card.querySelector('.pet-description');
            const petDescription = descriptionElement ? descriptionElement.textContent.toLowerCase() : '';

            const matchesSearch = searchTerm === '' || 
                petName.includes(searchTerm) || 
                petBreed.includes(searchTerm) || 
                petLocation.includes(searchTerm) ||
                petDescription.includes(searchTerm);


            const matchesSpecies = selectedSpecies === '' || petSpecies.includes(selectedSpecies);

            if (matchesSearch && matchesSpecies) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.4s ease';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });


        showNoResultsMessage(visibleCount);
    }


    function showNoResultsMessage(visibleCount) {
        const existingMessage = document.getElementById('no-results-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (visibleCount === 0 && petCards.length > 0) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'no-results-message';
            noResultsDiv.style.cssText = 'text-align: center; padding: 60px 20px; color: #7f8c8d; grid-column: 1 / -1;';
            noResultsDiv.innerHTML = `
                <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                <h2 style="color: var(--dark-text); margin-bottom: 10px;">No pets found</h2>
                <p>Try adjusting your search or filters</p>
            `;
            petsGrid.appendChild(noResultsDiv);
        }
    }

    searchInput.addEventListener('input', filterPets);
    speciesFilter.addEventListener('change', filterPets);

    searchInput.addEventListener('input', function() {
        if (this.value) {
            if (!document.getElementById('clear-search')) {
                const clearBtn = document.createElement('button');
                clearBtn.id = 'clear-search';
                clearBtn.innerHTML = '✕';
                clearBtn.style.cssText = `
                    position: absolute;
                    right: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(124, 152, 179, 0.2);
                    border: none;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    font-size: 14px;
                    cursor: pointer;
                    color: #7C98B3;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                `;
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    this.remove();
                    filterPets();
                });
                clearBtn.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(124, 152, 179, 0.4)';
                });
                clearBtn.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(124, 152, 179, 0.2)';
                });
                searchInput.parentElement.style.position = 'relative';
                searchInput.parentElement.appendChild(clearBtn);
            }
        } else {
            const clearBtn = document.getElementById('clear-search');
            if (clearBtn) clearBtn.remove();
        }
    });
});
</script>
</body>
</html>
