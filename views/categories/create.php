<?php require VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Create Category</h1>
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
                                   value="<?= htmlspecialchars($data['name'] ?? '') ?>"
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
                                      rows="3"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
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
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                            <?= isset($data['parent_id']) && $data['parent_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['parent_id'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['parent_id'] ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Optional. Select a parent category to create a subcategory.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Guidelines</h5>
                </div>
                <div class="card-body">
                    <h6>Tips for Creating Categories:</h6>
                    <ul class="mb-4">
                        <li>Use clear, descriptive names that accurately represent the products within.</li>
                        <li>Keep category names concise but informative.</li>
                        <li>Use proper capitalization and avoid special characters.</li>
                        <li>Consider the logical hierarchy when creating subcategories.</li>
                        <li>Ensure categories are distinct and don't overlap in meaning.</li>
                    </ul>

                    <h6>Category Structure Example:</h6>
                    <pre class="bg-light p-3 rounded">
Medicines
├── Prescription Drugs
│   ├── Antibiotics
│   └── Pain Relief
├── Over-the-Counter
│   ├── Cold & Flu
│   └── Digestive Health
└── Medical Supplies
    ├── First Aid
    └── Personal Care</pre>

                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Categories help organize products and make them easier to find. A well-structured category system improves navigation and user experience.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
