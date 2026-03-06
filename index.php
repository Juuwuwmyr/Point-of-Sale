<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}
header('Location: views/login.php');
exit;
?>
