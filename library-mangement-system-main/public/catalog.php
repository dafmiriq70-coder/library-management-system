<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();

$searchTitle = trim($_GET['q'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);

$categories = $pdo->query('SELECT * FROM categories ORDER BY name_somali')->fetchAll();

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

$sql = 'SELECT b.*, c.name_somali AS category_name 
        FROM books b 
        JOIN categories c ON b.category_id = c.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY b.title_somali ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();


include __DIR__ . '/../includes/header.php';
?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">Book Catalog</h3>
        
        <!-- Search Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-5">
                        <label for="search" class="form-label text-muted small text-uppercase fw-bold">Search Title</label>
                        <input type="text" name="q" id="search" value="<?php echo htmlspecialchars($searchTitle); ?>" class="form-control" placeholder="Search by book title (Original)">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label text-muted small text-uppercase fw-bold">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name_somali']); ?> 
                                    <!-- Keeping original category name as it is data -->
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-4">Search Results</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Title (Original)</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Available</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $b): ?>
                                    <tr>
                                        <td class="fw-medium text-dark"><?php echo htmlspecialchars($b['title_somali']); ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No books found matching your criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


