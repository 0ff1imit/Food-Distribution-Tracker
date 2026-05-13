    <?php
session_start();
define('BASE_URL', '/donation_system');
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/flash.php';
requireLogin();

$pageTitle = 'Donors';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEdit()) { setFlash('danger','No permission.'); header('Location: donors.php'); exit; }
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addr  = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $pa    = $_POST['form_action'] ?? 'add';
    $pid   = (int)($_POST['id'] ?? 0);
    if (!$name) { setFlash('warning','Name is required.'); header('Location: donors.php?action='.($pid?'edit':'add').($pid?"&id=$pid":'')); exit; }
    if ($pa==='edit' && $pid) {
        $pdo->prepare("UPDATE donors SET name=?,email=?,phone=?,address=?,notes=? WHERE id=?")
            ->execute([$name,$email,$phone,$addr,$notes,$pid]);
        setFlash('success','Donor updated.');
    } else {
        $pdo->prepare("INSERT INTO donors (name,email,phone,address,notes,created_by) VALUES(?,?,?,?,?,?)")
            ->execute([$name,$email,$phone,$addr,$notes,$_SESSION['user_id']]);
        setFlash('success','Donor added.');
    }
    header('Location: donors.php'); exit;
}

if ($action==='delete' && $id) {
    if (!canDelete()) { setFlash('danger','Only admins can delete.'); header('Location: donors.php'); exit; }
    $pdo->prepare("DELETE FROM donors WHERE id=?")->execute([$id]);
    setFlash('success','Donor deleted.');
    header('Location: donors.php'); exit;
}

$donor = null;
if ($action==='edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM donors WHERE id=?"); $s->execute([$id]); $donor=$s->fetch();
}

$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%"] : [];
$stmt = $pdo->prepare("SELECT d.*, (SELECT COUNT(*) FROM donations WHERE donor_id=d.id) AS don_count FROM donors d $where ORDER BY d.name");
$stmt->execute($params);
$donors = $stmt->fetchAll();

include __DIR__.'/includes/header.php';
?>

<?php if(in_array($action,['add','edit'])): ?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-plus me-2 text-primary"></i><?= $action==='edit'?'Edit Donor':'Add New Donor' ?></div>
      <div class="card-body p-4">
        <form method="POST">
          <input type="hidden" name="form_action" value="<?= $action ?>">
          <?php if($donor): ?><input type="hidden" name="id" value="<?= $donor['id'] ?>"><?php endif; ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-600">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required
                     value="<?= htmlspecialchars($donor['name']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Email</label>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($donor['email']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Phone</label>
              <input type="text" name="phone" class="form-control"
                     value="<?= htmlspecialchars($donor['phone']??'') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Address</label>
              <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($donor['address']??'') ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Notes</label>
              <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($donor['notes']??'') ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Save</button>
              <a href="donors.php" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <span class="fw-600"><i class="bi bi-people me-2 text-primary"></i>Donors (<?= count($donors) ?>)</span>
    <div class="d-flex gap-2 flex-wrap">
      <form class="d-flex gap-2" method="GET">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
        <?php if($search): ?><a href="donors.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
      </form>
      <?php if(canEdit()): ?>
      <a href="donors.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Add Donor</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr>
        <th class="px-3">#</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Donations</th><th class="text-center">Actions</th>
      </tr></thead>
      <tbody>
      <?php if(!$donors): ?>
      <tr><td colspan="7" class="text-center py-5 text-muted">No donors found.</td></tr>
      <?php endif; ?>
      <?php foreach($donors as $i=>$d): ?>
      <tr>
        <td class="px-3 text-muted small"><?= $i+1 ?></td>
        <td class="fw-600"><?= htmlspecialchars($d['name']) ?></td>
        <td class="text-muted"><?= htmlspecialchars($d['email']??'—') ?></td>
        <td><?= htmlspecialchars($d['phone']??'—') ?></td>
        <td class="text-muted small" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= htmlspecialchars($d['address']??'—') ?></td>
        <td><span class="badge bg-primary rounded-pill"><?= $d['don_count'] ?></span></td>
        <td class="text-center">
          <div class="d-flex gap-1 justify-content-center">
            <?php if(canEdit()): ?>
            <a href="donors.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-sm btn-warning py-1 px-2"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if(canDelete()): ?>
            <a href="donors.php?action=delete&id=<?= $d['id'] ?>" class="btn btn-sm btn-danger py-1 px-2"
               onclick="return confirm('Delete donor &quot;<?= addslashes($d['name']) ?>&quot;? All their donations will also be deleted!')">
               <i class="bi bi-trash"></i></a>
            <?php endif; ?>
            <?php if(!canEdit()): ?><span class="text-muted small">View only</span><?php endif; ?>
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