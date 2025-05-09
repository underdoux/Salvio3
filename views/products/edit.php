<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Edit Product</h1>

<div>
    <label for="bpom-search">Search BPOM Data (Name or Reg No):</label>
    <input type="text" id="bpom-search" placeholder="Enter product name or registration number">
    <button type="button" id="bpom-search-btn">Search</button>
    <div id="bpom-search-results"></div>
</div>

<form method="post" action="<?= url('product/update/' . $data['id']) ?>">
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['name'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['name']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea name="description" id="description"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="sku">SKU</label>
        <input type="text" name="sku" id="sku" value="<?= htmlspecialchars($data['sku'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="barcode">Barcode</label>
        <input type="text" name="barcode" id="barcode" value="<?= htmlspecialchars($data['barcode'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="category_id">Category</label>
        <select name="category_id" id="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>" <?= (isset($data['category_id']) && $data['category_id'] == $category['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($data['errors']['category_id'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['category_id']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="purchase_price">Purchase Price</label>
        <input type="number" step="0.01" name="purchase_price" id="purchase_price" value="<?= htmlspecialchars($data['purchase_price'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['purchase_price'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['purchase_price']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="selling_price">Selling Price</label>
        <input type="number" step="0.01" name="selling_price" id="selling_price" value="<?= htmlspecialchars($data['selling_price'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['selling_price'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['selling_price']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="stock">Stock</label>
        <input type="number" name="stock" id="stock" value="<?= htmlspecialchars($data['stock'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['stock'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['stock']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="min_stock">Minimum Stock</label>
        <input type="number" name="min_stock" id="min_stock" value="<?= htmlspecialchars($data['min_stock'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['min_stock'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['min_stock']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="active" <?= (isset($data['status']) && $data['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (isset($data['status']) && $data['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Update Product</button>
    <a href="<?= url('product') ?>" class="btn btn-secondary">Cancel</a>
</form>

<script>
document.getElementById('bpom-search-btn').addEventListener('click', function() {
    const query = document.getElementById('bpom-search').value.trim();
    if (!query) return;

    fetch('<?= url('bpom/search') ?>?query=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        const resultsDiv = document.getElementById('bpom-search-results');
        resultsDiv.innerHTML = '';

        if (data.error) {
            resultsDiv.textContent = data.error;
            return;
        }

        if (data.length === 0) {
            resultsDiv.textContent = 'No results found.';
            return;
        }

        const list = document.createElement('ul');
        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.name + ' (Reg No: ' + item.registration_number + ', Category: ' + item.category + ')';
            li.style.cursor = 'pointer';
            li.addEventListener('click', () => {
                fetch('<?= url('bpom/details') ?>/' + encodeURIComponent(item.registration_number), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(resp => resp.json())
                .then(details => {
                    if (details.error) {
                        alert(details.error);
                        return;
                    }
                    // Auto-fill form fields
                    document.getElementById('name').value = details.name || '';
                    // Find category option matching details.category and select it
                    const categorySelect = document.getElementById('category_id');
                    for (let i = 0; i < categorySelect.options.length; i++) {
                        if (categorySelect.options[i].text.toLowerCase() === (details.category || '').toLowerCase()) {
                            categorySelect.selectedIndex = i;
                            break;
                        }
                    }
                    // Optionally fill description or other fields if available
                    document.getElementById('description').value = details.ingredients || '';
                    // Clear search results
                    resultsDiv.innerHTML = '';
                });
            });
            list.appendChild(li);
        });
        resultsDiv.appendChild(list);
    })
    .catch(err => {
        document.getElementById('bpom-search-results').textContent = 'Error fetching data.';
    });
});
</script>

<?php $this->view('layout/footer'); ?>
