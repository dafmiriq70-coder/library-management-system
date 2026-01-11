<?php
require_once __DIR__ . '/../config/config.php';

$isLoggedIn = isset($_SESSION['user']);
$currentUser = $isLoggedIn ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top mb-4 border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-book-half me-2"></i>UniLibrary
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3 d-flex align-items-center">
                            <?php
                            // Generate initials from full name
                            $initials = strtoupper(substr($currentUser['full_name'], 0, 1));
                            if (isset($currentUser['full_name']) && strpos($currentUser['full_name'], ' ') !== false) {
                                $nameParts = explode(' ', trim($currentUser['full_name']));
                                if (count($nameParts) >= 2) {
                                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
                                }
                            }
                            
                            // Check for profile picture
                            $profile_picture = $currentUser['profile_picture'] ?? null;
                            $profile_path = __DIR__ . '/../public/uploads/profiles/' . ($profile_picture ?? '');
                            ?>
                            <?php if ($profile_picture && file_exists($profile_path)): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile" 
                                     class="rounded-circle me-2" 
                                     style="width: 36px; height: 36px; object-fit: cover; border: 2px solid #dee2e6; flex-shrink: 0;">
                            <?php else: ?>
                                <div class="rounded-circle me-2 d-flex align-items-center justify-content-center bg-dark text-white fw-bold" 
                                     style="width: 36px; height: 36px; font-size: 0.9rem; flex-shrink: 0;">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>
                            <?php endif; ?>
                            <span class="me-1 fw-medium"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                            <span class="badge bg-dark rounded-pill ms-1 text-uppercase" style="font-size: 0.7rem; font-weight: 600;">
                                <?php echo htmlspecialchars($currentUser['role']); ?>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid">

