<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getPDO();

// Simple stats for dashboard
$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$availableBooks = (int)$pdo->query('SELECT COUNT(*) FROM books WHERE available_copies > 0')->fetchColumn();
$issuedToday = (int)$pdo->query('SELECT COUNT(*) FROM book_issues WHERE DATE(issue_date) = CURDATE()')->fetchColumn();
$lateReturns = (int)$pdo->query('SELECT COUNT(*) FROM book_issues WHERE return_date IS NULL AND due_date < CURDATE()')->fetchColumn();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0"> <!-- g-0 for full width sidebar look if needed, or normal row -->
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted mb-0">Role: <span class="badge bg-primary text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span></p>
            </div>
            <div>
               <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                             <div class="bg-indigo-100 text-primary p-2 rounded me-3">
                                 <i class="bi bi-book fs-4"></i>
                             </div>
                             <h6 class="text-muted mb-0">Total Books</h6>
                        </div>
                        <h3 class="mb-0 fw-bold"><?php echo $totalBooks; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                     <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                             <div class="bg-teal-100 text-success p-2 rounded me-3">
                                 <i class="bi bi-check-circle fs-4"></i>
                             </div>
                             <h6 class="text-muted mb-0">Available</h6>
                        </div>
                        <h3 class="mb-0 fw-bold"><?php echo $availableBooks; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                     <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                             <div class="bg-amber-100 text-warning p-2 rounded me-3">
                                 <i class="bi bi-journal-arrow-down fs-4"></i>
                             </div>
                             <h6 class="text-muted mb-0">Issued Today</h6>
                        </div>
                        <h3 class="mb-0 fw-bold"><?php echo $issuedToday; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                     <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                             <div class="bg-rose-100 text-danger p-2 rounded me-3">
                                 <i class="bi bi-exclamation-circle fs-4"></i>
                             </div>
                             <h6 class="text-muted mb-0">Overdue</h6>
                        </div>
                        <h3 class="mb-0 fw-bold"><?php echo $lateReturns; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


