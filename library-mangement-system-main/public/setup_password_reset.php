<?php
/**
 * Database Migration Script for Password Reset
 * 
 * Run this file once in your browser to add password reset columns to your database.
 * Example: http://localhost/library-system/public/setup_password_reset.php
 * 
 * After running successfully, you can delete this file for security.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$success = false;
$errors = [];
$messages = [];

try {
    $pdo = getPDO();
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
    $token_exists = $stmt->fetch();
    
    if ($token_exists) {
        $messages[] = "✓ Columns already exist. Database is ready!";
        $success = true;
    } else {
        // Add password_reset_token column
        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL AFTER password_hash");
        $messages[] = "✓ Added password_reset_token column";
        
        // Add password_reset_expires column
        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token");
        $messages[] = "✓ Added password_reset_expires column";
        
        // Add index for faster lookups
        try {
            $pdo->exec("CREATE INDEX idx_password_reset_token ON users(password_reset_token)");
            $messages[] = "✓ Added index for password_reset_token";
        } catch (PDOException $e) {
            // Index might already exist, that's okay
            $messages[] = "ℹ Index already exists or couldn't be created (not critical)";
        }
        
        $messages[] = "✓ Database migration completed successfully!";
        $success = true;
    }
    
} catch (PDOException $e) {
    $errors[] = "Error: " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
            max-width: 600px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h2 class="mb-4">Password Reset Database Setup</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h5>✓ Setup Complete!</h5>
                <ul class="mb-0">
                    <?php foreach ($messages as $msg): ?>
                        <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="alert alert-info">
                <strong>Next Steps:</strong>
                <ul class="mb-0 mt-2">
                    <li>Password reset functionality is now ready to use</li>
                    <li>You can now use "Forget Password" on the login page</li>
                    <li><strong>For security, delete this file (setup_password_reset.php) after running it</strong></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h5>✗ Setup Failed</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Go to Login Page</a>
            <a href="forgot_password.php" class="btn btn-outline-secondary">Test Forgot Password</a>
        </div>
    </div>
</body>
</html>

