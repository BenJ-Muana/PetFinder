<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) die("Database connection failed.");
$uid = $_SESSION['user_id'];

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = 'Only image files allowed (jpg, png, gif, webp).';
        } elseif ($_FILES['avatar']['size'] > 3 * 1024 * 1024) {
            $_SESSION['error'] = 'Image must be under 3MB.';
        } else {
            $old = mysqli_fetch_assoc(mysqli_query($connection, "SELECT avatar FROM users WHERE id=$uid"));
            if (!empty($old['avatar']) && file_exists($old['avatar'])) unlink($old['avatar']);
            $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                mysqli_query($connection, "UPDATE users SET avatar='$dest' WHERE id=$uid");
                $_SESSION['success'] = 'Profile photo updated!';
            } else { $_SESSION['error'] = 'Upload failed. Try again.'; }
        }
    } else { $_SESSION['error'] = 'Please select an image.'; }
    header("Location: profile.php"); exit();
}

// Handle avatar color pick
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar_color'])) {
    $color = mysqli_real_escape_string($connection, $_POST['avatar_color']);
    // Remove any uploaded photo when choosing color
    $old = mysqli_fetch_assoc(mysqli_query($connection, "SELECT avatar FROM users WHERE id=$uid"));
    if (!empty($old['avatar']) && file_exists($old['avatar'])) unlink($old['avatar']);
    mysqli_query($connection, "UPDATE users SET avatar='', avatar_color='$color' WHERE id=$uid");
    $_SESSION['success'] = 'Avatar color updated!';
    header("Location: profile.php"); exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first = mysqli_real_escape_string($connection, $_POST['first_name']);
    $last  = mysqli_real_escape_string($connection, $_POST['last_name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    mysqli_query($connection, "UPDATE users SET first_name='$first', last_name='$last', email='$email' WHERE id=$uid");
    $_SESSION['user_name'] = $first;
    $_SESSION['success'] = 'Profile updated!';
    header("Location: profile.php"); exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $curr = $_POST['current_password'];
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    $row = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$uid"));
    if (!password_verify($curr, $row['password'])) { $_SESSION['error'] = 'Current password is incorrect.'; }
    elseif ($new !== $conf) { $_SESSION['error'] = 'New passwords do not match.'; }
    elseif (strlen($new) < 6) { $_SESSION['error'] = 'Password must be at least 6 characters.'; }
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($connection, "UPDATE users SET password='$hash' WHERE id=$uid");
        $_SESSION['success'] = 'Password changed successfully!';
    }
    header("Location: profile.php"); exit();
}

// Mark message as read
if (isset($_GET['read'])) {
    $mid = (int)$_GET['read'];
    mysqli_query($connection, "UPDATE pet_inquiries SET is_read=1 WHERE id=$mid AND pet_id IN (SELECT id FROM pets WHERE user_id=$uid)");
    header("Location: profile.php?tab=messages"); exit();
}

// Fetch data
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$uid"));
$my_pets = []; $r = mysqli_query($connection, "SELECT * FROM pets WHERE user_id=$uid ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($r)) $my_pets[] = $row;

$inbox = []; $r3 = mysqli_query($connection, "SELECT m.*, p.name as pet_name, u.first_name as sender_first, u.last_name as sender_last, u.avatar as sender_avatar FROM pet_inquiries m JOIN pets p ON m.pet_id=p.id JOIN users u ON m.sender_id=u.id WHERE p.user_id=$uid ORDER BY m.created_at DESC");
while ($row = mysqli_fetch_assoc($r3)) $inbox[] = $row;

$sent = []; $r4 = mysqli_query($connection, "SELECT m.*, p.name as pet_name, u.first_name as owner_first, u.last_name as owner_last FROM pet_inquiries m JOIN pets p ON m.pet_id=p.id JOIN users u ON p.user_id=u.id WHERE m.sender_id=$uid ORDER BY m.created_at DESC");
while ($row = mysqli_fetch_assoc($r4)) $sent[] = $row;

$unread_count = count(array_filter($inbox, fn($m) => !$m['is_read']));
$stats = [
    'total'    => count($my_pets),
    'adopted'  => count(array_filter($my_pets, fn($p) => $p['status']==='adopted')),
    'available'=> count(array_filter($my_pets, fn($p) => $p['status']==='available')),
];
$active_tab = $_GET['tab'] ?? 'pets';
$avatar_color = !empty($user['avatar_color']) ? $user['avatar_color'] : '#7C98B3';
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>PetFinder 🐾 — My Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .avatar-wrap { position:relative; display:inline-block; cursor:pointer; }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay {
      position:absolute; inset:0; border-radius:50%;
      background:rgba(0,0,0,.45); display:flex; flex-direction:column;
      align-items:center; justify-content:center; opacity:0;
      transition:opacity .25s; color:white; font-size:.72rem; font-weight:700; gap:3px;
    }
    .avatar-overlay i { font-size:1.3rem; }
    .avatar-photo { width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 4px 16px rgba(0,0,0,.15); }
    .color-swatches { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
    .swatch {
      width:36px; height:36px; border-radius:50%; cursor:pointer;
      border:3px solid transparent; transition:transform .2s, border-color .2s;
    }
    .swatch:hover, .swatch.selected { transform:scale(1.15); border-color:white; }
    .msg-card { background:white;border-radius:16px;padding:20px;box-shadow:0 4px 14px rgba(0,0,0,.07);margin-bottom:14px;border-left:4px solid var(--primary); }
    .msg-card.unread { border-left-color:var(--secondary); background:#fff9fb; }
    .mini-avatar { width:32px;height:32px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:.75rem;flex-shrink:0;overflow:hidden; }
    .mini-avatar img { width:100%;height:100%;object-fit:cover; }
    .reply-form { margin-top:12px;display:none; }
    .reply-form.open { display:flex;gap:8px; }
    .reply-form input { flex:1;padding:9px 14px;border:2px solid #e8ebed;border-radius:50px;font-family:'Nunito',sans-serif;font-size:.88rem;outline:none; }
    .reply-form input:focus { border-color:var(--primary); }
    .reply-form button { padding:9px 18px;background:var(--gradient);border:none;color:white;border-radius:50px;font-family:'Nunito',sans-serif;font-weight:700;cursor:pointer; }
  </style>
</head>
<body>
  <nav class="nav">
    <div class="logo">PetFinder 🐾</div>
    <ul class="nav-links">
      <li><a href="home.php">Home</a></li>
      <li><a href="pets.php">Browse Pets</a></li>
      <li><a href="profile.php" class="active">My Profile</a></li>
      <li><a href="logout.php" class="logout-link">Logout</a></li>
    </ul>
  </nav>

  <?php if ($success): ?>
    <div id="toast" class="show">✅ <?php echo htmlspecialchars($success); ?></div>
    <script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),3500);</script>
  <?php endif; ?>
  <?php if ($error): ?>
    <div id="toast" class="show" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">❌ <?php echo htmlspecialchars($error); ?></div>
    <script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),3500);</script>
  <?php endif; ?>

  <div class="section" style="padding-top:36px;">

    <!-- PROFILE HEADER -->
    <div class="profile-header">
      <!-- Avatar -->
      <div style="position:relative;">
        <div class="avatar-wrap" onclick="document.getElementById('avatarInput').click()" title="Click to upload photo">
          <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="avatar-photo" alt="Profile">
          <?php else: ?>
            <div class="profile-avatar" id="colorAvatar" style="background:<?php echo $avatar_color; ?>;">
              <?php echo strtoupper(substr($user['first_name'],0,1)); ?>
            </div>
          <?php endif; ?>
          <div class="avatar-overlay"><i class="fas fa-camera"></i><span>Change</span></div>
        </div>
        <!-- Hidden upload form -->
        <form method="POST" enctype="multipart/form-data" id="avatarUploadForm">
          <input type="hidden" name="update_avatar" value="1">
          <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none;" onchange="document.getElementById('avatarUploadForm').submit()">
        </form>
        <!-- Color picker toggle -->
        <button onclick="document.getElementById('colorPickerPanel').style.display=document.getElementById('colorPickerPanel').style.display==='block'?'none':'block'"
          style="position:absolute;bottom:-4px;right:-4px;background:white;border:2px solid #e8ebed;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:.85rem;display:flex;align-items:center;justify-content:center;" title="Pick avatar color">🎨</button>
      </div>

      <!-- Color picker panel -->
      <div id="colorPickerPanel" style="display:none;position:absolute;background:white;border-radius:16px;padding:16px 20px;box-shadow:0 8px 30px rgba(0,0,0,.15);z-index:200;margin-top:8px;">
        <p style="font-weight:800;font-size:.85rem;margin-bottom:10px;color:var(--dark-text);">Pick avatar color</p>
        <form method="POST" id="colorForm">
          <input type="hidden" name="update_avatar_color" value="1">
          <input type="hidden" name="avatar_color" id="chosenColor" value="<?php echo $avatar_color; ?>">
          <div class="color-swatches">
            <?php foreach(['#7C98B3','#E8B4C4','#A8D5E2','#f39c12','#27ae60','#e74c3c','#9b59b6','#1abc9c','#e67e22','#2c3e50','#e91e8c','#00bcd4'] as $c): ?>
              <div class="swatch <?php echo $avatar_color===$c?'selected':''; ?>" style="background:<?php echo $c; ?>;" onclick="pickColor('<?php echo $c; ?>')"></div>
            <?php endforeach; ?>
          </div>
          <button class="btn btn-sm" type="submit" style="margin-top:12px;width:100%;">Apply Color</button>
        </form>
      </div>

      <div class="profile-info">
        <h2><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></h2>
        <p><i class="fas fa-envelope" style="margin-right:6px;color:var(--secondary);"></i><?php echo htmlspecialchars($user['email']); ?></p>
        <p style="margin-top:4px;color:var(--muted);font-size:.87rem;"><i class="fas fa-calendar" style="margin-right:6px;"></i>Member since 2025</p>
        <div class="profile-stats">
          <div class="stat"><div class="stat-num"><?php echo $stats['total']; ?></div><div class="stat-label">Pets Listed</div></div>
          <div class="stat"><div class="stat-num"><?php echo $stats['adopted']; ?></div><div class="stat-label">Adopted</div></div>
          <div class="stat"><div class="stat-num"><?php echo $stats['available']; ?></div><div class="stat-label">Available</div></div>
        </div>
      </div>
      <button class="btn btn-sm" onclick="switchTab('settings',document.querySelectorAll('.tab-btn')[3])" style="margin-left:auto;">
        <i class="fas fa-edit"></i> Edit Profile
      </button>
    </div>

    <!-- TABS -->
    <div class="tabs">
      <button class="tab-btn <?php echo $active_tab==='pets'?'active':''; ?>"     onclick="switchTab('pets',this)">🐾 My Pets</button>
      <button class="tab-btn <?php echo $active_tab==='messages'?'active':''; ?>" onclick="switchTab('messages',this)">
        📬 Messages <?php if($unread_count>0): ?><span style="background:var(--secondary);color:white;padding:2px 8px;border-radius:20px;font-size:.75rem;margin-left:4px;"><?php echo $unread_count; ?></span><?php endif; ?>
      </button>
      <button class="tab-btn <?php echo $active_tab==='settings'?'active':''; ?>" onclick="switchTab('settings',this)">⚙️ Settings</button>
    </div>

    <!-- MY PETS TAB -->
    <div id="tab-pets" class="tab-content <?php echo $active_tab==='pets'?'active':''; ?>">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div style="color:rgba(255,255,255,.8);font-weight:700;"><?php echo $stats['total']; ?> listing<?php echo $stats['total']!=1?'s':''; ?></div>
        <a class="btn btn-sm" href="pets.php">+ Add New Pet</a>
      </div>
      <?php if (empty($my_pets)): ?>
        <div class="empty-state"><div class="empty-icon">🐾</div><h3>No pets listed yet</h3><p style="margin-bottom:20px;">Help a pet find a home!</p><a class="btn" href="pets.php">List a Pet</a></div>
      <?php else: ?>
        <div class="grid-3">
          <?php foreach ($my_pets as $pet): ?>
            <div class="card pet-card">
              <?php if (!empty($pet['image_path']) && file_exists($pet['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($pet['image_path']); ?>" alt="">
              <?php else: ?>
                <div class="pet-img-placeholder"><?php $ic=['Dog'=>'🐶','Cat'=>'🐱','Bird'=>'🐦','Rabbit'=>'🐰']; echo $ic[$pet['species']]??'🐾'; ?></div>
              <?php endif; ?>
              <div class="pet-card-body">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                  <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                  <span class="status-badge status-<?php echo $pet['status']; ?>"><?php echo ucfirst($pet['status']); ?></span>
                </div>
                <span class="pet-tag species"><?php echo htmlspecialchars($pet['species']); ?></span>
                <?php if($pet['breed']): ?><span class="pet-tag"><?php echo htmlspecialchars($pet['breed']); ?></span><?php endif; ?>
                <p style="color:var(--muted);font-size:.85rem;margin-top:8px;"><?php echo htmlspecialchars(mb_substr($pet['description'],0,70)); ?>…</p>
              </div>
              <div class="pet-card-footer">
                <?php if($pet['status']==='available'): ?>
                  <form method="POST" action="mark_adopted.php" style="display:inline;">
                    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                    <button class="btn btn-sm" type="submit" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">✓ Adopted</button>
                  </form>
                <?php endif; ?>
                <form method="POST" action="delete_pet.php" style="display:inline;" onsubmit="return confirm('Remove this listing?')">
                  <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <!-- MESSAGES TAB -->
    <div id="tab-messages" class="tab-content <?php echo $active_tab==='messages'?'active':''; ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;" class="msgs-grid">
        <!-- INBOX -->
        <div>
          <h3 style="color:white;font-weight:800;margin-bottom:16px;font-size:1.1rem;">
            📬 Inbox — About my pets
            <?php if($unread_count>0): ?><span style="background:var(--secondary);color:white;padding:2px 8px;border-radius:20px;font-size:.75rem;margin-left:6px;"><?php echo $unread_count; ?> new</span><?php endif; ?>
          </h3>
          <?php if (empty($inbox)): ?>
            <div class="empty-state" style="padding:40px 20px;"><div class="empty-icon" style="font-size:2.5rem;">📭</div><h3>No messages yet</h3><p>When someone contacts you about your pet, it'll show here.</p></div>
          <?php else: ?>
            <?php foreach ($inbox as $m):
              $ini=strtoupper(substr($m['sender_first'],0,1).substr($m['sender_last'],0,1));
              $td2=time()-strtotime($m['created_at']);
              $tl2=$td2<3600?floor($td2/60).'m ago':($td2<86400?floor($td2/3600).'h ago':date('M j',strtotime($m['created_at'])));
            ?>
              <div class="msg-card <?php echo !$m['is_read']?'unread':''; ?>">
                <?php if(!$m['is_read']): ?>
                  <a href="profile.php?read=<?php echo $m['id']; ?>&tab=messages" style="float:right;font-size:.78rem;color:var(--primary);font-weight:700;text-decoration:none;">Mark read ✓</a>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                  <div class="mini-avatar">
                    <?php if(!empty($m['sender_avatar']) && file_exists($m['sender_avatar'])): ?><img src="<?php echo htmlspecialchars($m['sender_avatar']); ?>" alt=""><?php else: echo $ini; endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:700;font-size:.9rem;"><?php echo htmlspecialchars($m['sender_first'].' '.$m['sender_last']); ?><?php if(!$m['is_read']): ?><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--secondary);margin-left:6px;vertical-align:middle;"></span><?php endif; ?></div>
                    <div style="color:var(--muted);font-size:.78rem;"><?php echo $tl2; ?></div>
                  </div>
                </div>
                <div style="color:var(--primary);font-weight:700;font-size:.85rem;margin-bottom:6px;">🐾 About: <?php echo htmlspecialchars($m['pet_name']); ?></div>
                <div style="font-size:.92rem;line-height:1.6;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                <?php if(!empty($m['reply'])): ?>
                  <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0;font-size:.85rem;color:var(--muted);font-style:italic;">↩ You replied: "<?php echo htmlspecialchars($m['reply']); ?>"</div>
                <?php else: ?>
                  <button class="post-action-btn" style="margin-top:10px;color:var(--primary);" onclick="toggleReply(<?php echo $m['id']; ?>)"><i class="fas fa-reply"></i> Reply</button>
                  <form method="POST" action="reply_inquiry.php" class="reply-form" id="reply-<?php echo $m['id']; ?>">
                    <input type="hidden" name="inquiry_id" value="<?php echo $m['id']; ?>">
                    <input type="text" name="reply" placeholder="Type your reply...">
                    <button type="submit">Send</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <!-- SENT -->
        <div>
          <h3 style="color:white;font-weight:800;margin-bottom:16px;font-size:1.1rem;">📤 Sent — My inquiries</h3>
          <?php if (empty($sent)): ?>
            <div class="empty-state" style="padding:40px 20px;"><div class="empty-icon" style="font-size:2.5rem;">📤</div><h3>No sent messages</h3><p>Messages you send about pets will appear here.</p></div>
          <?php else: ?>
            <?php foreach ($sent as $m):
              $td3=time()-strtotime($m['created_at']);
              $tl3=$td3<3600?floor($td3/60).'m ago':($td3<86400?floor($td3/3600).'h ago':date('M j',strtotime($m['created_at'])));
            ?>
              <div class="msg-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                  <div style="font-weight:700;font-size:.9rem;"><i class="fas fa-paper-plane" style="color:var(--primary);margin-right:6px;"></i>To: <?php echo htmlspecialchars($m['owner_first'].' '.$m['owner_last']); ?></div>
                  <span style="color:var(--muted);font-size:.78rem;"><?php echo $tl3; ?></span>
                </div>
                <div style="color:var(--primary);font-weight:700;font-size:.85rem;margin-bottom:6px;">🐾 About: <?php echo htmlspecialchars($m['pet_name']); ?></div>
                <div style="font-size:.92rem;line-height:1.6;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                <?php if(!empty($m['reply'])): ?>
                  <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0;font-size:.85rem;color:var(--primary);font-weight:700;">↩ Reply: "<?php echo htmlspecialchars($m['reply']); ?>"</div>
                <?php else: ?>
                  <div style="margin-top:8px;font-size:.82rem;color:var(--muted);font-style:italic;">⏳ Awaiting reply...</div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- SETTINGS TAB -->
    <div id="tab-settings" class="tab-content <?php echo $active_tab==='settings'?'active':''; ?>">
      <div class="grid-2" style="align-items:start;">
        <div style="background:rgba(255,255,255,.95);border-radius:20px;padding:28px;box-shadow:0 6px 20px rgba(0,0,0,.08);">
          <h3 style="font-weight:800;margin-bottom:20px;color:var(--primary);">📝 Update Info</h3>
          <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
            <button class="btn" type="submit" style="width:100%;">Save Changes</button>
          </form>
        </div>
        <div style="background:rgba(255,255,255,.95);border-radius:20px;padding:28px;box-shadow:0 6px 20px rgba(0,0,0,.08);">
          <h3 style="font-weight:800;margin-bottom:20px;color:var(--primary);">🔒 Change Password</h3>
          <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required placeholder="Current password"></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" required placeholder="At least 6 characters"></div>
            <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required placeholder="Repeat new password"></div>
            <button class="btn" type="submit" style="width:100%;">Change Password</button>
          </form>
        </div>
      </div>
      <div style="margin-top:20px;background:rgba(231,76,60,.08);border-radius:20px;padding:24px;border:2px solid rgba(231,76,60,.15);">
        <h3 style="font-weight:800;margin-bottom:10px;color:#e74c3c;">⚠️ Account Actions</h3>
        <p style="color:var(--muted);margin-bottom:14px;font-size:.9rem;">You can safely log out at any time.</p>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>

  </div>

  <div id="toast"></div>
  <footer class="footer"><p>© 2025 PetFinder 🐾 — Built with ♥</p></footer>

  <style>@media(max-width:768px){.msgs-grid{grid-template-columns:1fr!important;}}</style>
  <script>
    function switchTab(name, el) {
      document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.getElementById('tab-'+name).classList.add('active');
      el.classList.add('active');
    }
    function toggleReply(id) { document.getElementById('reply-'+id).classList.toggle('open'); }
    function pickColor(c) {
      document.getElementById('chosenColor').value = c;
      document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));
      event.target.classList.add('selected');
      const av = document.getElementById('colorAvatar');
      if (av) av.style.background = c;
    }
    // Close color picker when clicking outside
    document.addEventListener('click', function(e) {
      const panel = document.getElementById('colorPickerPanel');
      if (!panel) return;
      if (!panel.contains(e.target) && !e.target.closest('.avatar-wrap') && e.target.getAttribute('title') !== 'Pick avatar color') {
        panel.style.display = 'none';
      }
    });
  </script>
</body>
</html>