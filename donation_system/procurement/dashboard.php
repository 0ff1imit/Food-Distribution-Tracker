<?php
session_start();
define('BASE_URL','..');
require_once BASE_URL.'/includes/db.php';
require_once BASE_URL.'/includes/auth.php';
require_once BASE_URL.'/includes/flash.php';
requireRole(['procurement']);

$pageTitle = 'Procurement Dashboard';
$totalDonations   = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$totalAmount      = $pdo->query("SELECT SUM(amount) FROM donations WHERE status='Received'")->fetchColumn() ?: 0;
$pendingCount     = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='Pending'")->fetchColumn();
$recent = $pdo->query("SELECT d.*,dn.name AS donor_name FROM donations d
    JOIN donors dn ON dn.id=d.donor_id ORDER BY d.created_at DESC LIMIT 6")->fetchAll();

include BASE_URL.'/includes/header.php';
?>
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card bg-primary"><i class="bi bi-cash-stack stat-icon"></i>
      <h3>₱<?= number_format($totalAmount,0) ?></h3><p>Total Received</p></div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-success"><i class="bi bi-gift stat-icon"></i>
      <h3><?= $totalDonations ?></h3><p>Total Donations</p></div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-warning"><i class="bi bi-hourglass stat-icon"></i>
      <h3><?= $pendingCount ?></h3><p>Pending</p></div>
  </div>
</div>
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Donations</span>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/donations.php?action=add" class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg me-1"></i>Add Donation
      </a>
      <a href="<?= BASE_URL ?>/donations.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th class="px-3">Donor</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($recent as $r): ?>
      <tr>
        <td class="px-3 py-2 fw-600"><?= htmlspecialchars($r['donor_name']) ?></td>
        <td><?= $r['amount']>0?'₱'.number_format($r['amount'],2):'—' ?></td>
        <td><?= date('M d, Y',strtotime($r['donation_date'])) ?></td>
        <td><span class="badge rounded-pill badge-status-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
        <td><a href="<?= BASE_URL ?>/donations.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-warning btn-sm py-0 px-2">Edit</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include BASE_URL.'/includes/footer.php'; ?>