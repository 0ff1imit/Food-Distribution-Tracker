<?php
session_start();
define('BASE_URL', '/donation_system');
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/flash.php';
requireLogin();

$pageTitle = 'Donations';
$action    = $_GET['action'] ?? 'list';
$id        = (int)($_GET['id'] ?? 0);
$role      = $_SESSION['user_role'];

// ---- PROCESS POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEdit()) { setFlash('danger','No permission.'); header('Location: donations.php'); exit; }

    $donor_id      = (int)$_POST['donor_id'];
    $amount        = (float)$_POST['amount'];
    $item          = trim($_POST['item'] ?? '');
    $quantity      = (int)$_POST['quantity'];
    $donation_date = $_POST['donation_date'];
    $status        = $_POST['status'];
    $notes         = trim($_POST['notes'] ?? '');
    $postAction    = $_POST['form_action'] ?? 'add';
    $postId        = (int)($_POST['id'] ?? 0);

    if (!$donor_id || !$donation_date) {
        setFlash('warning','Donor and date are required.');
        header('Location: donations.php?action='.($postId?'edit':'add').($postId?"&id=$postId":''));
        exit;
    }

    if ($postAction === 'edit' && $postId) {
        $pdo->prepare("UPDATE donations SET donor_id=?,amount=?,item=?,quantity=?,donation_date=?,status=?,notes=? WHERE id=?")
            ->execute([$donor_id,$amount,$item,$quantity,$donation_date,$status,$notes,$postId]);
        setFlash('success','Donation updated successfully.');
    } else {
        $pdo->prepare("INSERT INTO donations (donor_id,amount,item,quantity,donation_date,status,notes,created_by) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$donor_id,$amount,$item,$quantity,$donation_date,$status,$notes,$_SESSION['user_id']]);
        setFlash('success','Donation added successfully.');
    }
    header('Location: donations.php'); exit;
}

// ---- DELETE ----
if ($action === 'delete' && $id) {
    if (!canDelete()) { setFlash('danger','Only admins can delete.'); header('Location: donations.php'); exit; }
    $pdo->prepare("DELETE FROM donations WHERE id=?")->execute([$id]);
    setFlash('success','Donation deleted.');
    header('Location: donations.php'); exit;
}

// ---- FETCH DATA ----
$donors = $pdo->query("SELECT id,name FROM donors ORDER BY name")->fetchAll();
$donation = null;
if (in_array($action,['edit']) && $id) {
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE id=?");
    $stmt->execute([$id]);
    $donation = $stmt->fetch();
}

// ---- LIST with search/filter ----
$search   = trim($_GET['search'] ?? '');
$statusF  = $_GET['status'] ?? '';
$where    = "WHERE 1=1";
$params   = [];
if ($search) { $where .= " AND (dn.name LIKE ? OR d.item LIKE ? OR d.notes LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($statusF) { $where .= " AND d.status=?"; $params[] = $statusF; }

$donations = $pdo->prepare("SELECT d.*,dn.name AS donor_name FROM donations d
    JOIN donors dn ON dn.id=d.donor_id $where ORDER BY d.donation_date DESC");
$donations->execute($params);
$donations = $donations->fetchAll();

$totalAmount = $pdo->query("SELECT SUM(amount) FROM donations WHERE status='Received'")->fetchColumn() ?: 0;

include __DIR__.'/includes/header.php';
?>

<?php if (in_array($action,['add','edit'])): ?>
<!-- ============ FORM ============ -->
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-<?= $action==='edit'?'pencil':'plus-circle' ?> me-2 text-primary"></i>
        <?= $action==='edit' ? 'Edit Donation' : 'Add New Donation' ?>
      </div>
      <div class="card-body p-4">
        <form method="POST">
          <input type="hidden" name="form_action" value="<?= $action ?>">
          <?php if($donation): ?><input type="hidden" name="id" value="<?= $donation['id'] ?>"><?php endif; ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-600">Donor <span class="text-danger">*</span></label>
              <select name="donor_id" class="form-select" required>
                <option value="">-- Select Donor --</option>
                <?php foreach($donors as $dn): ?>
                <option value="<?= $dn['id'] ?>" <?= ($donation['donor_id']??'')==$dn['id']?'selected':'' ?>>
                  <?= htmlspecialchars($dn['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Amount (₱)</label>
              <input type="number" name="amount" class="form-control" step="0.01" min="0"
                     value="<?= htmlspecialchars($donation['amount']??'0') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Item / Type</label>
              <input type="text" name="item" class="form-control" placeholder="e.g. Canned Goods"
                     value="<?= htmlspecialchars($donation['item']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Quantity</label>
              <input type="number" name="quantity" class="form-control" min="0"
                     value="<?= htmlspecialchars($donation['quantity']??'0') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Donation Date <span class="text-danger">*</span></label>
              <input type="date" name="donation_date" class="form-control" required
                     value="<?= htmlspecialchars($donation['donation_date']??date('Y-m-d')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Status</label>
              <select name="status" class="form-select">
                <?php foreach(['Pending','Received','Distributed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($donation['status']??'Pending')===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Notes</label>
              <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($donation['notes']??'') ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i><?= $action==='edit'?'Update':'Save Donation' ?>
              </button>
              <a href="donations.php" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ============ LIST ============ -->

<!-- Summary -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-primary bg-opacity-10"><i class="bi bi-gift text-primary fs-4"></i></div>
      <div><div class="fw-700 fs-5"><?= count($donations) ?></div><div class="text-muted small">Total Donations</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-success bg-opacity-10"><i class="bi bi-cash-stack text-success fs-4"></i></div>
      <div><div class="fw-700 fs-5">₱<?= number_format($totalAmount,2) ?></div><div class="text-muted small">Amount Received</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-danger bg-opacity-10"><i class="bi bi-file-pdf text-danger fs-4"></i></div>
      <div>
        <a href="donations_pdf.php" class="btn btn-danger btn-sm"><i class="bi bi-download me-1"></i>PDF Report</a>
        <div class="text-muted small mt-1">Download all</div>
      </div>
    </div>
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <span class="fw-600"><i class="bi bi-table me-2 text-primary"></i>Donations List</span>
    <div class="d-flex gap-2 flex-wrap">
      <form class="d-flex gap-2" method="GET">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
        <select name="status" class="form-select form-select-sm" style="width:130px">
          <option value="">All Status</option>
          <?php foreach(['Pending','Received','Distributed'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
        <?php if($search||$statusF): ?>
        <a href="donations.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
        <?php endif; ?>
      </form>
      <?php if(canEdit()): ?>
      <a href="donations.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Add</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr>
        <th class="px-3">#</th>
        <th>Donor</th>
        <th>Amount</th>
        <th>Item</th>
        <th>Qty</th>
        <th>Date</th>
        <th>Status</th>
        <th>Notes</th>
        <th class="text-center">Actions</th>
      </tr></thead>
      <tbody>
      <?php if(!$donations): ?>
      <tr><td colspan="9" class="text-center py-5 text-muted">No donations found.</td></tr>
      <?php endif; ?>
      <?php foreach($donations as $i=>$d): ?>
      <tr>
        <td class="px-3 text-muted small"><?= $i+1 ?></td>
        <td class="fw-600"><?= htmlspecialchars($d['donor_name']) ?></td>
        <td class="<?= $d['amount']>0?'text-success fw-700':'' ?>"><?= $d['amount']>0?'₱'.number_format($d['amount'],2):'—' ?></td>
        <td><?= htmlspecialchars($d['item']??'—') ?></td>
        <td><?= $d['quantity']?:0 ?></td>
        <td><?= date('M d, Y',strtotime($d['donation_date'])) ?></td>
        <td><span class="badge rounded-pill badge-status-<?= $d['status'] ?>"><?= $d['status'] ?></span></td>
        <td class="text-muted small" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($d['notes']??'') ?>">
          <?= htmlspecialchars(mb_strimwidth($d['notes']??'',0,40,'…')) ?></td>
        <td class="text-center">
          <div class="d-flex gap-1 justify-content-center">
            <?php if(canEdit()): ?>
            <a href="donations.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-sm btn-warning py-1 px-2" title="Edit">
              <i class="bi bi-pencil"></i>
            </a>
            <?php endif; ?>
            <?php if(canDelete()): ?>
            <a href="donations.php?action=delete&id=<?= $d['id'] ?>"
               class="btn btn-sm btn-danger py-1 px-2" title="Delete"
               onclick="return confirm('Delete this donation? This cannot be undone.')">
              <i class="bi bi-trash"></i>
            </a>
            <?php endif; ?>
            <?php if(!canEdit()): ?>
            <span class="text-muted small">View only</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__.'/includes/footer.php'; ?>