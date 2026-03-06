<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
$auth = new AuthController();
$auth->requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Order.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$today = date('Y-m-d');
$dailySales = $order->getDailySales($today);
$weekStart = date('Y-m-d', strtotime('-7 days'));
$weekSales = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(TotalAmount),0) as total FROM orders WHERE DATE(OrderDate) >= '$weekStart' AND Status = 'Paid'")->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Reports - E.U.T Restaurant POS';
$pageStyles = ['pages/reports.css', 'style.css'];
$pageScripts = [];

ob_start();
?>
<div class="reports-page page-content">
    <h1>Reports & Analytics</h1>

    <div class="reports-stats">
        <div class="stat-card">
            <div class="stat-number"><?= $dailySales['total_orders'] ?? 0 ?></div>
            <div class="stat-label">Today's Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">₱<?= number_format($dailySales['total_sales'] ?? 0, 0) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $weekSales['cnt'] ?? 0 ?></div>
            <div class="stat-label">Orders (7 days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">₱<?= number_format($weekSales['total'] ?? 0, 0) ?></div>
            <div class="stat-label">Sales (7 days)</div>
        </div>
    </div>

    <div class="reports-cards">
        <div class="report-card">
            <div class="report-icon">📅</div>
            <div class="report-title">Daily Report</div>
            <div class="report-desc">Sales & orders by day</div>
        </div>
        <div class="report-card">
            <div class="report-icon">📆</div>
            <div class="report-title">Weekly Report</div>
            <div class="report-desc">7-day summary</div>
        </div>
        <div class="report-card">
            <div class="report-icon">🗓️</div>
            <div class="report-title">Monthly Report</div>
            <div class="report-desc">Monthly analytics</div>
        </div>
        <div class="report-card">
            <div class="report-icon">📊</div>
            <div class="report-title">Top Items</div>
            <div class="report-desc">Best selling products</div>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
?>
