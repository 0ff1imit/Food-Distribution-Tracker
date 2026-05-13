<?php
session_start();
define('BASE_URL','..');
require_once BASE_URL.'/includes/db.php';
require_once BASE_URL.'/includes/auth.php';
require_once BASE_URL.'/includes/flash.php';
requireRole(['volunteer']);

$pageTitle = 'Volunteer Dashboard';
$totalDonations   = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$totalAmount      = $pdo->query("SELECT SUM(amount) FROM donations WHERE status='Received'")->fetchColumn() ?: 0;
$receivedCount    = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='Received'")->fetchColumn();
$distributedCount = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='Distributed'")->fetchColumn();
$recent = $pdo->query("SELECT d.*,dn.name AS donor_name FROM donations d
    JOIN donors dn ON dn.id=d.donor_id ORDER BY d.donation_date DESC LIMIT 8")->fetchAll();

include BASE_URL.'/includes/header.php';
?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-info-circle-fill fs-5"></i>
  <span>You have <strong>View Only</strong> access. Contact an admin for changes.</span>
</div>
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3"><div class="stat-card bg-primary"><i class="bi bi-gift stat-icon"></i><h3><?= $totalDonations ?></h3><p>Donations</p></div></div>
  <div class="col-6 col-md-3"><div class="stat-card bg-success"><i class="bi bi-cash-stack stat-icon"></i><h3>₱<?= number_format($totalAmount,0) ?></h3><p>Raised</p></div></div>
  <div class="col-6 col-md-3"><div class="stat-card bg-info"><i class="bi bi-check-circle stat-icon"></i><h3><?= $receivedCount ?></h3><p>Received</p></div></div>
  <div class="col-6 col-md-3"><div class="stat-card bg-warning"><i class="bi bi-send-check stat-icon"></i><h3><?= $distributedCount ?></h3><p>Distributed</p></div></div>
</div>
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <span><i class="bi bi-list-ul me-2 text-primary"></i>All Donations</span>
    <a href="<?= BASE_URL ?>/donations.php" class="btn btn-sm btn-outline-primary">Full List</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th class="px-3">Donor</th><th>Item</th><th>Amount</th><th>Qty</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($recent as $r): ?>
      <tr>
        <td class="px-3 py-2 fw-600"><?= htmlspecialchars($r['donor_name']) ?></td>
        <td><?= htmlspecialchars($r['item']??'—') ?></td>
        <td><?= $r['amount']>0?'₱'.number_format($r['amount'],2):'—' ?></td>
        <td><?= $r['quantity']?:0 ?></td>
        <td><?= date('M d, Y',strtotime($r['donation_date'])) ?></td>
        <td><span class="badge rounded-pill badge-status-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include BASE_URL.'/includes/footer.php'; ?>