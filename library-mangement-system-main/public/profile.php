<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getPDO();
$message = '';
$error = '';

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
        } elseif ($file['size'] > 2097152) { // 2MB limit
            $error = 'File size is too large. Maximum size is 2MB.';
        } else {
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user['id'] . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            // Delete old profile picture if exists
            if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                @unlink($upload_dir . $user['profile_picture']);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Check if profile_picture column exists, if not add it
                try {
                    $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
                    $columnExists = $checkColumns->fetch();
                    
                    if (!$columnExists) {
                        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER email");
                    }
                    
                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute([
                        ':profile_picture' => $new_filename,
                        ':id' => $user['id']
                    ]);
                    
                    // Update session
                    $_SESSION['user']['profile_picture'] = $new_filename;
                    $user['profile_picture'] = $new_filename;
                    
                    $message = 'Profile picture uploaded successfully!';
                } catch (PDOException $e) {
                    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
                    @unlink($target_path); // Delete uploaded file if database update fails
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please select a file to upload.';
    } else {
        $error = 'Upload error occurred. Please try again.';
    }
}

// Get current user data with profile picture
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $user['id']]);
$userData = $stmt->fetch();
if ($userData) {
    $user = array_merge($user, $userData);
    $_SESSION['user'] = $user;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">My Profile</h3>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Profile Picture</h5>
                        
                        <div class="text-center mb-4">
                            <?php
                            $profile_picture = $user['profile_picture'] ?? null;
                            $initials = strtoupper(substr($user['full_name'], 0, 1));
                            if (isset($user['full_name']) && strpos($user['full_name'], ' ') !== false) {
                                $nameParts = explode(' ', trim($user['full_name']));
                                if (count($nameParts) >= 2) {
                                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
                                }
                            }
                            ?>
                            <?php if ($profile_picture && file_exists($upload_dir . $profile_picture)): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile Picture" 
                                     class="rounded-circle mb-3" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #dee2e6;">
                            <?php else: ?>
                                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-dark text-white fw-bold" 
                                     style="width: 150px; height: 150px; font-size: 3rem;">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Upload New Profile Picture</label>
                                <input type="file" 
                                       name="profile_picture" 
                                       id="profile_picture" 
                                       class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif"
                                       required>
                                <div class="form-text">Allowed formats: JPEG, PNG, GIF. Maximum size: 2MB</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Upload Picture
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Profile Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted fw-bold" style="width: 40%;">Full Name:</td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Email:</td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Role:</td>
                                <td>
                                    <span class="badge bg-primary text-uppercase">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Status:</td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Disabled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Member Since:</td>
                                <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

