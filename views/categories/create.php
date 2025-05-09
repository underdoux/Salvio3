<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Create Category</h1>

<form method="post" action="<?= url('category/store') ?>">
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
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="active" <?= (isset($data['status']) && $data['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (isset($data['status']) && $data['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Create Category</button>
    <a href="<?= url('category') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php $this->view('layout/footer'); ?>
