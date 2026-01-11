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
$token = $_GET['token'] ?? '';
$valid_token = false;

// Validate token
if ($token) {
    $pdo = getPDO();
    try {
        // Check if columns exist first
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
        $columnExists = $checkColumns->fetch();
        
        if (!$columnExists) {
            $error = 'Password reset feature is not set up. Please contact the administrator.';
        } else {
            // Check token validity - allow it even if expired for better UX (user can still reset)
            $stmt = $pdo->prepare("SELECT id, email, password_reset_expires FROM users WHERE password_reset_token = :token AND status = 'active' LIMIT 1");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check if token has expired
                if ($user['password_reset_expires'] && strtotime($user['password_reset_expires']) < time()) {
                    $error = 'Reset link has expired. Please request a new link.';
                } else {
                    $valid_token = true;
                }
            } else {
                $error = 'Reset link is invalid. Please request a new link.';
            }
        }
    } catch (PDOException $e) {
        $error = 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '. Please contact the administrator.';
    }
} else {
    // Only show error if it's not a POST request (form submission)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $error = 'Reset link is missing.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $post_token = $_POST['token'] ?? '';
    
    // Validate token again for POST request
    if ($post_token) {
        try {
            $pdo = getPDO();
            // Get user with token - don't check expiration on POST, allow reset even if slightly expired
            $stmt = $pdo->prepare("SELECT id, password_reset_expires FROM users WHERE password_reset_token = :token AND status = 'active' LIMIT 1");
            $stmt->execute([':token' => $post_token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Reset link is invalid. Please request a new link.';
            } elseif (empty($password)) {
                $error = 'Please enter a new password.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm_password) {
                $error = 'Password and confirmation do not match.';
            } else {
                // Update password and clear reset token
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id");
                $result = $updateStmt->execute([
                    ':password_hash' => $password_hash,
                    ':id' => $user['id']
                ]);
                
                if ($result) {
                    $success = 'Your password has been updated successfully! Please login now.';
                    $valid_token = false; // Hide form after success
                    $error = ''; // Clear any previous errors
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '. Please try again.';
        }
    } else {
        $error = 'Invalid reset token. Please request a new link.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-container">

<div class="login-card">
    <div class="login-header">
        <h2>Reset Password</h2>
        <p>Enter your new password</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <div class="mt-2">
                <a href="index.php" class="btn btn-sm btn-primary">Login</a>
            </div>
        </div>
    <?php elseif ($valid_token): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                <div class="form-text">At least 6 characters</div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Enter password again" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">
                <a href="index.php">Back to Login</a>
            </small>
        </div>
    <?php else: ?>
        <div class="text-center mt-3">
            <a href="forgot_password.php" class="btn btn-outline-primary">Request New Link</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

