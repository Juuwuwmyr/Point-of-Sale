<?php
// Dashboard page - uses AppLayout with TopNav and dashboard-specific CSS/JS

$pageTitle = 'Dashboard - E.U.T POS';
$pageStyles = ['pages/dashboard.css'];
$pageScripts = [
    'dashboard.js'
];

ob_start();
?>
<div class="dashboard-page page-content">
    <div class="dashboard-header">
        <div>
            <h1>Dashboard</h1>
            <div class="page-subtitle">
                Today’s performance overview, sales trend, and recent orders.
            </div>
        </div>
        <?php if (!empty($_SESSION['is_admin'])): ?>
        <button type="button" class="btn btn-outline-danger btn-reset-dashboard" id="btnResetDashboard" title="Clear all sales data and set dashboard to zero">Reset dashboard</button>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card orders">
            <div class="stat-icon">
                📦
            </div>
            <div class="stat-content">
                <div class="stat-label">TODAY'S ORDERS</div>
                <div class="stat-number" id="stat-total-orders">0</div>
            </div>
        </div>

        <div class="stat-card sales">
            <div class="stat-icon">
                💰
            </div>
            <div class="stat-content">
                <div class="stat-label">TODAY'S SALES</div>
                <div class="stat-number" id="stat-total-sales">₱0.00</div>
            </div>
        </div>

        <div class="stat-card week">
            <div class="stat-icon">
                📅
            </div>
            <div class="stat-content">
                <div class="stat-label">SALES (7 DAYS)</div>
                <div class="stat-number" id="stat-week-sales">₱0.00</div>
            </div>
        </div>

        <div class="stat-card average">
            <div class="stat-icon">
                📊
            </div>
            <div class="stat-content">
                <div class="stat-label">OVERALL SALES</div>
                <div class="stat-number" id="stat-overall-sales">₱0.00</div>
                <div class="page-subtitle" id="stat-open-orders">0 open orders</div>
            </div>
        </div>
    </div>

    <div class="dashboard-main-content">
        <div class="content-left">
            <div class="chart-section" style="flex: 1; display: flex; flex-direction: column;">
                <div class="section-header">
                    <h3>All Items Sold Today</h3>
                </div>
                <div id="topItems" class="top-items-grid">
                    <div class="loading-spinner">Loading top items...</div>
                </div>
            </div>
        </div>

        <div class="content-right">
            <div class="recent-orders-section">
                <div class="section-header">
                    <h3>Recent Orders</h3>
                    <a href="app.php?page=orders" class="view-all">View all</a>
                </div>
                <div id="recentOrders" class="recent-orders-list">
                    <div class="recent-empty">Loading recent orders...</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
?>


