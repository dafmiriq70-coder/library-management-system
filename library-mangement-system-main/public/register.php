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
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name)) {
        $error = 'Fadlan geli magacaaga oo buuxa.';
    } elseif (empty($email)) {
        $error = 'Fadlan geli email-kaaga.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email-ka aad gelisay ma aha mid sax ah.';
    } elseif (empty($password)) {
        $error = 'Fadlan geli erayga sirta ah.';
    } elseif (strlen($password) < 6) {
        $error = 'Erayga sirta ah waa inuu ugu yaraan yahay 6 xaraf.';
    } elseif ($password !== $confirm_password) {
        $error = 'Erayga sirta ah iyo xaqiijinta ma isma qabsan.';
    } else {
        $pdo = getPDO();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error = 'Email-kan horeyba waa la isticmaalay. Fadlan isticmaal email kale.';
        } else {
            // Get member role_id (role_id = 3 for member)
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'member' LIMIT 1");
            $stmt->execute();
            $role = $stmt->fetch();
            
            if (!$role) {
                $error = 'Qalad ayaa dhacay. Fadlan la xidhiidh maamulaha.';
            } else {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role_id, status) VALUES (:full_name, :email, :password_hash, :role_id, 'active')");
                
                try {
                    $stmt->execute([
                        ':full_name' => $full_name,
                        ':email' => $email,
                        ':password_hash' => $password_hash,
                        ':role_id' => $role['id']
                    ]);
                    
                    $success = 'Diiwaangelinta aad u guulaysatay! Hadda waa inaad soo galto.';
                } catch (PDOException $e) {
                    $error = 'Qalad ayaa dhacay markii la aburaynayo akoonkaaga. Fadlan isku day mar kale.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-container">

<div class="login-card">
    <div class="login-header">
        <h2>Register</h2>
        <p>Abuur akoonkaaga cusub</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <div class="mt-2">
                <a href="index.php" class="btn btn-sm btn-primary">Soo gal</a>
            </div>
        </div>
    <?php else: ?>
        <form method="post">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Tusaale: Axmed Maxamed" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="axmed@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Ugu yaraan 6 xaraf" required>
                <div class="form-text">Ugu yaraan 6 xaraf</div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Geli erayga sirta ah mar kale" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">
                Already have an account? <a href="index.php">Login here</a>
            </small>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

