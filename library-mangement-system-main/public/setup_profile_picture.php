<?php
/**
 * Database Migration Script for Profile Picture
 * 
 * Run this file once in your browser to add profile_picture column to your database.
 * Example: http://localhost/library-system/public/setup_profile_picture.php
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
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        $messages[] = "✓ Column already exists. Database is ready!";
        $success = true;
    } else {
        // Add profile_picture column
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER email");
        $messages[] = "✓ Added profile_picture column";
        
        $messages[] = "✓ Database migration completed successfully!";
        $success = true;
    }
    
    // Create uploads directory
    $upload_dir = __DIR__ . '/uploads/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        $messages[] = "✓ Created uploads/profiles directory";
    } else {
        $messages[] = "✓ Uploads directory already exists";
    }
    
} catch (PDOException $e) {
    $errors[] = "Error: " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Picture Setup</title>
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
        <h2 class="mb-4">Profile Picture Database Setup</h2>
        
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
                    <li>Profile picture functionality is now ready to use</li>
                    <li>Go to "My Profile" in the sidebar to upload your picture</li>
                    <li><strong>For security, delete this file (setup_profile_picture.php) after running it</strong></li>
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
            <a href="profile.php" class="btn btn-outline-secondary">Go to Profile Page</a>
        </div>
    </div>
</body>
</html>

