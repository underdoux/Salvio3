<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Create User</h1>

<form method="post" action="<?= url('user/store') ?>">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($data['username'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['username'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['username']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['email'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        <?php if (!empty($data['errors']['name'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['name']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="role">Role</label>
        <select name="role" id="role" required>
            <option value="">Select Role</option>
            <option value="admin" <?= (isset($data['role']) && $data['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="sales" <?= (isset($data['role']) && $data['role'] === 'sales') ? 'selected' : '' ?>>Sales</option>
            <option value="user" <?= (isset($data['role']) && $data['role'] === 'user') ? 'selected' : '' ?>>User</option>
        </select>
        <?php if (!empty($data['errors']['role'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['role']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
        <?php if (!empty($data['errors']['password'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['password']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password_confirm">Confirm Password</label>
        <input type="password" name="password_confirm" id="password_confirm" required>
        <?php if (!empty($data['errors']['password_confirm'])): ?>
            <div class="error"><?= htmlspecialchars($data['errors']['password_confirm']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="active" <?= (isset($data['status']) && $data['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (isset($data['status']) && $data['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Create User</button>
    <a href="<?= url('user') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php $this->view('layout/footer'); ?>
