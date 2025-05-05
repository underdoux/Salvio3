<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <p>Enter your new password</p>
            </div>

            <?= $this->getFlash() ?>

            <form action="<?= base_url('auth/updatePassword') ?>" method="POST" class="auth-form">
                <?= $this->csrf() ?>
                <input type="hidden" name="token" value="<?= $token ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               autofocus
                               minlength="<?= PASSWORD_MIN_LENGTH ?>"
                               class="form-control"
                               placeholder="Enter your new password">
                        <button type="button" 
                                class="toggle-password"
                                onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        Password must be at least <?= PASSWORD_MIN_LENGTH ?> characters long
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>"
                               class="form-control"
                               placeholder="Confirm your new password">
                        <button type="button" 
                                class="toggle-password"
                                onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        Reset Password
                    </button>
                </div>

                <div class="form-group text-center">
                    <a href="<?= base_url('auth/login') ?>" class="back-link">
                        Back to Login
                    </a>
                </div>
            </form>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');

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

        // Password validation
        const form = document.querySelector('form');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        form.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html>
