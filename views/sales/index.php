<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Sales</h1>
        <a href="<?= url('sales/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Sale
        </a>
    </div>

    <!-- Statistics Cards -->
    <?php require_once VIEW_PATH . '/sales/_stats.php'; ?>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= url('sales') ?>" class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search invoices..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Payment Status</option>
                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="date_range" class="form-control" placeholder="Date Range" value="<?= htmlspecialchars($dateRange) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
                <?php if ($search || $status || $dateRange): ?>
                    <div class="col-md-1">
                        <a href="<?= url('sales') ?>" class="btn btn-secondary w-100" title="Clear filters">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Sales List -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No sales found</p>
                    <?php if ($search || $status || $dateRange): ?>
                        <p class="text-muted">Try adjusting your filters</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($sale['invoice_number']) ?></div>
                                        <small class="text-muted">By: <?= htmlspecialchars($sale['user_name']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= number_format($sale['total_items']) ?> items</span>
                                    </td>
                                    <td>
                                        <span class="text-capitalize"><?= str_replace('_', ' ', $sale['payment_type']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= formatCurrency($sale['final_amount']) ?>
                                        <?php if ($sale['discount_amount'] > 0): ?>
                                            <br>
                                            <small class="text-success">
                                                -<?= formatCurrency($sale['discount_amount']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($sale['payment_status']) {
                                            'paid' => 'success',
                                            'partial' => 'warning',
                                            default => 'danger'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($sale['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= formatDate($sale['created_at']) ?>
                                        <br>
                                        <small class="text-muted"><?= formatTime($sale['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= url('sales/view/' . $sale['id']) ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= url('sales/invoice/' . $sale['id']) ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Download Invoice"
                                               target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
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
                                    <a class="page-link" href="<?= url('sales', ['page' => $pagination['page'] - 1, 'search' => $search, 'status' => $status, 'date_range' => $dateRange]) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('sales', ['page' => $i, 'search' => $search, 'status' => $status, 'date_range' => $dateRange]) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['page'] < $pagination['last_page']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('sales', ['page' => $pagination['page'] + 1, 'search' => $search, 'status' => $status, 'date_range' => $dateRange]) ?>">
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

<script>
// Initialize date range picker
document.addEventListener('DOMContentLoaded', function() {
    const dateRange = document.querySelector('input[name="date_range"]');
    if (dateRange) {
        new DateRangePicker(dateRange, {
            format: 'yyyy-mm-dd',
            maxDate: new Date(),
            autoclose: true,
            todayHighlight: true
        });
    }
});
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
