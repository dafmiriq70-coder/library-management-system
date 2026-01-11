<?php
$page = basename($_SERVER['PHP_SELF']);
// Ensure $user is defined (it should be from session)
$user = $_SESSION['user'] ?? null;
?>
<div class="col-md-2 sidebar p-4 d-none d-md-block">
    <h6 class="text-uppercase text-muted mb-4 small fw-bold">Main Menu</h6>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <?php if ($user && $user['role'] === ROLE_ADMIN): ?>
            <a href="users.php" class="list-group-item list-group-item-action <?php echo $page == 'users.php' ? 'active' : ''; ?>">
                <i class="bi bi-people me-2"></i>User Management
            </a>
        <?php endif; ?>
        <?php if ($user && ($user['role'] === ROLE_ADMIN || $user['role'] === ROLE_LIBRARIAN)): ?>
            <a href="books.php" class="list-group-item list-group-item-action <?php echo $page == 'books.php' ? 'active' : ''; ?>">
                <i class="bi bi-book me-2"></i>Book Management
            </a>
            <a href="issues.php" class="list-group-item list-group-item-action <?php echo $page == 'issues.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-left-right me-2"></i>Issues & Returns
            </a>
            <a href="fines.php" class="list-group-item list-group-item-action <?php echo $page == 'fines.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-coin me-2"></i>Fines
            </a>
        <?php endif; ?>
        <a href="catalog.php" class="list-group-item list-group-item-action <?php echo $page == 'catalog.php' ? 'active' : ''; ?>">
            <i class="bi bi-search me-2"></i>Catalog Search
        </a>
        <a href="reports.php" class="list-group-item list-group-item-action <?php echo $page == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up me-2"></i>Reports
        </a>
        <a href="profile.php" class="list-group-item list-group-item-action <?php echo $page == 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle me-2"></i>My Profile
        </a>
    </div>
</div>
