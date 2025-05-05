<?php require VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Edit Category</h1>
        <a href="<?= APP_URL ?>/categories" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Categories
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <?= $this->csrf() ?>

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name"
                                   value="<?= htmlspecialchars($data['name'] ?? $category['name']) ?>"
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" 
                                      name="description"
                                      rows="3"><?= htmlspecialchars($data['description'] ?? $category['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['description'] ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Optional. Provide a brief description of the category.
                            </div>
                        </div>

                        <!-- Parent Category -->
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select class="form-select <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>" 
                                    id="parent_id" 
                                    name="parent_id">
                                <option value="">None (Top Level Category)</option>
                                <?php foreach ($categories as $parent): ?>
                                    <?php if ($parent['id'] !== $category['id']): ?>
                                        <option value="<?= $parent['id'] ?>" 
                                                <?= (isset($data['parent_id']) ? $data['parent_id'] : $category['parent_id']) == $parent['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parent['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['parent_id'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['parent_id'] ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Optional. Select a parent category to make this a subcategory.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Category Info -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Created</h6>
                            <p class="text-muted">
                                <?= date('F j, Y g:i A', strtotime($category['created_at'])) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Last Updated</h6>
                            <p class="text-muted">
                                <?= date('F j, Y g:i A', strtotime($category['updated_at'])) ?>
                            </p>
                        </div>
                    </div>

                    <h6 class="mt-4">Category Path</h6>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <?php 
                            $breadcrumb = [];
                            $current = $category;
                            while ($current) {
                                array_unshift($breadcrumb, $current);
                                $current = isset($current['parent_id']) ? 
                                    array_filter($categories, fn($c) => $c['id'] == $current['parent_id'])[0] ?? null : 
                                    null;
                            }
                            ?>
                            <?php foreach ($breadcrumb as $item): ?>
                                <li class="breadcrumb-item">
                                    <?= htmlspecialchars($item['name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>

                    <?php if ($category['product_count'] > 0): ?>
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This category contains <?= $category['product_count'] ?> products. 
                            Changes will affect product categorization.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Delete Card -->
            <?php if ($category['product_count'] == 0): ?>
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p>Delete this category permanently. This action cannot be undone.</p>
                        <button type="button" 
                                class="btn btn-danger"
                                onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                            <i class="fas fa-trash me-2"></i>Delete Category
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete category <strong id="deleteCategoryName"></strong>?
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form method="post" id="deleteForm">
                    <?= $this->csrf() ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteForm').action = '<?= APP_URL ?>/categories/delete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
