<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
$auth = new AuthController();
$auth->requireLogin();
$auth->requireAdmin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$users = $user->getAll();
$activeCount = count(array_filter($users, fn($u) => $u['IsActive']));
$rolesQuery = "SELECT * FROM roles ORDER BY RoleName";
$rolesStmt = $db->prepare($rolesQuery);
$rolesStmt->execute();
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'User Management - E.U.T Restaurant POS';
$pageStyles = ['pages/users.css', 'style.css'];
$pageScripts = [];

ob_start();
?>
<div class="users-page page-content">
    <div class="page-header">
        <h1>User Management</h1>
        <button class="btn btn-primary" onclick="openUserModal()">+ Add User</button>
    </div>

    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <div class="users-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($users) ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $activeCount ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($users) - $activeCount ?></div>
            <div class="stat-label">Inactive</div>
        </div>
    </div>

    <div class="users-card">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search users..." onkeyup="filterUsers()">
        </div>
        <div class="filter-row">
            <button class="filter-btn active" onclick="filterByStatus('all', this)">All</button>
            <button class="filter-btn" onclick="filterByStatus('active', this)">Active</button>
            <button class="filter-btn" onclick="filterByStatus('inactive', this)">Inactive</button>
        </div>
        <div id="usersList">
            <?php foreach ($users as $u): ?>
            <?php $roleClass = 'role-' . strtolower($u['RoleName']); ?>
            <div class="user-row" data-status="<?= $u['IsActive'] ? 'active' : 'inactive' ?>" data-search="<?= strtolower(htmlspecialchars($u['Username'] . ' ' . $u['FullName'] . ' ' . $u['RoleName'])) ?>">
                <div class="user-meta">
                    <div class="name">
                        <?= htmlspecialchars($u['FullName']) ?>
                        <span class="role-badge <?= $roleClass ?>"><?= htmlspecialchars($u['RoleName']) ?></span>
                    </div>
                    <div class="detail"><?= htmlspecialchars($u['Username']) ?> • ID: <?= $u['UserID'] ?></div>
                </div>
                <div class="btn-row">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-right:0.5rem;">
                        <span style="font-size:0.8rem;">Active:</span>
                        <div class="toggle-switch <?= $u['IsActive'] ? 'active' : '' ?>" onclick="toggleStatus(<?= $u['UserID'] ?>, <?= $u['IsActive'] ? 0 : 1 ?>)"></div>
                    </div>
                    <button class="btn btn-sm btn-warning" onclick="editUser(<?= $u['UserID'] ?>)">Edit</button>
                    <?php if ($u['UserID'] != $currentUser['id']): ?>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $u['UserID'] ?>)">Delete</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="userModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="userModalTitle">Add User</h3>
            <button class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="userForm" action="controllers/UserController.php" method="POST">
            <input type="hidden" name="action" id="userAction" value="add_user">
            <input type="hidden" name="user_id" id="userId">
            <div class="form-row">
                <div class="form-group"><label>Username *</label><input type="text" name="username" id="username" class="form-control" required></div>
                <div class="form-group"><label>Full Name *</label><input type="text" name="fullname" id="fullname" class="form-control" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label id="passLabel">Password *</label><input type="password" name="password" id="password" class="form-control"></div>
                <div class="form-group"><label>Role *</label>
                    <select name="role_id" id="role_id" class="form-control" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $r): ?><option value="<?= $r['RoleID'] ?>"><?= htmlspecialchars($r['RoleName']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                    <span>Active User</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal(userId) {
    const title = document.getElementById('userModalTitle');
    const action = document.getElementById('userAction');
    const pass = document.getElementById('password');
    const passLabel = document.getElementById('passLabel');
    if (userId) {
        title.textContent = 'Edit User';
        action.value = 'update_user';
        document.getElementById('userId').value = userId;
        pass.required = false;
        passLabel.textContent = 'Password (leave blank to keep)';
    } else {
        title.textContent = 'Add User';
        action.value = 'add_user';
        document.getElementById('userId').value = '';
        pass.required = true;
        passLabel.textContent = 'Password *';
        document.getElementById('userForm').reset();
    }
    document.getElementById('userModal').classList.add('show');
}
function closeUserModal() { document.getElementById('userModal').classList.remove('show'); }
function editUser(id) { openUserModal(id); }
function deleteUser(id) {
    if (!confirm('Delete this user?')) return;
    const f = document.createElement('form');
    f.method = 'POST'; f.action = 'controllers/UserController.php';
    const a = document.createElement('input'); a.type = 'hidden'; a.name = 'action'; a.value = 'delete_user';
    const i = document.createElement('input'); i.type = 'hidden'; i.name = 'user_id'; i.value = id;
    f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit();
}
function toggleStatus(id, status) {
    const f = document.createElement('form');
    f.method = 'POST'; f.action = 'controllers/UserController.php';
    const a = document.createElement('input'); a.type = 'hidden'; a.name = 'action'; a.value = 'toggle_status';
    const i = document.createElement('input'); i.type = 'hidden'; i.name = 'user_id'; i.value = id;
    const s = document.createElement('input'); s.type = 'hidden'; s.name = 'is_active'; s.value = status;
    f.appendChild(a); f.appendChild(i); f.appendChild(s); document.body.appendChild(f); f.submit();
}
function filterUsers() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(r => {
        r.style.display = r.dataset.search.includes(q) ? 'flex' : 'none';
    });
}
function filterByStatus(s, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.user-row').forEach(r => {
        r.style.display = (s === 'all' || r.dataset.status === s) ? 'flex' : 'none';
    });
}
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserModal();
});
</script>
<?php
$pageContent = ob_get_clean();
?>
