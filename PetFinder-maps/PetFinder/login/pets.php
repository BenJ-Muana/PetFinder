<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) die("Database connection failed: " . mysqli_connect_error());

// Filters
$where = "WHERE p.status != 'adopted'";
$species_filter = isset($_GET['species']) ? mysqli_real_escape_string($connection, $_GET['species']) : '';
$gender_filter  = isset($_GET['gender'])  ? mysqli_real_escape_string($connection, $_GET['gender'])  : '';
$search         = isset($_GET['search'])  ? mysqli_real_escape_string($connection, $_GET['search'])  : '';

if ($species_filter) $where .= " AND p.species = '$species_filter'";
if ($gender_filter)  $where .= " AND p.gender = '$gender_filter'";
if ($search)         $where .= " AND (p.name LIKE '%$search%' OR p.breed LIKE '%$search%' OR p.location LIKE '%$search%')";

$query = "SELECT p.*, u.first_name, u.last_name FROM pets p JOIN users u ON p.user_id = u.id $where ORDER BY p.created_at DESC";
$result = mysqli_query($connection, $query);
$pets = [];
while ($row = mysqli_fetch_assoc($result)) $pets[] = $row;

$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetFinder 🐾 — Browse Pets</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    #pet-location-map { height: 260px; border-radius: 12px; margin-top: 8px; border: 2px solid rgba(255,255,255,0.3); }
    .map-hint { font-size:.8rem; color:var(--muted); margin-top:5px; }
    .mini-map { height: 160px; border-radius: 8px; margin-top: 10px; }
  </style>
</head>
<body>
  <nav class="nav">
    <div class="logo">PetFinder 🐾</div>
    <ul class="nav-links">
      <li><a href="home.php">Home</a></li>
      <li><a href="pets.php" class="active">Browse Pets</a></li>
      <li><a href="profile.php">My Profile</a></li>
      <li><a href="logout.php" class="logout-link">Logout</a></li>
    </ul>
  </nav>

  <header class="hero" style="padding:50px 20px 40px;">
    <h1 style="font-size:clamp(1.8rem,4vw,2.8rem);">Find Your Perfect Pet 🐾</h1>
    <p>Browse available pets ready for adoption. Each one is waiting for a loving home like yours.</p>
    <button class="btn" onclick="document.getElementById('addPetModal').classList.add('show')">
      + List a Pet for Adoption
    </button>
  </header>

  <?php if ($success): ?>
    <div id="toast" class="show">✅ <?php echo htmlspecialchars($success); ?></div>
    <script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),3500);</script>
  <?php endif; ?>
  <?php if ($error): ?>
    <div id="toast" class="show" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">❌ <?php echo htmlspecialchars($error); ?></div>
    <script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),3500);</script>
  <?php endif; ?>

  <div class="section">
    <!-- Filter Bar -->
    <form method="GET" class="filter-bar">
      <input type="text" name="search" placeholder="🔍  Search by name, breed, location..." value="<?php echo htmlspecialchars($search); ?>">
      <select name="species">
        <option value="">All Species</option>
        <option value="Dog"  <?php if($species_filter==='Dog')  echo 'selected'; ?>>🐶 Dogs</option>
        <option value="Cat"  <?php if($species_filter==='Cat')  echo 'selected'; ?>>🐱 Cats</option>
        <option value="Bird" <?php if($species_filter==='Bird') echo 'selected'; ?>>🐦 Birds</option>
        <option value="Rabbit" <?php if($species_filter==='Rabbit') echo 'selected'; ?>>🐰 Rabbits</option>
        <option value="Other" <?php if($species_filter==='Other') echo 'selected'; ?>>🐾 Other</option>
      </select>
      <select name="gender">
        <option value="">Any Gender</option>
        <option value="Male"   <?php if($gender_filter==='Male')   echo 'selected'; ?>>Male</option>
        <option value="Female" <?php if($gender_filter==='Female') echo 'selected'; ?>>Female</option>
      </select>
      <button class="btn btn-sm" type="submit">Filter</button>
      <?php if ($species_filter || $gender_filter || $search): ?>
        <a class="btn btn-sm btn-outline" href="pets.php" style="color:var(--primary);border-color:var(--primary);background:white;">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Results count -->
    <p style="color:rgba(255,255,255,.8);margin-bottom:20px;font-weight:700;">
      <?php echo count($pets); ?> pet<?php echo count($pets)!=1?'s':''; ?> found
    </p>

    <?php if (empty($pets)): ?>
      <div class="empty-state">
        <div class="empty-icon">🐾</div>
        <h3>No pets found</h3>
        <p style="margin-bottom:20px;">Try adjusting your filters or be the first to list a pet!</p>
        <button class="btn" onclick="document.getElementById('addPetModal').classList.add('show')">List a Pet</button>
      </div>
    <?php else: ?>
      <div class="grid-3">
        <?php foreach ($pets as $pet): ?>
          <div class="card pet-card">
            <?php if (!empty($pet['image_path']) && file_exists($pet['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($pet['image_path']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>">
            <?php else: ?>
              <div class="pet-img-placeholder">
                <?php
                  $icons = ['Dog'=>'🐶','Cat'=>'🐱','Bird'=>'🐦','Rabbit'=>'🐰'];
                  echo $icons[$pet['species']] ?? '🐾';
                ?>
              </div>
            <?php endif; ?>
            <div class="pet-card-body">
              <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                <span class="status-badge status-<?php echo $pet['status']; ?>"><?php echo ucfirst($pet['status']); ?></span>
              </div>
              <div>
                <span class="pet-tag species"><?php echo htmlspecialchars($pet['species']); ?></span>
                <?php if ($pet['breed']): ?><span class="pet-tag"><?php echo htmlspecialchars($pet['breed']); ?></span><?php endif; ?>
                <?php if ($pet['gender']): ?><span class="pet-tag gender"><?php echo htmlspecialchars($pet['gender']); ?></span><?php endif; ?>
                <?php if ($pet['age']): ?><span class="pet-tag"><?php echo $pet['age']; ?> yr<?php echo $pet['age']!=1?'s':''; ?></span><?php endif; ?>
              </div>
              <p style="color:var(--muted);font-size:.88rem;margin-top:10px;line-height:1.5;">
                <?php echo htmlspecialchars(mb_substr($pet['description'],0,90)); ?><?php echo mb_strlen($pet['description'])>90?'…':''; ?>
              </p>
              <div style="display:flex;align-items:center;gap:6px;margin-top:10px;color:var(--muted);font-size:.85rem;">
                <i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i>
                <?php echo htmlspecialchars($pet['location']); ?>
                <?php if (!empty($pet['landmark'])): ?>
                  <span style="color:var(--muted);font-size:.8rem;">· <?php echo htmlspecialchars($pet['landmark']); ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
              <div id="map-<?php echo $pet['id']; ?>" class="mini-map"></div>
              <?php endif; ?>
              <div style="font-size:.82rem;color:var(--muted);margin-top:4px;">
                Listed by <?php echo htmlspecialchars($pet['first_name'].' '.$pet['last_name']); ?>
              </div>
            </div>
            <div class="pet-card-footer">
              <?php if ($pet['user_id'] != $_SESSION['user_id']): ?>
              <button class="btn btn-sm" onclick="openInquiry(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars(addslashes($pet['name'])); ?>')">
                <i class="fas fa-envelope"></i> Contact Owner
              </button>
              <?php endif; ?>
              <?php if ($pet['user_id'] == $_SESSION['user_id']): ?>
                <?php if ($pet['status'] === 'available'): ?>
                  <form method="POST" action="mark_adopted.php" style="display:inline;">
                    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                    <button class="btn btn-sm" type="submit" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">✓ Mark Adopted</button>
                  </form>
                <?php endif; ?>
                <form method="POST" action="delete_pet.php" style="display:inline;" onsubmit="return confirm('Remove this pet listing?')">
                  <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ADD PET MODAL -->
  <div class="modal-overlay" id="addPetModal">
    <div class="modal-box">
      <div class="modal-header">
        <button class="modal-close" onclick="document.getElementById('addPetModal').classList.remove('show')">✕</button>
        <h2>🐾 List a Pet for Adoption</h2>
        <p>Fill in the details to help your pet find a loving home.</p>
      </div>
      <div class="modal-body">
        <form method="POST" action="add_pet_handler.php" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group">
              <label>Pet Name <span style="color:#e74c3c">*</span></label>
              <input type="text" name="pet_name" required placeholder="e.g. Buddy">
            </div>
            <div class="form-group">
              <label>Species <span style="color:#e74c3c">*</span></label>
              <select name="species" required>
                <option value="">Select species</option>
                <option value="Dog">🐶 Dog</option>
                <option value="Cat">🐱 Cat</option>
                <option value="Bird">🐦 Bird</option>
                <option value="Rabbit">🐰 Rabbit</option>
                <option value="Other">🐾 Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Breed</label>
              <input type="text" name="breed" placeholder="e.g. Golden Retriever">
            </div>
            <div class="form-group">
              <label>Age (years)</label>
              <input type="number" name="age" min="0" max="30" step="0.5" placeholder="e.g. 2">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Gender</label>
              <select name="gender">
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="form-group">
              <label>Location <span style="color:#e74c3c">*</span></label>
              <input type="text" name="location" id="locationText" required placeholder="e.g. Cebu City">
            </div>
          </div>
          <!-- Hidden lat/lng fields -->
          <input type="hidden" name="latitude"  id="inputLat">
          <input type="hidden" name="longitude" id="inputLng">
          <div class="form-group">
            <label>📍 Pin Adoption Location on Map</label>
            <div id="pet-location-map"></div>
            <p class="map-hint">Click anywhere on the map to drop a pin. The location field above will auto-fill.</p>
          </div>
          <div class="form-group">
            <label>Landmark / Notes <span style="font-weight:400;font-size:.85rem;">(optional)</span></label>
            <input type="text" name="landmark" placeholder="e.g. Near SM Seaside, beside Jollibee in Talisay">
          </div>
          <div class="form-group">
            <label>Contact Email <span style="color:#e74c3c">*</span></label>
            <input type="email" name="contact_email" required placeholder="your@email.com">
          </div>
          <div class="form-group">
            <label>Description <span style="color:#e74c3c">*</span></label>
            <textarea name="description" required placeholder="Describe your pet's personality, habits, health, and what makes them special..."></textarea>
          </div>
          <div class="form-group">
            <label>Pet Photo</label>
            <input type="file" name="pet_image" accept="image/*" style="background:white;">
          </div>
          <button class="btn" type="submit" style="width:100%;justify-content:center;">🐾 List for Adoption</button>
        </form>
      </div>
    </div>
  </div>

  <!-- CONTACT / INQUIRY MODAL -->
  <div class="modal-overlay" id="inquiryModal">
    <div class="modal-box" style="max-width:480px;">
      <div class="modal-header">
        <button class="modal-close" onclick="document.getElementById('inquiryModal').classList.remove('show')">✕</button>
        <h2>📬 Contact Owner</h2>
        <p id="inquirySubtitle">Send a message about this pet</p>
      </div>
      <div class="modal-body">
        <form method="POST" action="send_inquiry.php">
          <input type="hidden" name="pet_id" id="inquiryPetId">
          <div class="form-group">
            <label>Your Message</label>
            <textarea name="message" required placeholder="Hi! I'm interested in adopting your pet. Can you tell me more about them? Are they good with kids/other pets?" style="min-height:130px;"></textarea>
          </div>
          <p style="font-size:.83rem;color:var(--muted);margin-bottom:16px;">
            <i class="fas fa-info-circle"></i> The owner will receive your message in their profile inbox and can reply directly.
          </p>
          <button class="btn" type="submit" style="width:100%;">Send Message 📬</button>
        </form>
      </div>
    </div>
  </div>

  <div id="toast"></div>
  <footer class="footer"><p>© 2025 PetFinder 🐾 — Built with ♥</p></footer>
  <script>
    function openInquiry(petId, petName) {
      document.getElementById('inquiryPetId').value = petId;
      document.getElementById('inquirySubtitle').textContent = 'Send a message about ' + petName;
      document.getElementById('inquiryModal').classList.add('show');
    }

    // ── Add-Pet Map (Leaflet + OpenStreetMap, centered on Cebu City) ──
    let addMap, addMarker;
    function initAddMap() {
      if (addMap) return; // already init'd
      addMap = L.map('pet-location-map').setView([10.3157, 123.8854], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
      }).addTo(addMap);

      addMap.on('click', function(e) {
        const { lat, lng } = e.latlng;
        if (addMarker) addMarker.setLatLng(e.latlng);
        else addMarker = L.marker(e.latlng, { draggable: true }).addTo(addMap);

        addMarker.on('dragend', function() {
          const p = addMarker.getLatLng();
          document.getElementById('inputLat').value = p.lat.toFixed(7);
          document.getElementById('inputLng').value = p.lng.toFixed(7);
          reverseGeocode(p.lat, p.lng);
        });

        document.getElementById('inputLat').value = lat.toFixed(7);
        document.getElementById('inputLng').value = lng.toFixed(7);
        reverseGeocode(lat, lng);
      });
    }

    function reverseGeocode(lat, lng) {
      fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
        .then(r => r.json())
        .then(data => {
          if (data && data.display_name) {
            // Pull a short readable name: suburb/city
            const a = data.address;
            const short = [a.suburb, a.city_district, a.city, a.town, a.municipality]
              .filter(Boolean).slice(0, 2).join(', ') || data.display_name.split(',').slice(0,2).join(',');
            document.getElementById('locationText').value = short;
          }
        }).catch(() => {});
    }

    // Init the map when the modal opens
    document.getElementById('addPetModal').addEventListener('transitionend', function() {
      if (this.classList.contains('show')) {
        initAddMap();
        setTimeout(() => addMap && addMap.invalidateSize(), 100);
      }
    });
    document.querySelector('[onclick*="addPetModal"]') &&
      document.querySelector('[onclick*="addPetModal"]').addEventListener('click', function() {
        setTimeout(() => { initAddMap(); addMap && addMap.invalidateSize(); }, 300);
      });

    // ── Mini maps on pet cards ──
    <?php foreach ($pets as $pet): ?>
      <?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
      (function() {
        const m = L.map('map-<?php echo $pet['id']; ?>', { zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(m);
        const latlng = [<?php echo $pet['latitude']; ?>, <?php echo $pet['longitude']; ?>];
        m.setView(latlng, 15);
        L.marker(latlng).addTo(m)
          .bindPopup('<?php echo htmlspecialchars(addslashes($pet['name'])); ?><?php echo !empty($pet['landmark']) ? " · ".htmlspecialchars(addslashes($pet['landmark'])) : ""; ?>').openPopup();
      })();
      <?php endif; ?>
    <?php endforeach; ?>
  </script>
</body>
</html>