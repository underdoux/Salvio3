<?php require VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Users</h1>
        <a href="<?= APP_URL ?>/users/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add User
        </a>
    </div>

    <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?= Session::getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= Session::getFlash('error') ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <!-- Search Form -->
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search users..."
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= APP_URL ?>/users" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <?php if ($search): ?>
                                        <p class="text-muted mb-0">No users found matching your search.</p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No users have been created yet.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'info' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <span title="<?= date('Y-m-d H:i:s', strtotime($user['last_login'])) ?>">
                                                <?= date('M j, Y', strtotime($user['last_login'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= APP_URL ?>/users/edit/<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] !== Auth::id()): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete"
                                                        onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="<?= APP_URL ?>/users?page=<?= $pagination['page'] - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" 
                                   href="<?= APP_URL ?>/users?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="<?= APP_URL ?>/users?page=<?= $pagination['page'] + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete user <strong id="deleteUsername"></strong>?
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form method="post" id="deleteForm">
                    <?= $this->csrf() ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, username) {
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteForm').action = '<?= APP_URL ?>/users/delete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
