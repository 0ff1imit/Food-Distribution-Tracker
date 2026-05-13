<?php
session_start();
define('BASE_URL','..');
require_once BASE_URL.'/includes/db.php';
require_once BASE_URL.'/includes/auth.php';
require_once BASE_URL.'/includes/flash.php';
requireRole(['admin']);

$pageTitle = 'Admin Dashboard';

// Stats
$totalDonations   = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$totalAmount      = $pdo->query("SELECT SUM(amount) FROM donations WHERE status='Received'")->fetchColumn() ?: 0;
$totalDonors      = $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn();
$pendingCount     = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='Pending'")->fetchColumn();
$distributedCount = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='Distributed'")->fetchColumn();
$userCount        = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$recent = $pdo->query("SELECT d.*,dn.name AS donor_name FROM donations d
    JOIN donors dn ON dn.id=d.donor_id ORDER BY d.created_at DESC LIMIT 8")->fetchAll();

include BASE_URL.'/includes/header.php';
?>

<div class="row g-4 mb-4">
  <?php foreach([
    ['bg-primary','cash-stack','Total Raised','₱'.number_format($totalAmount,2),'All time received'],
    ['bg-success','gift','Total Donations',$totalDonations,'All entries'],
    ['bg-info','people-fill','Donors',$totalDonors,'Registered donors'],
    ['bg-warning','hourglass-split','Pending',$pendingCount,'Awaiting receipt'],
    ['bg-purple','send-check','Distributed',$distributedCount,'Delivered'],
    ['bg-secondary','person-badge','System Users',$userCount,'All roles'],
  ] as [$bg,$icon,$label,$val,$sub]):?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card <?= $bg ?>">
      <i class="bi bi-<?= $icon ?> stat-icon"></i>
      <h3><?= $val ?></h3>
      <p><?= $label ?></p>
      <small style="opacity:.7;font-size:.7rem"><?= $sub ?></small>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Donations</span>
        <a href="<?= BASE_URL ?>/donations.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th class="px-3">Donor</th><th>Item</th><th>Amount</th><th>Date</th><th>Status</th>
          </tr></thead>
          <tbody>
          <?php foreach($recent as $r): ?>
          <tr>
            <td class="px-3 py-2"><?= htmlspecialchars($r['donor_name']) ?></td>
            <td><?= htmlspecialchars($r['item'] ?: '—') ?></td>
            <td class="<?= $r['amount']>0?'text-success fw-600':'' ?>">
              <?= $r['amount']>0 ? '₱'.number_format($r['amount'],2) : '—' ?></td>
            <td><?= date('M d, Y',strtotime($r['donation_date'])) ?></td>
            <td><span class="badge rounded-pill badge-status-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2 text-warning"></i>Status Breakdown</div>
      <div class="card-body">
        <?php
        $statuses = $pdo->query("SELECT status, COUNT(*) as cnt FROM donations GROUP BY status")->fetchAll();
        $total = array_sum(array_column($statuses,'cnt')) ?: 1;
        $colors=['Pending'=>'#fbbf24','Received'=>'#22c55e','Distributed'=>'#3b82f6'];
        foreach($statuses as $s):
          $pct = round($s['cnt']/$total*100);
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small fw-600 mb-1">
            <span><?= $s['status'] ?></span>
            <span><?= $s['cnt'] ?> (<?= $pct ?>%)</span>
          </div>
          <div class="progress" style="height:8px;border-radius:4px">
            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $colors[$s['status']]??'#6b7280' ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <hr>
        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/donations_pdf.php" class="btn btn-danger btn-sm w-100">
            <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF Report
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include BASE_URL.'/includes/footer.php'; ?>