<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E.U.T Restaurant POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/pages/login.css">
</head>
<body>
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <div class="coffee-beans">
        <div class="bean bean-1"><i class="fas fa-seedling"></i></div>
        <div class="bean bean-2"><i class="fas fa-seedling"></i></div>
        <div class="bean bean-3"><i class="fas fa-seedling"></i></div>
        <div class="bean bean-4"><i class="fas fa-seedling"></i></div>
        <div class="bean bean-5"><i class="fas fa-seedling"></i></div>
        <div class="bean bean-6"><i class="fas fa-seedling"></i></div>
    </div>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h1>E.U.T Restaurant</h1>
                <p>Sign in to access your POS dashboard</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="../controllers/AuthController.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" placeholder="Enter your username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> E.U.T Restaurant POS System. All rights reserved.
        </div>
    </div>
</body>
</html>
