<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();

// Basic stats
$issuedBooks = (int)$pdo->query('SELECT COUNT(*) FROM book_issues')->fetchColumn();
$lateReturns = (int)$pdo->query('SELECT COUNT(*) FROM book_issues WHERE return_date IS NULL AND due_date < CURDATE()')->fetchColumn();
$mostBorrowed = $pdo->query('SELECT b.title_somali, COUNT(*) AS count_issues 
                             FROM book_issues bi 
                             JOIN books b ON bi.book_id = b.id 
                             GROUP BY bi.book_id 
                             ORDER BY count_issues DESC 
                             LIMIT 5')->fetchAll();

$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

$dailyStats = $pdo->query('SELECT DATE(issue_date) AS day, COUNT(*) AS total 
                           FROM book_issues 
                           GROUP BY DATE(issue_date) 
                           ORDER BY day DESC 
                           LIMIT 7')->fetchAll();

include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">System Reports</h3>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-primary-subtle p-3 me-3">
                            <i class="bi bi-journal-text text-primary fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Issues</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?php echo $issuedBooks; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-danger-subtle p-3 me-3">
                            <i class="bi bi-exclamation-circle text-danger fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Overdue Books</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?php echo $lateReturns; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-success-subtle p-3 me-3">
                            <i class="bi bi-people text-success fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Active Users</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?php echo $activeUsers; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold mb-0">Most Borrowed Books</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Book Title (Original)</th>
                                        <th class="text-center pe-3">Issues Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mostBorrowed as $mb): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium text-dark"><?php echo htmlspecialchars($mb['title_somali']); ?></td>
                                            <td class="text-center pe-3">
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?php echo $mb['count_issues']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title fw-bold mb-0">Daily Activity (Last 7 Days)</h5>
                    </div>
                    <div class="card-body p-0">
                         <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th class="text-center pe-3">Issues</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dailyStats as $ds): ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?php echo htmlspecialchars($ds['day']); ?></td>
                                            <td class="text-center pe-3">
                                                <span class="fw-bold text-dark"><?php echo $ds['total']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


