<?php
// Dashboard page - uses AppLayout with TopNav and dashboard-specific CSS/JS

$pageTitle = 'Dashboard - E.U.T POS';
$pageStyles = ['pages/dashboard.css'];
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js',
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

        <div class="stat-card average">
            <div class="stat-icon">
                📊
            </div>
            <div class="stat-content">
                <div class="stat-label">AVERAGE ORDER VALUE</div>
                <div class="stat-number" id="stat-average-order">₱0.00</div>
                <div class="page-subtitle" id="stat-open-orders">0 open orders</div>
            </div>
        </div>
    </div>

    <div class="dashboard-main-content">
        <div class="content-left">
            <div class="chart-section">
                <div class="section-header">
                    <h3>Last 7 Days Sales</h3>
                </div>
                <div class="canvas-container">
                    <canvas id="salesChart"></canvas>
                    <div id="salesChartEmpty" class="chart-empty" style="display:none;">
                        No sales data available for the last 7 days.
                    </div>
                </div>
            </div>

            <div class="chart-section">
                <div class="section-header">
                    <h3>Top Items Today</h3>
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


