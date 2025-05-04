<?php require_once 'views/layout/header.php'; ?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1><?= APP_NAME ?></h1>
            <p>Please login to continue</p>
        </div>

        <form action="<?= BASE_URL ?>/auth/login" method="POST" class="auth-form">
            <?php if(isset($data['errors']['login'])): ?>
                <div class="alert alert-danger">
                    <?= $data['errors']['login'] ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= $data['username'] ?? '' ?>"
                           class="<?= isset($data['errors']['username']) ? 'is-invalid' : '' ?>"
                           placeholder="Enter your username"
                           autocomplete="username">
                </div>
                <?php if(isset($data['errors']['username'])): ?>
                    <div class="invalid-feedback">
                        <?= $data['errors']['username'] ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password"
                           class="<?= isset($data['errors']['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Enter your password"
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if(isset($data['errors']['password'])): ?>
                    <div class="invalid-feedback">
                        <?= $data['errors']['password'] ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </div>

            <div class="auth-links">
                <a href="<?= BASE_URL ?>/auth/forgot" class="forgot-password">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once 'views/layout/footer.php'; ?>
