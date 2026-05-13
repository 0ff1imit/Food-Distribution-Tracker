<?php
session_start();
require_once __DIR__.'/includes/db.php';
define('BASE_URL','');

$recentDonations = $pdo->query("
    SELECT d.*, dn.name AS donor_name FROM donations d
    JOIN donors dn ON dn.id=d.donor_id
    WHERE d.status='Received'
    ORDER BY d.donation_date DESC LIMIT 6")->fetchAll();

$topDonors = $pdo->query("
    SELECT dn.name, SUM(d.amount) AS total, COUNT(*) AS count
    FROM donations d JOIN donors dn ON dn.id=d.donor_id
    GROUP BY dn.id ORDER BY total DESC LIMIT 5")->fetchAll();

$stats = $pdo->query("
    SELECT COUNT(*) AS total_donations,
           SUM(amount) AS total_amount,
           COUNT(DISTINCT donor_id) AS total_donors
    FROM donations WHERE status='Received'")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>DonateMS – Donation Management System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{font-family:'Segoe UI',sans-serif;}
.hero{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%);min-height:92vh;display:flex;align-items:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
.hero-title{font-size:clamp(2.5rem,6vw,4rem);font-weight:800;color:#fff;line-height:1.1;}
.hero-sub{font-size:1.15rem;color:rgba(255,255,255,.8);max-width:520px;}
.stat-pill{background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2);border-radius:1rem;padding:.75rem 1.5rem;color:#fff;text-align:center;}
.stat-pill h3{font-size:1.8rem;font-weight:700;margin:0;}
.stat-pill p{margin:0;font-size:.8rem;opacity:.8;}
.section-title{font-size:1.75rem;font-weight:700;color:#1e293b;}
.donor-card{border:none;border-radius:1rem;box-shadow:0 2px 12px rgba(0,0,0,.08);transition:.2s;overflow:hidden;}
.donor-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(37,99,235,.15);}
.rank-badge{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;}
.donation-row:hover{background:#f8fafc;}
.status-badge{padding:.25rem .75rem;border-radius:2rem;font-size:.75rem;font-weight:600;}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg" style="background:rgba(30,58,95,.95);backdrop-filter:blur(10px);">
  <div class="container">
    <a class="navbar-brand text-white fw-700 fs-5" href="landing.php">
      <i class="bi bi-heart-fill text-danger me-2"></i>DonateMS
    </a>
    <div class="ms-auto d-flex gap-2">
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="<?= $_SESSION['user_role'] ?>/dashboard.php" class="btn btn-light btn-sm">
          <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
<?php else: ?>
    <a href="register.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-person-plus me-1"></i>Sign Up
    </a>
    <a href="login.php" class="btn btn-warning btn-sm fw-600">Login</a>
<?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="container py-5" style="position:relative;z-index:1;">
    <div class="row align-items-center gy-5">
      <div class="col-lg-6">
        <div class="mb-3">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
            <i class="bi bi-stars me-1"></i>Trusted Donation Platform
          </span>
        </div>
        <h1 class="hero-title mb-4">Manage Donations<br><span style="color:#fbbf24">With Purpose</span></h1>
        <p class="hero-sub mb-4">Track every donation, empower your team, and make a lasting impact. Transparent, secure, and easy to use.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="login.php" class="btn btn-warning btn-lg fw-700 px-4">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login Now
          </a>
          <a href="#recent" class="btn btn-outline-light btn-lg px-4">
            <i class="bi bi-eye me-2"></i>View Donations
          </a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-4">
            <div class="stat-pill">
              <h3>₱<?= number_format($stats['total_amount']/1000,0) ?>K</h3>
              <p>Raised</p>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-pill">
              <h3><?= $stats['total_donations'] ?></h3>
              <p>Donations</p>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-pill">
              <h3><?= $stats['total_donors'] ?></h3>
              <p>Donors</p>
            </div>
          </div>
        </div>
        <!-- Floating card -->
        <div class="mt-4 p-4 rounded-3" style="background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2);">
          <div class="d-flex align-items-center gap-3 text-white mb-3">
            <i class="bi bi-shield-check fs-3 text-warning"></i>
            <div><strong>Secure & Transparent</strong><br><small style="opacity:.7">Every peso accounted for</small></div>
          </div>
          <div class="d-flex align-items-center gap-3 text-white mb-3">
            <i class="bi bi-graph-up-arrow fs-3 text-success"></i>
            <div><strong>Real-time Reports</strong><br><small style="opacity:.7">Download PDF anytime</small></div>
          </div>
          <div class="d-flex align-items-center gap-3 text-white">
            <i class="bi bi-people-fill fs-3 text-info"></i>
            <div><strong>Role-based Access</strong><br><small style="opacity:.7">Admin, Procurement, Volunteer</small></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Bar -->
<div style="background:#1e293b;padding:2rem 0;">
  <div class="container">
    <div class="row text-center g-4">
      <?php foreach([
        ['bi-gift-fill','text-warning','Total Donations', $stats['total_donations']],
        ['bi-people-fill','text-info','Total Donors', $stats['total_donors']],
        ['bi-cash-stack','text-success','Amount Received','₱'.number_format($stats['total_amount'],2)],
        ['bi-heart-fill','text-danger','Making Impact','Every Day']
      ] as [$icon,$col,$label,$val]): ?>
      <div class="col-6 col-md-3">
        <i class="bi <?= $icon ?> <?= $col ?> fs-2 mb-2 d-block"></i>
        <div class="text-white fw-700 fs-5"><?= $val ?></div>
        <div class="text-secondary small"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent Donations -->
<section id="recent" class="py-5" style="background:#f8fafc;">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Recent Donations</h2>
      <p class="text-muted">Latest received contributions from our generous donors</p>
    </div>
    <div class="card shadow-sm border-0 rounded-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr style="background:#f1f5f9;">
              <th class="px-4 py-3">Donor</th>
              <th>Item / Amount</th>
              <th>Quantity</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($recentDonations as $d): ?>
            <tr class="donation-row">
              <td class="px-4 py-3 fw-600"><?= htmlspecialchars($d['donor_name']) ?></td>
              <td><?php if($d['amount']>0): ?><span class="text-success fw-700">₱<?= number_format($d['amount'],2) ?></span>
                <?php else: ?><?= htmlspecialchars($d['item']) ?><?php endif; ?></td>
              <td><?= $d['quantity'] ?: '—' ?></td>
              <td><?= date('M d, Y', strtotime($d['donation_date'])) ?></td>
              <td><span class="status-badge badge-status-<?= $d['status'] ?>"><?= $d['status'] ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Top Donors -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Our Top Donors</h2>
      <p class="text-muted">Recognizing those who give the most</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php $rankColors=['#f59e0b','#94a3b8','#b45309','#2563eb','#7c3aed'];
            foreach($topDonors as $i=>$d): ?>
      <div class="col-md-4 col-lg-2-4">
        <div class="donor-card card text-center p-4">
          <div class="rank-badge mx-auto mb-3 text-white"
               style="background:<?= $rankColors[$i] ?>;">#<?= $i+1 ?></div>
          <div class="fw-700 fs-6 mb-1"><?= htmlspecialchars($d['name']) ?></div>
          <div class="text-success fw-700">₱<?= number_format($d['total'],2) ?></div>
          <div class="text-muted small"><?= $d['count'] ?> donation<?= $d['count']>1?'s':'' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer style="background:#1e293b;color:rgba(255,255,255,.6);padding:2rem 0;text-align:center;">
  <div class="container">
    <strong class="text-white"><i class="bi bi-heart-fill text-danger me-2"></i>DonateMS</strong>
    <p class="mt-2 mb-0 small">© <?= date('Y') ?> Donation Management System. Built with ❤️ for transparency.</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>