<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Invoice -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Invoice #<?= htmlspecialchars($sale['invoice_number']) ?></h1>
                        <div>
                            <a href="<?= url('sales/invoice/' . $sale['id']) ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </a>
                        </div>
                    </div>

                    <!-- Invoice Header -->
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">From:</h6>
                            <div class="mb-1 fw-bold"><?= APP_NAME ?></div>
                            <div>Sales Person: <?= htmlspecialchars($sale['user_name']) ?></div>
                            <div>Date: <?= formatDate($sale['created_at']) ?></div>
                            <div>Time: <?= formatTime($sale['created_at']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="mb-3">To:</h6>
                            <div class="mb-1 fw-bold"><?= htmlspecialchars($sale['customer_name']) ?></div>
                            <?php if (!empty($sale['customer_email'])): ?>
                                <div><?= htmlspecialchars($sale['customer_email']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_phone'])): ?>
                                <div><?= htmlspecialchars($sale['customer_phone']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_address'])): ?>
                                <div><?= nl2br(htmlspecialchars($sale['customer_address'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sale['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['sku']) ?></small>
                                        </td>
                                        <td class="text-center"><?= number_format($item['quantity']) ?></td>
                                        <td class="text-end"><?= formatCurrency($item['unit_price']) ?></td>
                                        <td class="text-end text-success">
                                            <?php if ($item['discount_amount'] > 0): ?>
                                                -<?= formatCurrency($item['discount_amount']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= formatCurrency($item['total_amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold"><?= formatCurrency($sale['total_amount']) ?></td>
                                </tr>
                                <?php if ($sale['discount_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end fw-bold">Total Discount:</td>
                                        <td class="text-end fw-bold text-success">
                                            -<?= formatCurrency($sale['discount_amount']) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="text-end fw-bold">Final Total:</td>
                                    <td class="text-end fw-bold"><?= formatCurrency($sale['final_amount']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if (!empty($sale['notes'])): ?>
                        <div class="mt-4">
                            <h6>Notes:</h6>
                            <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($sale['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Terms & Conditions</h6>
                    <ol class="mb-0">
                        <li>All prices are in <?= APP_CURRENCY ?></li>
                        <li>Payment is due upon receipt unless other terms are agreed upon</li>
                        <li>Goods sold are not returnable unless defective</li>
                        <li>Prices are subject to change without notice</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Payment Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <div class="fw-bold text-capitalize">
                            <?= str_replace('_', ' ', $sale['payment_type']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <?php
                        $statusClass = match($sale['payment_status']) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            default => 'danger'
                        };
                        ?>
                        <div>
                            <span class="badge bg-<?= $statusClass ?> fs-6">
                                <?= ucfirst($sale['payment_status']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($sale['payment_status'] !== 'paid'): ?>
                        <hr>
                        <form action="<?= url('sales/updatePayment/' . $sale['id']) ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                            <div class="mb-3">
                                <label for="payment_status" class="form-label">Update Payment Status</label>
                                <select class="form-select" name="payment_status" id="payment_status">
                                    <option value="paid">Paid</option>
                                    <option value="partial" <?= $sale['payment_status'] === 'partial' ? 'selected' : '' ?>>
                                        Partially Paid
                                    </option>
                                    <option value="unpaid" <?= $sale['payment_status'] === 'unpaid' ? 'selected' : '' ?>>
                                        Unpaid
                                    </option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('sales/invoice/' . $sale['id']) ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Download Invoice
                        </a>
                        <a href="<?= url('sales/create') ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Sale
                        </a>
                        <a href="<?= url('sales') ?>" class="btn btn-secondary">
                            <i class="fas fa-list"></i> All Sales
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Details</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-initial rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                                <?= strtoupper(substr($sale['customer_name'], 0, 1)) ?>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1"><?= htmlspecialchars($sale['customer_name']) ?></h6>
                            <?php if (!empty($sale['customer_phone'])): ?>
                                <div class="small text-muted">
                                    <i class="fas fa-phone fa-fw"></i> <?= htmlspecialchars($sale['customer_phone']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_email'])): ?>
                                <div class="small text-muted">
                                    <i class="fas fa-envelope fa-fw"></i> <?= htmlspecialchars($sale['customer_email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($sale['customer_address'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <div class="text-muted">
                                <?= nl2br(htmlspecialchars($sale['customer_address'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <a href="<?= url('customers/view/' . $sale['customer_id']) ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user"></i> View Customer Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
