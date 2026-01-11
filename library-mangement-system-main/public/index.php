<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$remembered_email = $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    if ($email && $password) {
        $pdo = getPDO();
        // Queries to fetch user with role name
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name 
                               FROM users u 
                               JOIN roles r ON u.role_id = r.id 
                               WHERE u.email = :email AND u.status = 'active'
                               LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Verify password hash
        if ($user && password_verify($password, $user['password_hash'])) {
            // Unset sensitive data
            unset($user['password_hash']);
            
            // Normalize role to be accessed as $user['role'] for compatibility
            $user['role'] = $user['role_name'];
            
            $_SESSION['user'] = $user;
            
            // Handle "Remember me" - store email in cookie for 30 days
            if ($remember_me) {
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            } else {
                // Delete cookie if not checked
                setcookie('remember_email', '', time() - 3600, '/');
            }
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Incorrect email or password";
        }
    } else {
        $error = "Please enter both email and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-container">

<div class="login-card">
    <div class="login-header">
        <h2>UniLibrary</h2>
        <p>University Library Management</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="admin@example.com" required value="<?php echo htmlspecialchars($remembered_email); ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="******" required>
        </div>
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me" <?php echo $remembered_email ? 'checked' : ''; ?>>
                <label class="form-check-label" for="remember_me">
                    Remember me
                </label>
            </div>
            <div>
                <a href="forgot_password.php" class="text-decoration-none small">Forgot password?</a>
            </div>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>
    <div class="text-center mt-2">
        <small class="text-muted">
            Don't have an account? <a href="register.php">Register here</a>
        </small>
    </div>
</div>

</body>
</html>
