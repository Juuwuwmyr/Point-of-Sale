<?php
/**
 * AppLayout - Main application layout with TopNav + main content container
 * Requires: $currentUser, $pageContent, $pageTitle, $pageStyles (array), $pageScripts (array)
 */
$pageTitle = $pageTitle ?? 'E.U.T Restaurant POS';
$pageStyles = $pageStyles ?? ['app-layout.css', 'pos.css'];
$pageScripts = $pageScripts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Icon Settings -->
    <link rel="icon" type="image/x-icon" href="images/logo.ico">
    <link rel="shortcut icon" type="image/x-icon" href="images/logo.ico">
    <link rel="apple-touch-icon" href="images/logo.ico">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="E.U.T POS">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app-layout.css">
    <link rel="stylesheet" href="assets/css/pos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php
    $baseStyles = ['app-layout.css', 'pos.css'];
    foreach (array_diff($pageStyles ?? [], $baseStyles) as $css):
?>
    <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../partials/TopNav.php'; ?>
        <main class="app-main-container">
            <?php echo $pageContent; ?>
        </main>
    </div>

    <?php foreach ($pageScripts as $js): ?>
        <?php if (strpos($js, 'http') === 0): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php else: ?>
            <script src="assets/js/<?php echo htmlspecialchars($js); ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
