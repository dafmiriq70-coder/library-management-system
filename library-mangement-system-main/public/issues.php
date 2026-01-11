<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], [ROLE_ADMIN, ROLE_LIBRARIAN], true)) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$message = '';

// Handle issue book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'issue') {
    $book_id   = (int)($_POST['book_id'] ?? 0);
    $member_id = (int)($_POST['member_id'] ?? 0);
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $due_date   = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));

    if ($book_id === 0 || $member_id === 0) {
        $message = 'Fadlan dooro buug iyo arday.';
    } else {
        // Check availability
        $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE id = :id');
        $stmt->execute([':id' => $book_id]);
        $book = $stmt->fetch();
        if (!$book || (int)$book['available_copies'] <= 0) {
            $message = 'Buuggan lama heli karo wakhtigan.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO book_issues (book_id, member_id, issued_by, issue_date, due_date) VALUES (:book_id, :member_id, :issued_by, :issue_date, :due_date)');
                $stmt->execute([
                    ':book_id'   => $book_id,
                    ':member_id' => $member_id,
                    ':issued_by' => $_SESSION['user']['id'],
                    ':issue_date'=> $issue_date,
                    ':due_date'  => $due_date,
                ]);

                $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = :id');
                $stmt->execute([':id' => $book_id]);

                $pdo->commit();
                $message = 'Buugga waa la amaahiyay.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Khalad ayaa dhacay: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Handle return book
if (isset($_GET['return'])) {
    $issue_id = (int)$_GET['return'];
    $today = date('Y-m-d');

    $pdo->beginTransaction();
    try {
        // Get issue and book
        $stmt = $pdo->prepare('SELECT * FROM book_issues WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $issue_id]);
        $issue = $stmt->fetch();

        if ($issue && $issue['status'] === 'issued') {
            // Update issue
            $stmt = $pdo->prepare('UPDATE book_issues SET status = "returned", return_date = :return_date WHERE id = :id');
            $stmt->execute([
                ':return_date' => $today,
                ':id'          => $issue_id,
            ]);

            // Update book copies
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = :book_id');
            $stmt->execute([':book_id' => $issue['book_id']]);

            // Calculate fine if late
            $due_date = new DateTime($issue['due_date']);
            $return_date = new DateTime($today);
            if ($return_date > $due_date) {
                $daysLate = (int)$due_date->diff($return_date)->days;
                if ($daysLate > 0) {
                    $amount = $daysLate * FINE_PER_DAY;
                    $stmt = $pdo->prepare('INSERT INTO fines (issue_id, amount) VALUES (:issue_id, :amount)');
                    $stmt->execute([
                        ':issue_id' => $issue_id,
                        ':amount'   => $amount,
                    ]);
                }
            }

            $pdo->commit();
            $message = 'Buugga waa la soo celiyay.';
        } else {
            $pdo->rollBack();
            $message = 'Amaahdaan lama heli karo ama hore ayaa loo soo celiyay.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Khalad ayaa dhacay: ' . htmlspecialchars($e->getMessage());
    }
}

// Fetch data for forms
$members = $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' AND role_id = (SELECT id FROM roles WHERE name = 'member' LIMIT 1) ORDER BY full_name")->fetchAll();
$availableBooks = $pdo->query('SELECT id, title_somali FROM books WHERE available_copies > 0 ORDER BY title_somali')->fetchAll();

// List of current issues
$issues = $pdo->query('SELECT bi.*, b.title_somali, u.full_name AS member_name 
                       FROM book_issues bi 
                       JOIN books b ON bi.book_id = b.id 
                       JOIN users u ON bi.member_id = u.id 
                       ORDER BY bi.created_at DESC')->fetchAll();


include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">Issues & Returns</h3>
        
        <?php if ($message): ?>
             <!-- Translate message on display if it is still Somali -->
             <?php 
                $displayMessage = $message;
                if ($message == 'Buuggan lama heli karo wakhtigan.') $displayMessage = 'This book is currently unavailable.';
                if ($message == 'Buugga waa la amaahiyay.') $displayMessage = 'Book issued successfully.';
                if ($message == 'Buugga waa la soo celiyay.') $displayMessage = 'Book returned successfully.';
                if ($message == 'Fadlan dooro buug iyo arday.') $displayMessage = 'Please select a book and a member.';
             ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($displayMessage); ?></div>
        <?php endif; ?>

        <!-- Issue Book Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Issue New Book</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="issue">
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Select Book</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">-- Choose Book --</option>
                            <?php foreach ($availableBooks as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title_somali']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Select Member</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">-- Choose Member --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Issue Date</label>
                        <input type="date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Due Date</label>
                        <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" class="form-control" required>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-journal-plus me-2"></i>Issue Book
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Issues List Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Current Issues List</h5>
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
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issues as $i): ?>
                                <tr>
                                    <td><?php echo $i['id']; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($i['title_somali']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <?php echo htmlspecialchars($i['member_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($i['issue_date']); ?></td>
                                    <td>
                                        <?php 
                                            // Highlight overdue
                                            $dueDate = new DateTime($i['due_date']);
                                            $today = new DateTime();
                                            $isOverdue = $dueDate < $today && $i['status'] === 'issued';
                                            $class = $isOverdue ? 'text-danger fw-bold' : '';
                                        ?>
                                        <span class="<?php echo $class; ?>"><?php echo htmlspecialchars($i['due_date']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($i['return_date'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($i['status'] === 'issued'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Issued</span>
                                        <?php elseif ($i['status'] === 'returned'): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis">Returned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($i['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($i['status'] === 'issued'): ?>
                                            <a href="issues.php?return=<?php echo $i['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to return this book?');">
                                                <i class="bi bi-arrow-return-left me-1"></i>Return
                                            </a>
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


