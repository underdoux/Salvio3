<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Categories</h1>
        <a href="<?= APP_URL ?>/categories/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Category
        </a>
    </div>

    <div class="row">
        <!-- Category Tree -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Structure</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tree)): ?>
                        <p class="text-muted mb-0">No categories have been created yet.</p>
                    <?php else: ?>
                        <div class="category-tree">
                            <?php foreach ($tree as $item): ?>
                                <div class="category-item" style="padding-left: <?= $item['level'] * 20 ?>px">
                                    <a href="<?= APP_URL ?>/categories/view/<?= $item['id'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Parent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <p class="text-muted mb-0">No categories have been created yet.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/categories/view/<?= $category['id'] ?>" 
                                                   class="text-decoration-none">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($category['description']): ?>
                                                    <?= htmlspecialchars(substr($category['description'], 0, 50)) ?>
                                                    <?= strlen($category['description']) > 50 ? '...' : '' ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= $category['product_count'] ?> products
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($category['parent_id']): ?>
                                                    <?php foreach ($categories as $parent): ?>
                                                        <?php if ($parent['id'] === $category['parent_id']): ?>
                                                            <a href="<?= APP_URL ?>/categories/view/<?= $parent['id'] ?>" 
                                                               class="text-decoration-none">
                                                                <?= htmlspecialchars($parent['name']) ?>
                                                            </a>
                                                            <?php break; ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?= APP_URL ?>/categories/edit/<?= $category['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($category['product_count'] == 0): ?>
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="Delete"
                                                                onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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

<style>
.category-tree {
    max-height: 400px;
    overflow-y: auto;
}
.category-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.category-item:last-child {
    border-bottom: none;
}
</style>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteForm').action = '<?= APP_URL ?>/categories/delete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
