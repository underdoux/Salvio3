<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Customers</h1>
        <a href="<?= url('customers/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-users fa-fw text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Total Customers</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_customers']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-user-plus fa-fw text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">New This Month</h6>
                            <h3 class="mb-0"><?= number_format($stats['new_this_month']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-shopping-cart fa-fw text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Active Today</h6>
                            <h3 class="mb-0"><?= number_format($stats['active_today']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-receipt fa-fw text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Avg. Order Value</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['avg_order_value']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('customers') ?>" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort by Name</option>
                        <option value="total_orders" <?= $sort === 'total_orders' ? 'selected' : '' ?>>Sort by Orders</option>
                        <option value="total_spent" <?= $sort === 'total_spent' ? 'selected' : '' ?>>Sort by Spending</option>
                        <option value="last_purchase" <?= $sort === 'last_purchase' ? 'selected' : '' ?>>Sort by Last Purchase</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="order" class="form-select">
                        <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
                <?php if ($search || $sort !== 'name' || $order !== 'ASC'): ?>
                    <div class="col-md-1">
                        <a href="<?= url('customers') ?>" class="btn btn-secondary w-100" title="Clear filters">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Customers List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($customers)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No customers found</p>
                    <?php if ($search): ?>
                        <p class="text-muted">Try adjusting your search criteria</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th class="text-center">Total Orders</th>
                                <th class="text-end">Total Spent</th>
                                <th>Last Purchase</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initial rounded-circle bg-primary bg-opacity-10 text-primary me-2">
                                                <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($customer['name']) ?></div>
                                                <?php if (!empty($customer['address'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($customer['address']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($customer['email'])): ?>
                                            <div><i class="fas fa-envelope fa-fw text-muted"></i> <?= htmlspecialchars($customer['email']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($customer['phone'])): ?>
                                            <div><i class="fas fa-phone fa-fw text-muted"></i> <?= htmlspecialchars($customer['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= number_format($customer['total_orders']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold"><?= formatCurrency($customer['total_spent']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_purchase']): ?>
                                            <?= formatDate($customer['last_purchase']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">No purchases yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $customer['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($customer['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= url('customers/view/' . $customer['id']) ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= url('customers/edit/' . $customer['id']) ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit Customer">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($customer['total_orders'] === 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="Delete Customer"
                                                        onclick="confirmDelete(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['last_page'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('customers', ['page' => $pagination['page'] - 1, 'search' => $search, 'sort' => $sort, 'order' => $order]) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('customers', ['page' => $i, 'search' => $search, 'sort' => $sort, 'order' => $order]) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['page'] < $pagination['last_page']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('customers', ['page' => $pagination['page'] + 1, 'search' => $search, 'sort' => $sort, 'order' => $order]) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <span id="deleteCustomerName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCustomerName').textContent = name;
    document.getElementById('deleteForm').action = '<?= url('customers/delete') ?>/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
