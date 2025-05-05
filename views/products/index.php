<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Products</h1>
        <?php if ($this->isAdmin()): ?>
            <a href="<?= url('products/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('products') ?>" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $selectedCategory == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <?php if ($search || $selectedCategory): ?>
                    <div class="col-md-2">
                        <a href="<?= url('products') ?>" class="btn btn-secondary w-100">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Products List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No products found</p>
                    <?php if ($search || $selectedCategory): ?>
                        <p class="text-muted">Try adjusting your search or filters</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="<?= url('uploads/products/' . $product['image']) ?>" 
                                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                                     class="rounded me-2" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                <?php if (!empty($product['barcode'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($product['barcode']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($product['sku']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                    <td>
                                        <?php
                                        $stockClass = match($product['stock_status']) {
                                            'out_of_stock' => 'text-danger',
                                            'low_stock' => 'text-warning',
                                            default => 'text-success'
                                        };
                                        ?>
                                        <span class="<?= $stockClass ?>">
                                            <?= number_format($product['stock']) ?>
                                            <?php if ($product['stock_status'] === 'low_stock'): ?>
                                                <i class="fas fa-exclamation-triangle ms-1" title="Low Stock"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?= formatCurrency($product['selling_price']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= url('products/view/' . $product['id']) ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($this->isAdmin()): ?>
                                                <a href="<?= url('products/edit/' . $product['id']) ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Edit Product">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="Delete Product"
                                                        onclick="confirmDelete(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['lastPage'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('products', ['page' => $pagination['page'] - 1, 'search' => $search, 'category' => $selectedCategory]) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['lastPage']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('products', ['page' => $i, 'search' => $search, 'category' => $selectedCategory]) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['page'] < $pagination['lastPage']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('products', ['page' => $pagination['page'] + 1, 'search' => $search, 'category' => $selectedCategory]) ?>">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <span id="deleteProductName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteForm').action = '<?= url('products/delete') ?>/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
