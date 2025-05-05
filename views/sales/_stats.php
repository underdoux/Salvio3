<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-shopping-cart fa-fw"></i>
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
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-money-bill-wave fa-fw"></i>
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
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-chart-line fa-fw"></i>
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
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <?php
                    $growth = $stats['last_month_revenue'] > 0 
                        ? (($stats['monthly_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100
                        : 0;
                    $growthClass = $growth >= 0 ? 'success' : 'danger';
                    $growthIcon = $growth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                    ?>
                    <div class="stats-icon bg-<?= $growthClass ?> bg-opacity-10 text-<?= $growthClass ?>">
                        <i class="fas <?= $growthIcon ?> fa-fw"></i>
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
