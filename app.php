<?php
/**
 * Main application entry - uses AppLayout with TopNav + page content
 */
require_once __DIR__ . '/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$currentPage = $_GET['page'] ?? 'pos';

// Role-based access for TopNav
$canAccessDashboard = $auth->canAccessDashboard();
$canAccessKitchen = $auth->canAccessKitchen();
$canAccessUsers = $auth->canAccessUsers();
$canAccessMenu = $auth->canAccessMenu();
$canAccessInventory = $auth->canAccessInventory();
$canAccessReports = $auth->canAccessReports();

// Access control: redirect if user tries to access page they can't
$restricted = [
    'dashboard' => $canAccessDashboard,
    'kitchen'   => $canAccessKitchen,
    'users'     => $canAccessUsers,
    'menu'      => $canAccessMenu,
    'inventory' => $canAccessInventory,
    'reports'   => $canAccessReports,
];
if (isset($restricted[$currentPage]) && !$restricted[$currentPage]) {
    $currentPage = 'pos';
}

$pageFiles = [
    'pos' => __DIR__ . '/views/pages/pos.php',
    'dashboard' => __DIR__ . '/views/pages/dashboard.php',
    'orders' => __DIR__ . '/views/pages/orders.php',
    'kitchen' => __DIR__ . '/views/pages/kitchen.php',
    'menu' => __DIR__ . '/views/pages/menu.php',
    'inventory' => __DIR__ . '/views/pages/inventory.php',
    'users' => __DIR__ . '/views/pages/users.php',
    'reports' => __DIR__ . '/views/pages/reports.php',
];

$pageFile = $pageFiles[$currentPage] ?? $pageFiles['pos'];

// Initialize variables that pages will set
$pageContent = '';
$pageTitle = '';
$pageStyles = [];
$pageScripts = [];

if (file_exists($pageFile)) {
    // Include the page file - it will set $pageContent, $pageTitle, etc.
    include $pageFile;
} else {
    $pageContent = '<div class="container"><h2>Page not found</h2></div>';
    $pageTitle = 'Not Found';
    $pageStyles = ['app-layout.css'];
    $pageScripts = [];
}

$pageTitle = $pageTitle ?? 'E.U.T Restaurant POS';
$pageStyles = $pageStyles ?? ['app-layout.css', 'pos.css'];
$pageScripts = $pageScripts ?? [];

require __DIR__ . '/views/layouts/AppLayout.php';
?>
