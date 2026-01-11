<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== ROLE_ADMIN) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$message = '';

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = $_POST['id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role_id   = (int)($_POST['role_id'] ?? 0);
    $status    = $_POST['status'] ?? 'active';
    $password  = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $role_id === 0) {
        $message = 'Fadlan buuxi dhammaan xogta muhiimka ah.';
    } else {
        if ($id) {
            // Update existing user
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, email = :email, role_id = :role_id, status = :status, password_hash = :password_hash WHERE id = :id');
                $stmt->execute([
                    ':full_name'     => $full_name,
                    ':email'         => $email,
                    ':role_id'       => $role_id,
                    ':status'        => $status,
                    ':password_hash' => $hash,
                    ':id'            => $id,
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, email = :email, role_id = :role_id, status = :status WHERE id = :id');
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email'     => $email,
                    ':role_id'   => $role_id,
                    ':status'    => $status,
                    ':id'        => $id,
                ]);
            }
            $message = 'Isticmaalaha waa la cusboonaysiiyay.';
        } else {
            // Insert new user
            if ($password === '') {
                $message = 'Fadlan geli eray sir ah isticmaalaha cusub.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role_id, status) VALUES (:full_name, :email, :password_hash, :role_id, :status)');
                $stmt->execute([
                    ':full_name'     => $full_name,
                    ':email'         => $email,
                    ':password_hash' => $hash,
                    ':role_id'       => $role_id,
                    ':status'        => $status,
                ]);
                $message = 'Isticmaale cusub waa la abuuray.';
            }
        }
    }
}

// Handle delete (soft delete via status)
if (isset($_GET['disable'])) {
    $id = (int)$_GET['disable'];
    $stmt = $pdo->prepare('UPDATE users SET status = "disabled" WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $message = 'Isticmaalaha waa la xannibay.';
}

// Fetch roles and users
$roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
$users = $pdo->query('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC')->fetchAll();



    if (isset($message)) {
        if ($message === 'Fadlan buuxi dhammaan xogta muhiimka ah.') $message = 'Please fill in all required user details.';
        elseif ($message === 'Isticmaalaha waa la cusboonaysiiyay.') $message = 'User updated successfully.';
        elseif ($message === 'Fadlan geli eray sir ah isticmaalaha cusub.') $message = 'Please enter a password for the new user.';
        elseif ($message === 'Isticmaale cusub waa la abuuray.') $message = 'New user created successfully.';
        elseif ($message === 'Isticmaalaha waa la xannibay.') $message = 'User account disabled.';
    }
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row g-0">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <h3 class="mb-4 fw-bold">User Management</h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit User Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Add / Edit User</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="id" id="user_id">
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required placeholder="John Doe">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="john@example.com">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Role</label>
                        <select name="role_id" id="role_id" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted text-uppercase fw-bold">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="New password (leave empty to keep current)">
                        <div class="form-text small">Only fill if you want to change/set password.</div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-person-check me-2"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- User List Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">User List</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td class="fw-medium">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-indigo-100 text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #e0e7ff;">
                                               <span style="font-size: 0.8rem; font-weight: bold;"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></span>
                                            </div>
                                            <?php echo htmlspecialchars($u['full_name']); ?>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge bg-light text-dark border text-uppercase"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick='fillForm(<?php echo json_encode($u); ?>)'>
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <a href="users.php?disable=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to disable this user account?');">
                                                <i class="bi bi-ban me-1"></i>Disable
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
<script>
function fillForm(user) {
    document.getElementById('user_id').value = user.id;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('email').value = user.email;
    document.getElementById('role_id').value = user.role_id;
    document.getElementById('status').value = user.status;
    document.getElementById('password').value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>


