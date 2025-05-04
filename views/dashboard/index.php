<?php
// Format numbers for display
$formatNumber = fn($num) => number_format($num, 0, '.', ',');
$formatCurrency = fn($amount) => $this->formatCurrency($amount);
$formatPercent = fn($num) => number_format($num, 1) . '%';
?>

<!-- Dashboard Overview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Dashboard Overview</h1>
                <p class="text-muted mb-0">Welcome back, <?= Auth::name() ?>!</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light" data-bs-toggle="tooltip" title="Refresh Data">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <!-- Sales Today -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="content-card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="card-title mb-1">Sales Today</h6>
                        <h3 class="mb-0"><?= $formatCurrency($todaySales) ?></h3>
                        <small class="text-muted"><?= $formatNumber($todayOrders) ?> orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Products -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="content-card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="card-title mb-1">Total Products</h6>
                        <h3 class="mb-0"><?= $formatNumber($totalProducts) ?></h3>
                        <small class="text-muted"><?= $formatNumber($lowStock) ?> low stock</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Customers -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="content-card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="card-title mb-1">Total Customers</h6>
                        <h3 class="mb-0"><?= $formatNumber($totalCustomers) ?></h3>
                        <small class="text-muted"><?= $formatNumber($newCustomers) ?> new this month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="content-card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="card-title mb-1">Monthly Revenue</h6>
                        <h3 class="mb-0"><?= $formatCurrency($monthlyRevenue) ?></h3>
                        <?php if (isset($revenueChange)): ?>
                            <small class="<?= $revenueChange >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-<?= $revenueChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= $formatPercent(abs($revenueChange)) ?> from last month
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales & Low Stock -->
<div class="row g-4">
    <!-- Recent Sales -->
    <div class="col-12 col-xl-8">
        <div class="content-card h-100">
            <div class="card-header border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Sales</h5>
                    <a href="<?= APP_URL ?>/sales" class="btn btn-primary btn-sm">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentSales)): ?>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="<?= APP_URL ?>/sales/view/<?= $sale['id'] ?>" 
                                               class="text-decoration-none">
                                                <?= $sale['invoice_number'] ?>
                                            </a>
                                        </td>
                                        <td><?= $sale['customer_name'] ?? 'Walk-in Customer' ?></td>
                                        <td><?= $formatCurrency($sale['final_amount']) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match($sale['payment_status']) {
                                                'paid' => 'success',
                                                'partial' => 'warning',
                                                'unpaid' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?>">
                                                <?= ucfirst($sale['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-3"><?= $this->formatDate($sale['created_at'], 'd M Y') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No recent sales</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="col-12 col-xl-4">
        <div class="content-card h-100">
            <div class="card-header border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Alert</h5>
                    <a href="<?= APP_URL ?>/products?filter=low_stock" class="btn btn-primary btn-sm">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($lowStockProducts)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="list-group-item border-0 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= $product['name'] ?></h6>
                                        <small class="text-muted">SKU: <?= $product['sku'] ?></small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-1"><?= $formatNumber($product['stock']) ?> units</h6>
                                        <small class="text-danger">
                                            Below <?= $formatNumber($product['min_stock']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No low stock products</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
