<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Commissions</h1>
        <?php if ($this->isAdmin()): ?>
            <a href="<?= url('commissions/rates') ?>" class="btn btn-primary">
                <i class="fas fa-cog"></i> Manage Rates
            </a>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-chart-line fa-fw text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Total Commissions</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['paid_amount'] + $stats['pending_amount']) ?></h3>
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
                            <i class="fas fa-check-circle fa-fw text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Paid Commissions</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['paid_amount']) ?></h3>
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
                            <i class="fas fa-clock fa-fw text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Pending Commissions</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['pending_amount']) ?></h3>
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
                            <h6 class="card-title mb-1">Total Sales</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_commissions']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Commission History -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Commission History</h5>
                    <form class="row g-2 align-items-center">
                        <div class="col-auto">
                            <input type="date" class="form-control form-control-sm" name="start_date" value="<?= $startDate ?>">
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control form-control-sm" name="end_date" value="<?= $endDate ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="h5 text-muted">No commission history found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th class="text-end">Sale Amount</th>
                                        <th class="text-end">Commission</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= url('sales/view/' . $record['sale_id']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($record['invoice_number']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($record['customer_name']) ?></td>
                                            <td class="text-end"><?= formatCurrency($record['sale_amount']) ?></td>
                                            <td class="text-end"><?= formatCurrency($record['amount']) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($record['status']) {
                                                    'paid' => 'success',
                                                    default => 'warning'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($record['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($record['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commission Rates -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Your Commission Rates</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rates)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No commission rates defined</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Scope</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rates as $rate): ?>
                                        <tr>
                                            <td>
                                                <?php if ($rate['product_id']): ?>
                                                    <i class="fas fa-box fa-fw text-primary"></i>
                                                    <?= htmlspecialchars($rate['product_name']) ?>
                                                <?php elseif ($rate['category_id']): ?>
                                                    <i class="fas fa-folder fa-fw text-warning"></i>
                                                    <?= htmlspecialchars($rate['category_name']) ?>
                                                <?php else: ?>
                                                    <i class="fas fa-globe fa-fw text-success"></i>
                                                    Global Rate
                                                <?php endif; ?>
                                            </td>
                                            <td><?= number_format($rate['rate'], 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Pending Commissions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pending Commissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingCommissions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="text-muted mb-0">No pending commissions</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th class="text-end">Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingCommissions as $commission): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= url('sales/view/' . $commission['sale_id']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($commission['invoice_number']) ?>
                                                </a>
                                            </td>
                                            <td class="text-end"><?= formatCurrency($commission['amount']) ?></td>
                                            <td><?= formatDate($commission['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td class="text-end">
                                            <strong><?= formatCurrency(array_sum(array_column($pendingCommissions, 'amount'))) ?></strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($this->isAdmin()): ?>
                            <div class="mt-3">
                                <form action="<?= url('commissions/pay') ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                                    <input type="hidden" name="user_id" value="<?= $this->getUserId() ?>">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check"></i> Mark All as Paid
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Last Commission</label>
                        <div class="fw-bold">
                            <?= $stats['last_commission'] ? formatDate($stats['last_commission']) : 'Never' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Payment</label>
                        <div class="fw-bold">
                            <?= $stats['last_payment'] ? formatDate($stats['last_payment']) : 'Never' ?>
                        </div>
                    </div>
                    <?php if ($this->isAdmin()): ?>
                        <hr>
                        <div class="d-grid">
                            <a href="<?= url('commissions/reports') ?>" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
