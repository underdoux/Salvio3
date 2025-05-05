<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Commission Rates Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Commission Rates</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rates)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-percentage fa-3x text-muted mb-3"></i>
                            <p class="h5 text-muted">No commission rates defined</p>
                            <p class="text-muted">Add rates using the form on the right</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Scope</th>
                                        <th>Rate</th>
                                        <th>Sales Person</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rates as $rate): ?>
                                        <tr>
                                            <td>
                                                <?php if ($rate['product_id']): ?>
                                                    <div>
                                                        <i class="fas fa-box fa-fw text-primary"></i>
                                                        <strong>Product:</strong> <?= htmlspecialchars($rate['product_name']) ?>
                                                    </div>
                                                <?php elseif ($rate['category_id']): ?>
                                                    <div>
                                                        <i class="fas fa-folder fa-fw text-warning"></i>
                                                        <strong>Category:</strong> <?= htmlspecialchars($rate['category_name']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div>
                                                        <i class="fas fa-globe fa-fw text-success"></i>
                                                        <strong>Global Rate</strong>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= number_format($rate['rate'], 1) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($rate['user_name']): ?>
                                                    <?= htmlspecialchars($rate['user_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">All Sales Staff</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="<?= url('commissions/deleteRate/' . $rate['id']) ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this rate?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rate Priority Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rate Priority</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-box fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>1. Product-Specific</h6>
                                    <p class="text-muted mb-0">Highest priority, applies to specific products</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-folder fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>2. Category-Based</h6>
                                    <p class="text-muted mb-0">Applied when no product-specific rate exists</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-globe fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>3. Global Rate</h6>
                                    <p class="text-muted mb-0">Default rate when no other rates apply</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Add Rate Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Commission Rate</h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('commissions/storeRate') ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="mb-3">
                            <label for="user_id" class="form-label">Sales Person</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">All Sales Staff</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Leave blank for global rate</small>
                        </div>

                        <div class="mb-3">
                            <label for="rate" class="form-label">Commission Rate (%)</label>
                            <input type="number" class="form-control" id="rate" name="rate" 
                                   min="0" max="100" step="0.1" required>
                            <small class="text-muted">Enter percentage between 0 and 100</small>
                        </div>

                        <div class="mb-3">
                            <label for="scope" class="form-label">Scope</label>
                            <select class="form-select" id="scope" onchange="toggleScope(this.value)">
                                <option value="global">Global</option>
                                <option value="category">Category</option>
                                <option value="product">Product</option>
                            </select>
                        </div>

                        <div id="categoryScope" style="display: none;" class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <!-- Categories will be loaded via AJAX -->
                            </select>
                        </div>

                        <div id="productScope" style="display: none;" class="mb-3">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id">
                                <option value="">Select Product</option>
                                <!-- Products will be loaded via AJAX -->
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Rate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Set specific rates for top-selling products</li>
                        <li class="mb-2">Use category rates for product groups</li>
                        <li class="mb-2">Global rates serve as a fallback</li>
                        <li class="mb-2">Individual rates override category rates</li>
                        <li>Consider seasonal adjustments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize select2 for better dropdown experience
$(document).ready(function() {
    $('#user_id').select2({
        placeholder: 'Select Sales Person',
        allowClear: true
    });

    $('#category_id').select2({
        placeholder: 'Select Category',
        allowClear: true,
        ajax: {
            url: '<?= url('categories/search') ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name
                        };
                    })
                };
            }
        }
    });

    $('#product_id').select2({
        placeholder: 'Select Product',
        allowClear: true,
        ajax: {
            url: '<?= url('products/search') ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name + ' (' + item.sku + ')'
                        };
                    })
                };
            }
        }
    });
});

function toggleScope(scope) {
    document.getElementById('categoryScope').style.display = scope === 'category' ? 'block' : 'none';
    document.getElementById('productScope').style.display = scope === 'product' ? 'block' : 'none';
    
    // Clear hidden inputs when scope changes
    if (scope === 'global') {
        document.getElementById('category_id').value = '';
        document.getElementById('product_id').value = '';
    }
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
