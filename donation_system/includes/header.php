<?php
if (!defined('BASE_URL')) define('BASE_URL', '');
$user = currentUser();
$role = $user['role'];
$picSrc = $user['picture'] ? BASE_URL.'/uploads/profiles/'.htmlspecialchars($user['picture'])
        : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=2563eb&color=fff&size=80';
?>
 <?php  if ($role === 'admin'):
    // Show badge for pending users
    $pendingUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
    if ($pendingUsers > 0): ?>
    <span class="badge bg-danger rounded-pill ms-auto"><?= $pendingUsers ?></span>
    <?php endif; ?>
</a></li>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?? 'Donation System' ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<li><a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='users.php'?'active':'') ?>"
       href="<?= BASE_URL ?>/admin/users.php">
    <i class="bi bi-people-fill"></i> Users
<style>
:root{--sidebar-width:260px;--brand:#2563eb;--brand-dark:#1d4ed8;}
body{background:#f1f5f9;font-family:'Segoe UI',sans-serif;}
.sidebar{width:var(--sidebar-width);min-height:100vh;background:linear-gradient(160deg,#1e3a5f 0%,#2563eb 100%);position:fixed;top:0;left:0;z-index:1000;transition:.3s;}
.sidebar .brand{padding:1.5rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.15);}
.sidebar .brand h5{color:#fff;font-weight:700;margin:0;font-size:1.1rem;}
.sidebar .brand small{color:rgba(255,255,255,.6);font-size:.75rem;}
.sidebar .nav-link{color:rgba(255,255,255,.75);padding:.65rem 1.25rem;border-radius:.5rem;margin:.1rem .75rem;display:flex;align-items:center;gap:.6rem;font-size:.9rem;transition:.2s;}
.sidebar .nav-link:hover,.sidebar .nav-link.active{background:rgba(255,255,255,.18);color:#fff;}
.sidebar .nav-link i{font-size:1.1rem;width:20px;text-align:center;}
.sidebar-section{padding:.5rem 1.25rem .25rem;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.4);font-weight:600;}
.main-content{margin-left:var(--sidebar-width);padding:1.5rem;}
.topbar{background:#fff;border-radius:.75rem;padding:.75rem 1.25rem;display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.07);}
.topbar .page-title{font-weight:700;font-size:1.2rem;color:#1e293b;margin:0;}
.user-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--brand);}
.card{border:none;border-radius:.9rem;box-shadow:0 1px 6px rgba(0,0,0,.07);}
.card-header{background:#fff;border-bottom:1px solid #e9ecef;border-radius:.9rem .9rem 0 0 !important;padding:1rem 1.25rem;font-weight:600;}
.stat-card{border-radius:.9rem;padding:1.25rem;color:#fff;position:relative;overflow:hidden;}
.stat-card .stat-icon{position:absolute;right:1rem;top:1rem;font-size:2.5rem;opacity:.25;}
.stat-card h3{font-size:2rem;font-weight:700;margin:0;}
.stat-card p{margin:0;font-size:.85rem;opacity:.85;}
.table th{font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;background:#f8fafc;color:#64748b;}
.badge-status-Pending{background:#fef3c7;color:#92400e;}
.badge-status-Received{background:#d1fae5;color:#065f46;}
.badge-status-Distributed{background:#dbeafe;color:#1e40af;}
@media(max-width:768px){.sidebar{transform:translateX(-100%);}.sidebar.show{transform:translateX(0);}.main-content{margin-left:0;}}
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="brand">
    <h5><i class="bi bi-heart-fill text-danger me-2"></i>Relief</h5>
    <small>Donation Management</small>
  </div>
  <div class="mt-3 px-3 mb-2">
    <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:rgba(255,255,255,.1)">
      <img src="<?= $picSrc ?>" class="user-avatar" alt="">
      <div>
        <div class="text-white fw-600 small"><?= htmlspecialchars($user['name']) ?></div>
        <span class="badge <?= roleBadgeClass($role) ?> py-0" style="font-size:.65rem"><?= ucfirst($role) ?></span>
      </div>
    </div>
  </div>

  <div class="sidebar-section mt-2">Navigation</div>
  <ul class="nav flex-column px-0">
    <li><a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'') ?>"
           href="<?= BASE_URL ?>/<?= $role ?>/dashboard.php">
        <i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='donations.php'?'active':'') ?>"
           href="<?= BASE_URL ?>/donations.php">
        <i class="bi bi-gift"></i> Donations</a></li>
    <li><a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='donors.php'?'active':'') ?>"
           href="<?= BASE_URL ?>/donors.php">
        <i class="bi bi-people"></i> Donors</a></li>

    <div class="sidebar-section mt-1">Account</div>
    <li><a class="nav-link <?= (basename($_SERVER['PHP_SELF'])=='edit-profile.php'?'active':'') ?>"
           href="<?= BASE_URL ?>/pages/edit-profile.php">
        <i class="bi bi-person-gear"></i> Edit Profile</a></li>
    <li><a class="nav-link" href="<?= BASE_URL ?>/logout.php"
           onclick="return confirm('Log out?')">
        <i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>

<!-- Main -->
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
        <i class="bi bi-list"></i>
      </button>
      <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
      <a href="<?= BASE_URL ?>/pages/edit-profile.php">
        <img src="<?= $picSrc ?>" class="user-avatar" title="Edit Profile" alt="">
      </a>
    </div>
  </div>
  <div id="flash-container"><?php renderFlash(); ?></div>