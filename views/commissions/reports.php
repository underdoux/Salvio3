<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Commission Reports</h1>
        <a href="<?= url('commissions/export', ['start_date' => $startDate, 'end_date' => $endDate, 'user_id' => $selectedUser]) ?>" 
           class="btn btn-primary" target="_blank">
            <i class="fas fa-download"></i> Export Report
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('commissions/reports') ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="start_date" value="<?= $startDate ?>">
                        <input type="date" class="form-control" name="end_date" value="<?= $endDate ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Sales Person</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">All Sales Staff</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $selectedUser == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
                <?php if ($startDate || $endDate || $selectedUser): ?>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="<?= url('commissions/reports') ?>" class="btn btn-secondary w-100">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Commission Summary -->
    <div class="row g-4 mb-4">
        <?php foreach ($commissions as $commission): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-initial rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                                    <?= strtoupper(substr($commission['user_name'], 0, 1)) ?>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($commission['user_name']) ?></h5>
                                <div class="text-muted small">Sales Person</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Total Sales</div>
                                    <div class="h5 mb-0"><?= number_format($commission['total_sales']) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Total Commission</div>
                                    <div class="h5 mb-0"><?= formatCurrency($commission['total_commission']) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Paid</div>
                                    <div class="h5 mb-0 text-success"><?= formatCurrency($commission['paid_amount']) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Pending</div>
                                    <div class="h5 mb-0 text-warning"><?= formatCurrency($commission['pending_amount']) ?></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row text-center g-2">
                            <div class="col-6">
                                <div class="text-muted small">First Commission</div>
                                <div><?= formatDate($commission['first_commission']) ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Last Commission</div>
                                <div><?= formatDate($commission['last_commission']) ?></div>
                            </div>
                        </div>

                        <?php if ($commission['pending_amount'] > 0): ?>
                            <div class="mt-3">
                                <form action="<?= url('commissions/pay') ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                                    <input type="hidden" name="user_id" value="<?= array_key_first($commission) ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fas fa-check"></i> Pay Pending Commissions
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Performance Charts -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Monthly Performance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="300"></canvas>
                </div>
            </div>

            <!-- Commission Distribution -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Commission Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Top Performers -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Performers</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Sort commissions by total amount
                    usort($commissions, function($a, $b) {
                        return $b['total_commission'] - $a['total_commission'];
                    });
                    ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($commissions, 0, 5) as $commission): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initial rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3">
                                            <?= strtoupper(substr($commission['user_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($commission['user_name']) ?></h6>
                                            <small class="text-muted"><?= number_format($commission['total_sales']) ?> sales</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold"><?= formatCurrency($commission['total_commission']) ?></div>
                                        <small class="text-muted">earned</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Commission Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    $totalCommission = array_sum(array_column($commissions, 'total_commission'));
                    $totalSales = array_sum(array_column($commissions, 'total_sales'));
                    $avgCommission = $totalSales > 0 ? $totalCommission / $totalSales : 0;
                    ?>
                    <div class="mb-3">
                        <label class="form-label">Average Commission per Sale</label>
                        <div class="h4 mb-0"><?= formatCurrency($avgCommission) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Commissions Paid</label>
                        <div class="h4 mb-0 text-success">
                            <?= formatCurrency(array_sum(array_column($commissions, 'paid_amount'))) ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Total Commissions Pending</label>
                        <div class="h4 mb-0 text-warning">
                            <?= formatCurrency(array_sum(array_column($commissions, 'pending_amount'))) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize select2
$(document).ready(function() {
    $('#user_id').select2({
        placeholder: 'Select Sales Person',
        allowClear: true
    });
});

// Monthly Performance Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Commissions',
            data: [/* Add monthly data here */],
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Commission Distribution Chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($commissions, 'user_name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($commissions, 'total_commission')) ?>,
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 206, 86)',
                'rgb(75, 192, 192)',
                'rgb(153, 102, 255)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
