<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Create Customer</h1>

<form method="post" action="<?= url('customer/store') ?>">
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['name'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['name']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>">
        <?php if (!empty($data['errors']['email'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">
        <?php if (!empty($data['errors']['phone'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['phone']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="address">Address</label>
        <textarea name="address" id="address"><?= htmlspecialchars($data['address'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="active" <?= (isset($data['status']) && $data['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (isset($data['status']) && $data['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Create Customer</button>
    <a href="<?= url('customer') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php $this->view('layout/footer'); ?>
