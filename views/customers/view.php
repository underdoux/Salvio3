<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Customer Profile -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-initial rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; line-height: 100px;">
                        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($customer['name']) ?></h4>
                    <p class="text-muted mb-3">Customer since <?= formatDate($customer['created_at'], 'F Y') ?></p>
                    
                    <div class="d-grid gap-2 mb-3">
                        <a href="<?= url('customers/edit/' . $customer['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Customer
                        </a>
                        <?php if (empty($customer['sales_history'])): ?>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i> Delete Customer
                            </button>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <!-- Contact Information -->
                    <div class="text-start">
                        <?php if (!empty($customer['email'])): ?>
                            <div class="mb-2">
                                <i class="fas fa-envelope fa-fw text-muted me-2"></i>
                                <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($customer['email']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($customer['phone'])): ?>
                            <div class="mb-2">
                                <i class="fas fa-phone fa-fw text-muted me-2"></i>
                                <a href="tel:<?= htmlspecialchars($customer['phone']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($customer['phone']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($customer['address'])): ?>
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt fa-fw text-muted me-2"></i>
                                <?= nl2br(htmlspecialchars($customer['address'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h3 class="mb-0"><?= number_format(count($customer['sales_history'])) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Total Spent</h6>
                                <h3 class="mb-0"><?= formatCurrency(array_sum(array_column($customer['sales_history'], 'final_amount'))) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Avg. Order Value</h6>
                                <h3 class="mb-0">
                                    <?= formatCurrency(
                                        count($customer['sales_history']) > 0 
                                            ? array_sum(array_column($customer['sales_history'], 'final_amount')) / count($customer['sales_history'])
                                            : 0
                                    ) ?>
                                </h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Last Purchase</h6>
                                <h3 class="mb-0">
                                    <?php
                                    $lastPurchase = !empty($customer['sales_history']) 
                                        ? formatDate(max(array_column($customer['sales_history'], 'created_at')), 'd M Y')
                                        : 'Never';
                                    echo $lastPurchase;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Frequency -->
            <?php if (!empty($purchaseFrequency)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Purchase Frequency</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchaseFrequency as $record): ?>
                                    <tr>
                                        <td><?= formatDate($record['month'], 'M Y') ?></td>
                                        <td class="text-center"><?= number_format($record['order_count']) ?></td>
                                        <td class="text-end"><?= formatCurrency($record['total_amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <!-- Sales History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Purchase History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($customer['sales_history'])): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="h5 text-muted">No purchase history</p>
                            <p class="text-muted">This customer hasn't made any purchases yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Payment</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customer['sales_history'] as $sale): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($sale['invoice_number']) ?></div>
                                                <small class="text-muted">By: <?= htmlspecialchars($sale['sales_person']) ?></small>
                                            </td>
                                            <td><?= formatDate($sale['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= number_format($sale['total_items']) ?> items</span>
                                            </td>
                                            <td>
                                                <span class="text-capitalize"><?= str_replace('_', ' ', $sale['payment_type']) ?></span>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?= formatCurrency($sale['final_amount']) ?>
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
                                                <a href="<?= url('sales/view/' . $sale['id']) ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
                Are you sure you want to delete this customer? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= url('customers/delete/' . $customer['id']) ?>" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
