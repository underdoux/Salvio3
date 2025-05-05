<?php $this->section('css') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
<?php $this->endSection() ?>

<div class="dashboard">
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="dateRangeForm" class="form-inline">
                <div class="form-group mr-3">
                    <label for="start_date" class="mr-2">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" 
                           class="form-control" value="<?= $startDate ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="end_date" class="mr-2">End Date:</label>
                    <input type="date" id="end_date" name="end_date" 
                           class="form-control" value="<?= $endDate ?>">
                </div>
                <button type="submit" class="btn btn-primary">Apply</button>
                <div class="ml-auto">
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            Export
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?= base_url("dashboard/export?format=pdf&start_date={$startDate}&end_date={$endDate}") ?>" 
                               class="dropdown-item">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </a>
                            <a href="<?= base_url("dashboard/export?format=excel&start_date={$startDate}&end_date={$endDate}") ?>" 
                               class="dropdown-item">
                                <i class="fas fa-file-excel"></i> Export as Excel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5 class="stats-title">Total Sales</h5>
                    <h3 class="stats-number">
                        <?= format_currency($salesStats['total_sales']) ?>
                    </h3>
                    <p class="stats-change <?= $salesStats['sales_growth'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="fas fa-<?= $salesStats['sales_growth'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs($salesStats['sales_growth']) ?>% from previous period
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="stats-title">New Customers</h5>
                    <h3 class="stats-number">
                        <?= $customerStats['new_customers'] ?>
                    </h3>
                    <p class="stats-change <?= $customerStats['customer_growth'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="fas fa-<?= $customerStats['customer_growth'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs($customerStats['customer_growth']) ?>% from previous period
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-box"></i>
                    </div>
                    <h5 class="stats-title">Products Sold</h5>
                    <h3 class="stats-number">
                        <?= number_format($salesStats['total_items']) ?>
                    </h3>
                    <p class="stats-change <?= $salesStats['items_growth'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="fas fa-<?= $salesStats['items_growth'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs($salesStats['items_growth']) ?>% from previous period
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <h5 class="stats-title">Average Order</h5>
                    <h3 class="stats-number">
                        <?= format_currency($salesStats['average_order']) ?>
                    </h3>
                    <p class="stats-change <?= $salesStats['average_growth'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="fas fa-<?= $salesStats['average_growth'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs($salesStats['average_growth']) ?>% from previous period
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Sales Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products and Recent Sales -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?= $this->e($product['name']) ?></td>
                                    <td><?= number_format($product['quantity']) ?></td>
                                    <td><?= format_currency($product['total_amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('sales/view/' . $sale['id']) ?>">
                                            <?= $this->e($sale['invoice_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= $this->e($sale['customer_name']) ?></td>
                                    <td><?= format_currency($sale['total_amount']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $sale['status_color'] ?>">
                                            <?= $this->e($sale['status']) ?>
                                        </span>
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

    <!-- Low Stock and Notifications -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Low Stock Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td><?= $this->e($product['name']) ?></td>
                                    <td><?= number_format($product['stock']) ?></td>
                                    <td><?= number_format($product['min_stock']) ?></td>
                                    <td>
                                        <span class="badge badge-danger">Low Stock</span>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-center">No new notifications</p>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <div class="notification-icon bg-<?= $notification['type'] ?>">
                                        <i class="fas fa-<?= $notification['icon'] ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p><?= $this->e($notification['message']) ?></p>
                                        <?php if (isset($notification['link'])): ?>
                                            <a href="<?= base_url($notification['link']) ?>" class="btn btn-sm btn-link">
                                                View Details
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->section('js') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($salesChart['labels']) ?>,
        datasets: [{
            label: 'Sales',
            data: <?= json_encode($salesChart['values']) ?>,
            borderColor: '#007bff',
            tension: 0.1,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => '<?= DEFAULT_CURRENCY ?> ' + value.toLocaleString()
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: context => '<?= DEFAULT_CURRENCY ?> ' + context.parsed.y.toLocaleString()
                }
            }
        }
    }
});

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($paymentStats, 'method')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($paymentStats, 'amount')) ?>,
            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: context => {
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${context.label}: ${percentage}% (${DEFAULT_CURRENCY} ${value.toLocaleString()})`;
                    }
                }
            }
        }
    }
});

// Date range form submission
document.getElementById('dateRangeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.location.href = `<?= base_url('dashboard') ?>?start_date=${startDate}&end_date=${endDate}`;
});
</script>
<?php $this->endSection() ?>
