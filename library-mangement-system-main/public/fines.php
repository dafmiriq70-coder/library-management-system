<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], [ROLE_ADMIN, ROLE_LIBRARIAN], true)) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$message = '';

// Mark fine as paid
if (isset($_GET['pay'])) {
    $fine_id = (int)$_GET['pay'];
    $stmt = $pdo->prepare('UPDATE fines SET is_paid = 1 WHERE id = :id');
    $stmt->execute([':id' => $fine_id]);
    $message = 'Ganaaxa waa la bixiyay.';
}

$fines = $pdo->query('SELECT f.*, bi.issue_date, bi.due_date, bi.return_date, b.title_somali, u.full_name AS member_name 
                      FROM fines f
                      JOIN book_issues bi ON f.issue_id = bi.id
                      JOIN books b ON bi.book_id = b.id
                      JOIN users u ON bi.member_id = u.id
                      ORDER BY f.created_at DESC')->fetchAll();



    if (isset($message) && $message === 'Ganaaxa waa la bixiyay.') {
        $message = 'Fine marked as paid.';
    }
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">Fines Management</h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Fines List</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Book Title</th>
                                <th>Member</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines as $f): ?>
                                <tr>
                                    <td><?php echo $f['id']; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($f['title_somali']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <?php echo htmlspecialchars($f['member_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($f['issue_date']); ?></td>
                                    <td><?php echo htmlspecialchars($f['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($f['return_date']); ?></td>
                                    <td class="text-end fw-bold text-danger">$<?php echo number_format($f['amount'], 2); ?></td>
                                    <td class="text-center">
                                        <?php if ($f['is_paid']): ?>
                                            <span class="badge bg-success-subtle text-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$f['is_paid']): ?>
                                            <a href="fines.php?pay=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to mark this fine as paid?');">
                                                <i class="bi bi-check-lg me-1"></i>Mark Paid
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-check2-all me-1"></i>Completed</span>
                                        <?php endif; ?>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>


