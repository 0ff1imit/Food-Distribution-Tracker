<?php
session_start();
define('BASE_URL','..');
require_once BASE_URL.'/includes/db.php';
require_once BASE_URL.'/includes/auth.php';
require_once BASE_URL.'/includes/flash.php';
requireLogin();

$pageTitle = 'Edit Profile';
$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$uid]); $user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['form_type'] ?? 'profile';

    if ($type === 'profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name || !$email) { setFlash('warning','Name and email are required.'); header('Location: edit-profile.php'); exit; }

        // Handle profile picture upload
        $pictureName = $user['profile_picture'];
        if (!empty($_FILES['picture']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $ftype   = $_FILES['picture']['type'];
            if (!in_array($ftype, $allowed)) { setFlash('danger','Only JPG/PNG/GIF/WEBP allowed.'); header('Location: edit-profile.php'); exit; }
            if ($_FILES['picture']['size'] > 2*1024*1024) { setFlash('danger','Image must be under 2MB.'); header('Location: edit-profile.php'); exit; }
            $ext         = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $pictureName = 'user_'.$uid.'_'.time().'.'.$ext;
            $uploadPath  = BASE_URL.'/uploads/profiles/'.$pictureName;
            move_uploaded_file($_FILES['picture']['tmp_name'], __DIR__.'/../uploads/profiles/'.$pictureName);
        }

        $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,profile_picture=? WHERE id=?")
            ->execute([$name,$email,$phone,$pictureName,$uid]);
        $_SESSION['user_name']    = $name;
        $_SESSION['user_email']   = $email;
        $_SESSION['user_picture'] = $pictureName;
        setFlash('success','Profile updated successfully!');

    } elseif ($type === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) { setFlash('danger','Current password is incorrect.'); header('Location: edit-profile.php'); exit; }
        if (strlen($new) < 8) { setFlash('warning','New password must be at least 8 characters.'); header('Location: edit-profile.php'); exit; }
        if ($new !== $confirm) { setFlash('warning','Passwords do not match.'); header('Location: edit-profile.php'); exit; }
        $pdo->prepare("UPDATE users SET password=? WHERE id=")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>10]),$uid]);
        // Fix: proper syntax:
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>10]),$uid]);
        setFlash('success','Password changed successfully!');
    }
    header('Location: edit-profile.php'); exit;
}

$picSrcLarge = $user['profile_picture']
    ? BASE_URL.'/uploads/profiles/'.htmlspecialchars($user['profile_picture'])
    : 'https://ui-avatars.com/api/?name='.urlencode($user['full_name']).'&background=2563eb&color=fff&size=160';

include BASE_URL.'/includes/header.php';
?>

<div class="row g-4">
  <!-- Profile Card -->
  <div class="col-lg-4">
    <div class="card text-center p-4">
      <img src="<?= $picSrcLarge ?>" class="rounded-circle mx-auto mb-3"
           style="width:120px;height:120px;object-fit:cover;border:4px solid #2563eb" alt="Profile">
      <h5 class="fw-700 mb-0"><?= htmlspecialchars($user['full_name']) ?></h5>
      <span class="badge <?= roleBadgeClass($user['role']) ?> mt-2 mb-1"><?= ucfirst($user['role']) ?></span>
      <p class="text-muted small"><?= htmlspecialchars($user['email']) ?></p>
      <p class="text-muted small"><?= htmlspecialchars($user['phone'] ?? 'No phone set') ?></p>
      <p class="text-muted small">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
    </div>
  </div>

  <!-- Forms -->
  <div class="col-lg-8">
    <!-- Profile Info -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-person me-2 text-primary"></i>Update Profile Information</div>
      <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="form_type" value="profile">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-600">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" required
                     value="<?= htmlspecialchars($user['full_name']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required
                     value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Phone</label>
              <input type="text" name="phone" class="form-control"
                     value="<?= htmlspecialchars($user['phone']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Profile Picture</label>
              <input type="file" name="picture" class="form-control" accept="image/*"
                     onchange="previewImg(this)">
              <small class="text-muted">Max 2MB, JPG/PNG/GIF</small>
            </div>
            <div class="col-12" id="imgPreviewBox" style="display:none">
              <img id="imgPreview" src="" style="max-height:100px;border-radius:.5rem;border:2px solid #2563eb">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i>Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><i class="bi bi-shield-lock me-2 text-warning"></i>Change Password</div>
      <div class="card-body p-4">
        <form method="POST">
          <input type="hidden" name="form_type" value="password">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-600">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="8" required>
              <small class="text-muted">At least 8 characters</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning px-4">
                <i class="bi bi-key me-1"></i>Change Password
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreviewBox').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php include BASE_URL.'/includes/footer.php'; ?>