<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Product Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Product Details</h5>
                    <?php if ($this->isAdmin()): ?>
                        <a href="<?= url('products/edit/' . $product['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Product
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Product Image -->
                        <div class="col-md-4 text-center mb-4">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= url('uploads/products/' . $product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="img-fluid rounded">
                            <?php else: ?>
                                <div class="placeholder-image bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-box fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Product Information -->
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            
                            <div class="mb-3">
                                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($product['status']) ?>
                                </span>
                                <?php if ($product['stock'] <= $product['min_stock']): ?>
                                    <span class="badge bg-warning">Low Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>SKU:</strong></p>
                                    <p><?= htmlspecialchars($product['sku'] ?: 'Not set') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Barcode:</strong></p>
                                    <p><?= htmlspecialchars($product['barcode'] ?: 'Not set') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Category:</strong></p>
                                    <p><?= htmlspecialchars($product['category_name'] ?: 'Uncategorized') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Current Stock:</strong></p>
                                    <p class="<?= $product['stock'] <= $product['min_stock'] ? 'text-warning' : '' ?>">
                                        <?= number_format($product['stock']) ?> units
                                        <?php if ($product['stock'] <= $product['min_stock']): ?>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Minimum Stock:</strong></p>
                                    <p><?= number_format($product['min_stock']) ?> units</p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Selling Price:</strong></p>
                                    <p class="text-primary fw-bold"><?= formatCurrency($product['selling_price']) ?></p>
                                </div>
                                <?php if ($this->isAdmin()): ?>
                                    <div class="col-sm-6">
                                        <p class="mb-1"><strong>Purchase Price:</strong></p>
                                        <p><?= formatCurrency($product['purchase_price']) ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-1"><strong>Profit Margin:</strong></p>
                                        <?php
                                        $margin = $product['purchase_price'] > 0 
                                            ? (($product['selling_price'] - $product['purchase_price']) / $product['purchase_price']) * 100 
                                            : 0;
                                        ?>
                                        <p class="<?= $margin > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($margin, 1) ?>%
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($product['description'])): ?>
                                <div class="mt-3">
                                    <h6>Description:</h6>
                                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BPOM Information -->
            <?php if (!empty($product['bpom_id'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">BPOM Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <p class="mb-1"><strong>Registration Number:</strong></p>
                                <p><?= htmlspecialchars($product['bpom_id']) ?></p>
                            </div>
                            <?php if (!empty($product['bpom_data'])): ?>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Manufacturer:</strong></p>
                                    <p><?= htmlspecialchars($product['bpom_data']['manufacturer']) ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Category:</strong></p>
                                    <p><?= htmlspecialchars($product['bpom_data']['category']) ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Valid Until:</strong></p>
                                    <p><?= formatDate($product['bpom_data']['expired_date']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <?php if ($this->isAdmin()): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <!-- Stock Update Form -->
                        <form action="<?= url('products/updateStock/' . $product['id']) ?>" method="POST" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                            <label class="form-label">Update Stock</label>
                            <div class="input-group">
                                <input type="number" name="quantity" class="form-control" placeholder="Enter quantity">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                            <small class="text-muted">Use positive numbers to add stock, negative to remove</small>
                        </form>

                        <div class="d-grid gap-2">
                            <a href="<?= url('products/edit/' . $product['id']) ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit Product
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i> Delete Product
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stock Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="stock-gauge" style="width: 200px; height: 100px;">
                            <!-- Add a gauge chart or visual representation here -->
                            <?php
                            $stockPercentage = $product['min_stock'] > 0 
                                ? min(100, ($product['stock'] / $product['min_stock']) * 100) 
                                : 100;
                            $gaugeColor = match(true) {
                                $stockPercentage <= 25 => 'danger',
                                $stockPercentage <= 50 => 'warning',
                                default => 'success'
                            };
                            ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-<?= $gaugeColor ?>" 
                                     role="progressbar" 
                                     style="width: <?= $stockPercentage ?>%"
                                     aria-valuenow="<?= $stockPercentage ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?= number_format($stockPercentage, 0) ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row text-center g-2">
                        <div class="col-6">
                            <div class="p-3 border rounded">
                                <h6 class="mb-1">Current Stock</h6>
                                <p class="h4 mb-0 <?= $product['stock'] <= $product['min_stock'] ? 'text-warning' : 'text-success' ?>">
                                    <?= number_format($product['stock']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded">
                                <h6 class="mb-1">Minimum Stock</h6>
                                <p class="h4 mb-0"><?= number_format($product['min_stock']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <!-- Add recent sales, stock updates, etc. -->
                    <p class="text-muted text-center">Activity log will be displayed here</p>
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
                Are you sure you want to delete this product? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= url('products/delete/' . $product['id']) ?>" method="POST" style="display: inline;">
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
