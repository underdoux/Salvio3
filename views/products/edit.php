<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Product: <?= htmlspecialchars($product['name']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('products/update/' . $product['id']) ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($product['sku']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="barcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barcode" name="barcode" value="<?= htmlspecialchars($product['barcode']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <!-- Pricing & Stock -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="purchase_price" class="form-label">Purchase Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= APP_CURRENCY ?></span>
                                    <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" value="<?= $product['purchase_price'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="selling_price" class="form-label">Selling Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= APP_CURRENCY ?></span>
                                    <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" value="<?= $product['selling_price'] ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock" class="form-label">Current Stock *</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="<?= $product['stock'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="min_stock" class="form-label">Minimum Stock Level *</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" value="<?= $product['min_stock'] ?>" required>
                            </div>
                        </div>

                        <!-- BPOM Information -->
                        <div class="mb-3">
                            <label for="bpom_id" class="form-label">BPOM Registration Number</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="bpom_id" name="bpom_id" value="<?= htmlspecialchars($product['bpom_id']) ?>">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchBPOM()">
                                    <i class="fas fa-search"></i> Search BPOM
                                </button>
                            </div>
                            <small class="text-muted">Enter BPOM registration number or search in BPOM database</small>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-3">
                            <?php if (!empty($product['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?= url('uploads/products/' . $product['image']) ?>" 
                                         alt="Current product image" 
                                         class="img-thumbnail" 
                                         style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <label for="image" class="form-label">Update Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">Maximum file size: 2MB. Supported formats: JPG, PNG</small>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <a href="<?= url('products') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- BPOM Search Results -->
        <div class="col-lg-4">
            <div class="card mb-4" id="bpomResults" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">BPOM Search Results</h5>
                </div>
                <div class="card-body">
                    <div id="bpomLoading" style="display: none;">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Searching BPOM database...</p>
                        </div>
                    </div>
                    <div id="bpomData"></div>
                </div>
            </div>

            <!-- Stock History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock History</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Quick Stock Update</label>
                        <form action="<?= url('products/updateStock/' . $product['id']) ?>" method="POST" class="input-group">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="number" name="quantity" class="form-control" placeholder="Enter quantity">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                        <small class="text-muted">Use positive numbers to add stock, negative to remove</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function searchBPOM() {
    const bpomId = document.getElementById('bpom_id').value.trim();
    if (!bpomId) {
        alert('Please enter a BPOM registration number');
        return;
    }

    const resultsDiv = document.getElementById('bpomResults');
    const loadingDiv = document.getElementById('bpomLoading');
    const dataDiv = document.getElementById('bpomData');

    resultsDiv.style.display = 'block';
    loadingDiv.style.display = 'block';
    dataDiv.innerHTML = '';

    try {
        const response = await fetch(`<?= url('bpom/search') ?>?id=${encodeURIComponent(bpomId)}`);
        const data = await response.json();

        if (data.success) {
            dataDiv.innerHTML = `
                <div class="alert alert-success">
                    <h6>Product Found</h6>
                    <p class="mb-1"><strong>Name:</strong> ${data.data.product_name}</p>
                    <p class="mb-1"><strong>Registration:</strong> ${data.data.registration_number}</p>
                    <p class="mb-1"><strong>Manufacturer:</strong> ${data.data.manufacturer}</p>
                    <p class="mb-1"><strong>Category:</strong> ${data.data.category}</p>
                    <p class="mb-0"><strong>Valid Until:</strong> ${data.data.expired_date}</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm w-100" onclick="applyBPOMData(${JSON.stringify(data.data)})">
                    Use This Data
                </button>
            `;
        } else {
            dataDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No results found for this BPOM number
                </div>
            `;
        }
    } catch (error) {
        dataDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error searching BPOM database
            </div>
        `;
    } finally {
        loadingDiv.style.display = 'none';
    }
}

function applyBPOMData(data) {
    document.getElementById('name').value = data.product_name;
    document.getElementById('bpom_id').value = data.registration_number;
    // You can add more fields to auto-fill as needed
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
