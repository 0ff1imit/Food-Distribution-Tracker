<?php
session_start();
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/flash.php';
define('BASE_URL', '/donation_system');

// Already logged in? Redirect
if (isset($_SESSION['user_id'])) {
    header('Location: '.$_SESSION['user_role'].'/dashboard.php'); exit;
}

$errors = [];
$success = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'phone'     => trim($_POST['phone'] ?? ''),
        'role'      => $_POST['role'] ?? 'volunteer',
        'password'  => $_POST['password'] ?? '',
        'confirm'   => $_POST['confirm_password'] ?? '',
    ];

    // --- Validation ---
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Name must be at least 3 characters.';
    }

    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$formData['email']]);
        if ($check->fetch()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (!empty($formData['phone']) && !preg_match('/^[\+\d\s\-\(\)]{7,20}$/', $formData['phone'])) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    if (!in_array($formData['role'], ['volunteer','procurement'])) {
        $errors['role'] = 'Invalid role selected.';
    }

    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $formData['password'])) {
        $errors['password'] = 'Must include at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $formData['password'])) {
        $errors['password'] = 'Must include at least one number.';
    }

    if ($formData['password'] !== $formData['confirm']) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($formData['password'], PASSWORD_BCRYPT, ['cost' => 10]);
        $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role, status) VALUES (?,?,?,?,?,'pending')")
            ->execute([
                $formData['full_name'],
                $formData['email'],
                $hash,
                $formData['phone'],
                $formData['role'],
            ]);
        $success = true;
        $formData = []; // Clear form
    }
}

// Password strength helper
function pwStrength(string $pw): array {
    $score = 0;
    $checks = [
        'length'    => strlen($pw) >= 8,
        'uppercase' => (bool)preg_match('/[A-Z]/', $pw),
        'number'    => (bool)preg_match('/[0-9]/', $pw),
        'special'   => (bool)preg_match('/[\W_]/', $pw),
    ];
    $score = array_sum($checks);
    $labels = [0=>'','1'=>'Very Weak','2'=>'Weak','3'=>'Fair','4'=>'Strong'];
    $colors = [0=>'','1'=>'danger','2'=>'warning','3'=>'info','4'=>'success'];
    return ['score'=>$score,'label'=>$labels[$score]??'','color'=>$colors[$score]??''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account – DonateMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #7c3aed 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
    font-family: 'Segoe UI', sans-serif;
  }
  .register-card {
    background: #fff;
    border-radius: 1.25rem;
    box-shadow: 0 24px 64px rgba(0,0,0,.28);
    overflow: hidden;
    max-width: 560px;
    width: 100%;
    margin: auto;
  }
  .register-header {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    color: #fff;
  }
  .form-body { padding: 2rem; }
  .form-control, .form-select {
    border-radius: .6rem;
    padding: .65rem 1rem;
    border-color: #e2e8f0;
    transition: border-color .2s, box-shadow .2s;
  }
  .form-control:focus, .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 .2rem rgba(37,99,235,.18);
  }
  .form-control.is-invalid { border-color: #dc3545; }
  .form-control.is-valid   { border-color: #198754; }
  .btn-register {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    border: none;
    border-radius: .65rem;
    padding: .75rem;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: .01em;
  }
  .btn-register:hover { opacity: .92; transform: translateY(-1px); }
  .role-card {
    border: 2px solid #e2e8f0;
    border-radius: .75rem;
    padding: 1rem;
    cursor: pointer;
    transition: .2s;
  }
  .role-card:hover { border-color: #2563eb; background: #eff6ff; }
  .role-card.selected { border-color: #2563eb; background: #eff6ff; }
  .role-card input[type=radio] { accent-color: #2563eb; }
  .pw-meter { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
  .pw-meter-bar { height: 100%; border-radius: 3px; transition: width .4s, background .4s; }
  .step-badge {
    width: 28px; height: 28px; border-radius: 50%;
    background: #2563eb; color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; margin-right: .5rem;
  }
  .success-screen { text-align: center; padding: 3rem 2rem; }
  .success-icon { font-size: 5rem; color: #22c55e; animation: popIn .5s ease; }
  @keyframes popIn { 0%{transform:scale(0)} 80%{transform:scale(1.15)} 100%{transform:scale(1)} }
</style>
</head>
<body>

<div class="register-card">
  <!-- Header -->
  <div class="register-header">
    <i class="bi bi-heart-fill text-danger fs-1 mb-2 d-block"></i>
    <h4 class="fw-800 mb-0">DonateMS</h4>
    <small class="opacity-75">Create Your Account</small>
  </div>

  <?php if ($success): ?>
  <!-- Success Screen -->
  <div class="success-screen">
    <div class="success-icon mb-3"><i class="bi bi-check-circle-fill"></i></div>
    <h4 class="fw-700 text-success mb-2">Account Request Submitted!</h4>
    <p class="text-muted mb-1">Thanks for signing up, <strong><?= htmlspecialchars($_POST['full_name'] ?? 'there') ?></strong>!</p>
    <p class="text-muted mb-4">Your account is <strong>pending admin approval</strong>. You'll be able to log in once an administrator activates your account.</p>
    <div class="alert alert-info d-flex align-items-center gap-2 text-start">
      <i class="bi bi-info-circle-fill fs-5"></i>
      <span>An admin can approve your account from the <strong>User Management</strong> panel.</span>
    </div>
    <div class="d-flex gap-3 justify-content-center mt-4">
      <a href="login.php" class="btn btn-primary px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
      </a>
      <a href="landing.php" class="btn btn-outline-secondary px-4">
        <i class="bi bi-house me-2"></i>Home
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- Registration Form -->
  <div class="form-body">
    <p class="text-muted small mb-4 text-center">
      Already have an account? <a href="login.php" class="fw-600 text-decoration-none">Sign in</a>
    </p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill mt-1"></i>
      <div>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 ps-3 mt-1">
          <?php foreach($errors as $e): ?>
          <li class="small"><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="registerForm" novalidate>

      <!-- Section 1: Personal Info -->
      <div class="mb-3 d-flex align-items-center">
        <span class="step-badge">1</span>
        <span class="fw-700 text-dark">Personal Information</span>
      </div>

      <div class="mb-3">
        <label class="form-label fw-600 small">Full Name <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" name="full_name" id="full_name"
                 class="form-control border-start-0 <?= isset($errors['full_name'])?'is-invalid':'' ?>"
                 placeholder="e.g. Maria Santos"
                 value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                 required minlength="3">
        </div>
        <?php if(isset($errors['full_name'])): ?>
        <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['full_name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-600 small">Email Address <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
          <input type="email" name="email" id="email"
                 class="form-control border-start-0 <?= isset($errors['email'])?'is-invalid':'' ?>"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                 required>
        </div>
        <?php if(isset($errors['email'])): ?>
        <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-4">
        <label class="form-label fw-600 small">Phone Number <span class="text-muted fw-400">(optional)</span></label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
          <input type="tel" name="phone"
                 class="form-control border-start-0 <?= isset($errors['phone'])?'is-invalid':'' ?>"
                 placeholder="+63 917 000 0000"
                 value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
        </div>
        <?php if(isset($errors['phone'])): ?>
        <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['phone']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Section 2: Role -->
      <div class="mb-3 d-flex align-items-center">
        <span class="step-badge">2</span>
        <span class="fw-700 text-dark">Select Your Role</span>
      </div>
      <p class="text-muted small mb-3">Choose the role that matches your responsibilities.</p>

      <?php if(isset($errors['role'])): ?>
      <div class="text-danger small mb-2"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['role']) ?></div>
      <?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="role-card d-block <?= ($formData['role']??'volunteer')==='volunteer'?'selected':'' ?>"
                 id="card-volunteer">
            <input type="radio" name="role" value="volunteer" class="me-2"
                   <?= ($formData['role']??'volunteer')==='volunteer'?'checked':'' ?>>
            <i class="bi bi-eye text-success fs-5 d-block mb-1"></i>
            <strong class="d-block small">Volunteer</strong>
            <span class="text-muted" style="font-size:.7rem">View donations &amp; reports only</span>
          </label>
        </div>
        <div class="col-6">
          <label class="role-card d-block <?= ($formData['role']??'')==='procurement'?'selected':'' ?>"
                 id="card-procurement">
            <input type="radio" name="role" value="procurement" class="me-2"
                   <?= ($formData['role']??'')==='procurement'?'checked':'' ?>>
            <i class="bi bi-pencil-square text-warning fs-5 d-block mb-1"></i>
            <strong class="d-block small">Procurement</strong>
            <span class="text-muted" style="font-size:.7rem">Add &amp; edit donations</span>
          </label>
        </div>
      </div>

      <div class="alert alert-warning d-flex gap-2 align-items-start py-2 mb-4" style="font-size:.8rem">
        <i class="bi bi-shield-lock-fill mt-1 text-warning"></i>
        <span><strong>Admin role</strong> is not available for self-registration and must be assigned by an existing administrator.</span>
      </div>

      <!-- Section 3: Password -->
      <div class="mb-3 d-flex align-items-center">
        <span class="step-badge">3</span>
        <span class="fw-700 text-dark">Create Password</span>
      </div>

      <div class="mb-3">
        <label class="form-label fw-600 small">Password <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" name="password" id="password"
                 class="form-control border-start-0 border-end-0 <?= isset($errors['password'])?'is-invalid':'' ?>"
                 placeholder="Min 8 chars, 1 uppercase, 1 number"
                 required minlength="8">
          <button class="btn btn-outline-secondary" type="button" id="togglePw">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
        <?php if(isset($errors['password'])): ?>
        <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>

        <!-- Password strength meter -->
        <div class="mt-2">
          <div class="pw-meter">
            <div class="pw-meter-bar" id="pwBar" style="width:0%;background:#e2e8f0"></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <small id="pwLabel" class="text-muted"></small>
            <small class="text-muted" style="font-size:.7rem">
              <span id="chk-len"  class="me-2 text-danger"><i class="bi bi-x-circle"></i> 8+ chars</span>
              <span id="chk-up"   class="me-2 text-danger"><i class="bi bi-x-circle"></i> Uppercase</span>
              <span id="chk-num"  class="text-danger"><i class="bi bi-x-circle"></i> Number</span>
            </small>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-600 small">Confirm Password <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
          <input type="password" name="confirm_password" id="confirm_password"
                 class="form-control border-start-0 <?= isset($errors['confirm'])?'is-invalid':'' ?>"
                 placeholder="Re-enter your password" required>
        </div>
        <?php if(isset($errors['confirm'])): ?>
        <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($errors['confirm']) ?></div>
        <?php endif; ?>
        <div id="matchMsg" class="small mt-1"></div>
      </div>

      <!-- Terms -->
      <div class="mb-4">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="terms" required>
          <label class="form-check-label small" for="terms">
            I agree to use this system responsibly and acknowledge that my account requires <strong>admin approval</strong> before activation.
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-register btn-primary w-100 text-white" id="submitBtn">
        <i class="bi bi-person-plus me-2"></i>Create Account
      </button>

      <div class="text-center mt-3">
        <a href="landing.php" class="text-muted small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i>Back to Home
        </a>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Role card highlight
document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        radio.closest('.role-card').classList.add('selected');
    });
});

// Toggle password visibility
document.getElementById('togglePw')?.addEventListener('click', () => {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') { pw.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { pw.type = 'password'; icon.className = 'bi bi-eye'; }
});

// Password strength meter
const pwInput = document.getElementById('password');
const pwBar   = document.getElementById('pwBar');
const pwLabel = document.getElementById('pwLabel');
const colors  = ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
const labels  = ['', 'Very Weak', 'Weak', 'Fair', 'Strong'];

pwInput?.addEventListener('input', () => {
    const v = pwInput.value;
    const checks = [v.length >= 8, /[A-Z]/.test(v), /[0-9]/.test(v), /[\W_]/.test(v)];
    const score  = checks.filter(Boolean).length;
    const pct    = score * 25;

    pwBar.style.width = pct + '%';
    pwBar.style.background = colors[score] || '#e2e8f0';
    pwLabel.textContent = labels[score] || '';
    pwLabel.style.color  = colors[score] || '';

    const setCheck = (id, ok) => {
        const el = document.getElementById(id);
        el.className = ok ? 'me-2 text-success' : 'me-2 text-danger';
        el.innerHTML = (ok ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-x-circle"></i>') + el.innerHTML.replace(/<[^>]+>/,'').trim();
    };
    document.getElementById('chk-len').innerHTML  = (v.length>=8?'<i class="bi bi-check-circle"></i>':'<i class="bi bi-x-circle"></i>') + ' 8+ chars';
    document.getElementById('chk-up').innerHTML   = (/[A-Z]/.test(v)?'<i class="bi bi-check-circle"></i>':'<i class="bi bi-x-circle"></i>') + ' Uppercase';
    document.getElementById('chk-num').innerHTML  = (/[0-9]/.test(v)?'<i class="bi bi-check-circle"></i>':'<i class="bi bi-x-circle"></i>') + ' Number';
    document.getElementById('chk-len').className  = v.length>=8   ? 'me-2 text-success' : 'me-2 text-danger';
    document.getElementById('chk-up').className   = /[A-Z]/.test(v)? 'me-2 text-success' : 'me-2 text-danger';
    document.getElementById('chk-num').className  = /[0-9]/.test(v)? 'text-success' : 'text-danger';
});

// Confirm match
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const msg = document.getElementById('matchMsg');
    if (this.value === document.getElementById('password').value) {
        msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</span>';
        this.classList.remove('is-invalid'); this.classList.add('is-valid');
    } else {
        msg.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</span>';
        this.classList.remove('is-valid'); this.classList.add('is-invalid');
    }
});

// Terms check gate
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    if (!document.getElementById('terms').checked) {
        e.preventDefault();
        document.getElementById('terms').nextElementSibling.style.color = 'red';
        document.getElementById('terms').focus();
    }
});
</script>
</body>
</html>