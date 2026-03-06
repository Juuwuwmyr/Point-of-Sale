<?php
// TopNav - Requires $currentUser, $canAccessDashboard, $canAccessKitchen, $canAccessUsers, $canAccessMenu, $canAccessInventory, $canAccessReports
$currentPage = $currentPage ?? 'pos';
$canAccessDashboard = $canAccessDashboard ?? false;
$canAccessKitchen = $canAccessKitchen ?? false;
$canAccessUsers = $canAccessUsers ?? false;
$canAccessMenu = $canAccessMenu ?? false;
$canAccessInventory = $canAccessInventory ?? false;
$canAccessReports = $canAccessReports ?? false;
?>
<nav class="top-nav">
    <div class="top-nav-left">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-utensils"></i></div>
            <div class="logo-text">E.U.T POS</div>
        </div>
        <div class="nav-menu">
            <?php if ($canAccessDashboard): ?>
            <a href="app.php?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span>Dashboard</span>
            </a>
            <?php endif; ?>
            <a href="app.php?page=pos" class="nav-item <?= $currentPage === 'pos' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-cash-register"></i></span>
                <span>POS</span>
            </a>
            <a href="app.php?page=orders" class="nav-item <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span>Orders</span>
            </a>
            <?php if ($canAccessKitchen): ?>
            <a href="app.php?page=kitchen" class="nav-item <?= $currentPage === 'kitchen' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-utensils"></i></span>
                <span>Kitchen</span>
            </a>
            <?php endif; ?>
            <?php if ($canAccessMenu): ?>
            <a href="app.php?page=menu" class="nav-item <?= $currentPage === 'menu' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-list"></i></span>
                <span>Menu</span>
            </a>
            <?php endif; ?>
            <?php if ($canAccessUsers): ?>
            <a href="app.php?page=users" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                <span>Users</span>
            </a>
        <?php endif; ?>
       
        </div>
    </div>
    <div class="top-nav-right">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($currentUser['username'] ?? 'User'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($currentUser['role'] ?? ''); ?></div>
            </div>
        </div>
        <div class="logout">
            <a href="controllers/AuthController.php?action=logout" class="nav-item logout-btn">
                <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>
