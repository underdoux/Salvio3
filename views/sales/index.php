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
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-shopping-cart fa-fw text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Today's Orders</h6>
                            <h3 class="mb-0"><?= number_format($stats['today_orders']) ?></h3>
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
                            <i class="fas fa-money-bill-wave fa-fw text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Today's Sales</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['today_sales']) ?></h3>
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
                            <i class="fas fa-chart-line fa-fw text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Monthly Revenue</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['monthly_revenue']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <?php
                        $growth = $stats['last_month_revenue'] > 0 
                            ? (($stats['monthly_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100
                            : 0;
                        $growthClass = $growth >= 0 ? 'success' : 'danger';
                        $growthIcon = $growth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                        ?>
                        <div class="flex-shrink-0 rounded-circle bg-<?= $growthClass ?> bg-opacity-10 p-3">
                            <i class="fas <?= $growthIcon ?> fa-fw text-<?= $growthClass ?>"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Monthly Growth</h6>
                            <h3 class="mb-0"><?= number_format(abs($growth), 1) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
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
    <div class="card">
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
                        <thead>
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
