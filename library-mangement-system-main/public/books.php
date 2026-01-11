<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], [ROLE_ADMIN, ROLE_LIBRARIAN], true)) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$message = '';

// Handle add/edit book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = $_POST['id'] ?? '';
    $title_somali   = trim($_POST['title_somali'] ?? '');
    $author_somali  = trim($_POST['author_somali'] ?? '');
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $isbn           = trim($_POST['isbn'] ?? '');
    $total_copies   = (int)($_POST['total_copies'] ?? 1);
    $available_copies = (int)($_POST['available_copies'] ?? $total_copies);
    $status         = $_POST['status'] ?? 'available';

    if ($title_somali === '' || $author_somali === '' || $category_id === 0) {
        $message = 'Fadlan buuxi xogta buugga muhiimka ah.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE books SET title_somali = :title_somali, author_somali = :author_somali, category_id = :category_id, isbn = :isbn, total_copies = :total_copies, available_copies = :available_copies, status = :status WHERE id = :id');
            $stmt->execute([
                ':title_somali'     => $title_somali,
                ':author_somali'    => $author_somali,
                ':category_id'      => $category_id,
                ':isbn'             => $isbn,
                ':total_copies'     => $total_copies,
                ':available_copies' => $available_copies,
                ':status'           => $status,
                ':id'               => $id,
            ]);
            $message = 'Buugga waa la cusboonaysiiyay.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO books (title_somali, author_somali, category_id, isbn, total_copies, available_copies, status) VALUES (:title_somali, :author_somali, :category_id, :isbn, :total_copies, :available_copies, :status)');
            $stmt->execute([
                ':title_somali'     => $title_somali,
                ':author_somali'    => $author_somali,
                ':category_id'      => $category_id,
                ':isbn'             => $isbn,
                ':total_copies'     => $total_copies,
                ':available_copies' => $available_copies,
                ':status'           => $status,
            ]);
            $message = 'Buug cusub waa la diiwaan geliyay.';
        }
    }
}

// Fetch categories
$categories = $pdo->query('SELECT * FROM categories ORDER BY name_somali')->fetchAll();

// Filters
$searchTitle = trim($_GET['q'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($searchTitle !== '') {
    $where[] = 'b.title_somali LIKE :title';
    $params[':title'] = '%' . $searchTitle . '%';
}
if ($categoryFilter) {
    $where[] = 'b.category_id = :category';
    $params[':category'] = $categoryFilter;
}
if ($statusFilter !== '') {
    $where[] = 'b.status = :status';
    $params[':status'] = $statusFilter;
}

$sql = 'SELECT b.*, c.name_somali AS category_name 
        FROM books b 
        JOIN categories c ON b.category_id = c.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY b.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();



    if (isset($message)) {
        if ($message === 'Fadlan buuxi xogta buugga muhiimka ah.') $message = 'Please fill in all required book details.';
        elseif ($message === 'Buugga waa la cusboonaysiiyay.') $message = 'Book updated successfully.';
        elseif ($message === 'Buug cusub waa la diiwaan geliyay.') $message = 'New book registered successfully.';
    }
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">Book Management</h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Book Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Add / Edit Book</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="id" id="book_id">
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Book Title (Original)</label>
                        <input type="text" name="title_somali" id="title_somali" class="form-control" required placeholder="e.g. Taariikhda Soomaaliya">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Author Name</label>
                        <input type="text" name="author_somali" id="author_somali" class="form-control" required placeholder="e.g. Prof. Axmed Cali">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name_somali']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">ISBN</label>
                        <input type="text" name="isbn" id="isbn" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Total Copies</label>
                        <input type="number" name="total_copies" id="total_copies" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Available</label>
                        <input type="number" name="available_copies" id="available_copies" class="form-control" min="0" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Book List Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Book List</h5>
                <form method="get" class="row g-2 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchTitle); ?>" class="form-control" placeholder="Search by title...">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name_somali']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Any Status</option>
                            <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo $statusFilter === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-filter me-1"></i>Filter
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Title (Original)</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Available</th>
                                <th class="text-center">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $b): ?>
                                <tr>
                                    <td><?php echo $b['id']; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($b['title_somali']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($b['author_somali']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b['category_name']); ?></span></td>
                                    <td class="text-center"><?php echo $b['total_copies']; ?></td>
                                    <td class="text-center">
                                        <?php if ($b['available_copies'] > 0): ?>
                                            <span class="text-success fw-bold"><?php echo $b['available_copies']; ?></span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($b['status'] === 'available' && $b['available_copies'] > 0): ?>
                                            <span class="badge bg-success-subtle text-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick='fillBookForm(<?php echo json_encode($b); ?>)'>
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
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
<script>
function fillBookForm(book) {
    document.getElementById('book_id').value = book.id;
    document.getElementById('title_somali').value = book.title_somali;
    document.getElementById('author_somali').value = book.author_somali;
    document.getElementById('category_id').value = book.category_id;
    document.getElementById('isbn').value = book.isbn;
    document.getElementById('total_copies').value = book.total_copies;
    document.getElementById('available_copies').value = book.available_copies;
    document.getElementById('status').value = book.status;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>


