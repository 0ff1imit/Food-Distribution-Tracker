<?php
session_start();
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/flash.php';
define('BASE_URL', '/donation_system');

if (isset($_SESSION['user_id'])) {
    header('Location: '.$_SESSION['user_role'].'/dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['full_name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['user_picture'] = $user['profile_picture'];
            $_SESSION['user_status']  = $user['status'];   
            setFlash('success', 'Welcome back, '.$user['full_name'].'!');
            header('Location: '. BASE_URL . '/' .$user['role'].'/dashboard.php'); exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }

//Block pending/inactive before redirecting:
    if ($user['status'] === 'pending') {
    $error = 'Your account is pending admin approval. Please wait.';
    } elseif ($user['status'] === 'inactive') {
    $error = 'Your account has been deactivated. Contact an administrator.';
    } else {
    setFlash('success', 'Welcome back, '.$user['full_name'].'!');
    header('Location: '. BASE_URL . '/' .$user['role'].'/dashboard.php'); exit;
    }
}
$reqError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – DonateMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-card{background:#fff;border-radius:1.25rem;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;max-width:420px;width:100%;}
.login-header{background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:2rem;text-align:center;color:#fff;}
.form-control{border-radius:.6rem;padding:.7rem 1rem;}
.form-control:focus{border-color:#2563eb;box-shadow:0 0 0 .2rem rgba(37,99,235,.2);}
.btn-login{background:linear-gradient(135deg,#2563eb,#7c3aed);border:none;border-radius:.6rem;padding:.75rem;font-weight:700;}
.btn-login:hover{opacity:.9;}
.input-group-text{background:#f8fafc;border-right:none;}
.form-control{border-left:none;}
</style>
</head>
<body>
<div class="login-card">
  <div class="login-header">
    <i class="bi bi-heart-fill text-danger fs-1 mb-2 d-block"></i>
    <h4 class="fw-700 mb-0">DonateMS</h4>
    <small class="opacity-75">Donation Management System</small>
  </div>
  <div class="p-4">
    <h5 class="fw-700 mb-1">Welcome back 👋</h5>
    <p class="text-muted small mb-4">Sign in to continue</p>

    <?php if($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
      <i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if($reqError==='unauthorized'): ?>
    <div class="alert alert-warning py-2">Access denied for your role.</div>
    <?php endif; ?>
    <?php if($reqError==='not_approved'): ?>
    <div class="alert alert-warning d-flex gap-2 py-2">
    <i class="bi bi-hourglass-split"></i>
    Your account is awaiting admin approval.
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label small fw-600">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email']??'') ?>" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-600">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" name="password" id="pw" class="form-control" placeholder="••••••••" required>
          <button class="btn btn-outline-secondary" type="button" onclick="let e=document.getElementById('pw');e.type=e.type==='password'?'text':'password'">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-login btn-primary w-100 text-white">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <hr class="my-3">
    <div class="text-center text-muted small">
      <strong>Demo Accounts</strong> (password: <code>Password123!</code>)<br>
      admin@donation.org | procurement@donation.org | volunteer@donation.org
    </div>
    
<div class="text-center mt-3">
    <a href="register.php" class="btn btn-outline-primary btn-sm w-100">
        <i class="bi bi-person-plus me-2"></i>Create New Account
    </a>
</div>
    <div class="text-center mt-2">
      <a href="landing.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to Home</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>