<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'The email you entered is not valid.';
    } else {
        $pdo = getPDO();
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Don't reveal if email exists or not for security
            $success = 'If your email is correct, a reset link will be sent to you.';
        } else {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour from now
            
            // Store token in database
            // First, check if password_reset_token column exists
            try {
                // Check if columns exist
                $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
                $columnExists = $checkColumns->fetch();
                
                if (!$columnExists) {
                    // Columns don't exist, try to add them
                    try {
                        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL AFTER password_hash");
                        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token");
                    } catch (PDOException $e) {
                        $error = 'Database setup required. Please run: <a href="setup_password_reset.php" target="_blank">setup_password_reset.php</a> or run the SQL migration file.';
                        throw new Exception('Database columns not available');
                    }
                }
                
                // Now update the user with token
                $stmt = $pdo->prepare("UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id");
                $stmt->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':id' => $user['id']
                ]);
                
                // In a real system, you would send an email here
                // For now, we'll show the reset link
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                              "://" . $_SERVER['HTTP_HOST'] . 
                              dirname($_SERVER['PHP_SELF']) . 
                              "/reset_password.php?token=" . $token;
                
                $success = 'Reset link has been sent. Please check your email.<br><br>';
                $success .= '<strong>Note:</strong> For testing purposes, here is the reset link:<br>';
                $success .= '<a href="' . htmlspecialchars($reset_link) . '" class="btn btn-sm btn-primary mt-2">Reset Link</a>';
            } catch (Exception $e) {
                if (empty($error)) {
                    $error = 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '<br>Please contact the administrator or run <a href="setup_password_reset.php" target="_blank">setup_password_reset.php</a>';
                }
            } catch (PDOException $e) {
                $error = 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '<br>Please contact the administrator or run <a href="setup_password_reset.php" target="_blank">setup_password_reset.php</a>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forget Password - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-container">

<div class="login-card">
    <div class="login-header">
        <h2>Forget Your Password</h2>
        <p>Enter your email to reset your password</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-outline-primary">Back to Login</a>
        </div>
    <?php else: ?>
        <form method="post">
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="axmed@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <div class="form-text">We will send you a reset link to your email.</div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">
                Remember your password? <a href="index.php">Login here</a>
            </small>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

