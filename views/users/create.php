<?php require VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Create User</h1>
        <a href="<?= APP_URL ?>/users" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Users
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <?= $this->csrf() ?>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                   id="username" 
                                   name="username"
                                   value="<?= htmlspecialchars($data['username'] ?? '') ?>"
                                   required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['username'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email"
                                   value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
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

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                       id="password" 
                                       name="password"
                                       required
                                       minlength="<?= PASSWORD_MIN_LENGTH ?>">
                                <button class="btn btn-outline-secondary" 
                                        type="button"
                                        onclick="togglePassword('password', 'passwordToggle')">
                                    <i class="fas fa-eye" id="passwordToggle"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['password'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">
                                Password must be at least <?= PASSWORD_MIN_LENGTH ?> characters long.
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" 
                                    id="role" 
                                    name="role"
                                    required>
                                <option value="">Select Role</option>
                                <option value="admin" <?= isset($data['role']) && $data['role'] === 'admin' ? 'selected' : '' ?>>
                                    Administrator
                                </option>
                                <option value="sales" <?= isset($data['role']) && $data['role'] === 'sales' ? 'selected' : '' ?>>
                                    Sales
                                </option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['role'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
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
