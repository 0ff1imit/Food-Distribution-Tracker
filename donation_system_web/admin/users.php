<?php
session_start();
define('BASE_URL','..');
require_once BASE_URL.'/includes/db.php';
require_once BASE_URL.'/includes/auth.php';
require_once BASE_URL.'/includes/flash.php';
requireRole(['admin']);

$pageTitle = 'User Management';

// Handle actions
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// Approve user
if ($action === 'approve' && $id) {
    $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
    setFlash('success','User account approved and activated.');
    header('Location: users.php'); exit;
}

// Reject / deactivate
if ($action === 'deactivate' && $id) {
    if ($id === (int)$_SESSION['user_id']) { setFlash('warning','You cannot deactivate your own account.'); header('Location: users.php'); exit; }
    $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$id]);
    setFlash('success','User account deactivated.');
    header('Location: users.php'); exit;
}

// Reactivate
if ($action === 'activate' && $id) {
    $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
    setFlash('success','User account reactivated.');
    header('Location: users.php'); exit;
}

// Delete
if ($action === 'delete' && $id) {
    if ($id === (int)$_SESSION['user_id']) { setFlash('warning','You cannot delete your own account.'); header('Location: users.php'); exit; }
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    setFlash('success','User deleted permanently.');
    header('Location: users.php'); exit;
}

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['new_role'];
    if (in_array($role,['admin','procurement','volunteer']) && $uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
        setFlash('success','User role updated.');
    }
    header('Location: users.php'); exit;
}

// Stats
$pending  = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$active   = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$inactive = $pdo->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn();

// Filters
$statusF = $_GET['status'] ?? '';
$search  = trim($_GET['search'] ?? '');
$where   = "WHERE 1=1";
$params  = [];
if ($statusF) { $where .= " AND status=?"; $params[] = $statusF; }
if ($search)  { $where .= " AND (full_name LIKE ? OR email LIKE ?)"; $params = array_merge($params,["%$search%","%$search%"]); }

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY
    FIELD(status,'pending','active','inactive'), created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

include BASE_URL.'/includes/header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php if($pending > 0): ?>
  <div class="col-12">
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
      <i class="bi bi-bell-fill fs-5"></i>
      <strong><?= $pending ?> pending account<?= $pending>1?'s':'' ?> waiting for your approval.</strong>
      <a href="?status=pending" class="ms-2 btn btn-sm btn-warning">Review Now</a>
    </div>
  </div>
  <?php endif; ?>
  <div class="col-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-success bg-opacity-10"><i class="bi bi-person-check text-success fs-4"></i></div>
      <div><div class="fw-700 fs-5"><?= $active ?></div><div class="text-muted small">Active</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-warning bg-opacity-10"><i class="bi bi-hourglass text-warning fs-4"></i></div>
      <div><div class="fw-700 fs-5"><?= $pending ?></div><div class="text-muted small">Pending</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-circle p-3 bg-secondary bg-opacity-10"><i class="bi bi-person-x text-secondary fs-4"></i></div>
      <div><div class="fw-700 fs-5"><?= $inactive ?></div><div class="text-muted small">Inactive</div></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <span class="fw-600"><i class="bi bi-people me-2 text-primary"></i>User Accounts (<?= count($users) ?>)</span>
    <div class="d-flex gap-2 flex-wrap">
      <form class="d-flex gap-2" method="GET">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search…"
               value="<?= htmlspecialchars($search) ?>" style="width:180px">
        <select name="status" class="form-select form-select-sm" style="width:130px">
          <option value="">All Status</option>
          <?php foreach(['active','pending','inactive'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
        <?php if($search||$statusF): ?>
        <a href="users.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th class="px-3">User</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Status</th>
          <th>Registered</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="7" class="text-center py-5 text-muted">No users found.</td></tr>
      <?php endif; ?>
      <?php foreach($users as $u): ?>
        <?php $isSelf = $u['id'] == $_SESSION['user_id']; ?>
        <tr class="<?= $u['status']==='pending' ? 'table-warning' : ($u['status']==='inactive'?'table-secondary opacity-75':'') ?>">
          <td class="px-3 py-2">
            <div class="d-flex align-items-center gap-2">
              <img src="<?= $u['profile_picture']
                ? BASE_URL.'/uploads/profiles/'.htmlspecialchars($u['profile_picture'])
                : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=2563eb&color=fff&size=40'
              ?>" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
              <div>
                <div class="fw-600 small"><?= htmlspecialchars($u['full_name']) ?>
                  <?php if($isSelf): ?><span class="badge bg-secondary ms-1" style="font-size:.6rem">You</span><?php endif; ?>
                </div>
              </div>
            </div>
          </td>
          <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
          <td class="small"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td>
            <?php if (!$isSelf): ?>
            <form method="POST" class="d-flex align-items-center gap-1">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="hidden" name="change_role" value="1">
              <select name="new_role" class="form-select form-select-sm py-0" style="width:130px"
                      onchange="this.form.submit()">
                <?php foreach(['admin','procurement','volunteer'] as $r): ?>
                <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <?php else: ?>
            <span class="badge <?= roleBadgeClass($u['role']) ?>"><?= ucfirst($u['role']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php
            $sBadge = match($u['status']??'active') {
                'active'   => ['bg-success','check-circle','Active'],
                'pending'  => ['bg-warning text-dark','hourglass','Pending Approval'],
                'inactive' => ['bg-secondary','x-circle','Inactive'],
                default    => ['bg-secondary','question','Unknown'],
            };
            ?>
            <span class="badge <?= $sBadge[0] ?>">
              <i class="bi bi-<?= $sBadge[1] ?> me-1"></i><?= $sBadge[2] ?>
            </span>
          </td>
          <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="d-flex gap-1 justify-content-center flex-wrap">
              <?php if (($u['status']??'') === 'pending'): ?>
                <a href="users.php?action=approve&id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-success py-1 px-2" title="Approve"
                   onclick="return confirm('Approve account for <?= addslashes(htmlspecialchars($u['full_name'])) ?>?')">
                  <i class="bi bi-check-lg"></i> Approve
                </a>
                <a href="users.php?action=deactivate&id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-danger py-1 px-2" title="Reject">
                  <i class="bi bi-x-lg"></i> Reject
                </a>
              <?php elseif (($u['status']??'') === 'active' && !$isSelf): ?>
                <a href="users.php?action=deactivate&id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-warning py-1 px-2" title="Deactivate"
                   onclick="return confirm('Deactivate this user?')">
                  <i class="bi bi-pause-circle"></i>
                </a>
              <?php elseif (($u['status']??'') === 'inactive'): ?>
                <a href="users.php?action=activate&id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-success py-1 px-2" title="Reactivate">
                  <i class="bi bi-play-circle"></i>
                </a>
              <?php endif; ?>
              <?php if (!$isSelf): ?>
              <a href="users.php?action=delete&id=<?= $u['id'] ?>"
                 class="btn btn-sm btn-danger py-1 px-2" title="Delete"
                 onclick="return confirm('Permanently delete <?= addslashes(htmlspecialchars($u['full_name'])) ?>? This cannot be undone.')">
                <i class="bi bi-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include BASE_URL.'/includes/footer.php'; ?>