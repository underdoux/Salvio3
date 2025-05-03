<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Reset Password - Salvio POS' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/theme.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
        }

        .reset-container {
            width: 100%;
            max-width: 420px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .reset-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
        }

        .reset-form {
            padding: 0 2rem 2rem;
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .copyright {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .password-requirements {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: rgba(108, 117, 125, 0.1);
        }

        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 1.25rem;
        }

        @media (prefers-color-scheme: dark) {
            .password-requirements {
                background-color: rgba(108, 117, 125, 0.2);
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="auth-card">
            <div class="reset-header">
                <a href="<?= APP_URL ?>" class="brand-logo">
                    <i class="fas fa-clinic-medical"></i>Salvio
                </a>
                <h1 class="h3 mb-3 fw-normal">Reset Password</h1>
                <p class="text-muted">Please enter your new password below.</p>
            </div>

            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger mx-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= Session::getFlash('error') ?>
                </div>
            <?php endif; ?>

            <?php if (Session::hasFlash('success')): ?>
                <div class="alert alert-success mx-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= Session::getFlash('success') ?>
                </div>
            <?php endif; ?>

            <div class="reset-form">
                <form action="<?= APP_URL ?>/auth/reset/<?= $token ?>" method="post" id="resetForm">
                    <?= $this->csrf() ?>
                    
                    <div class="form-floating">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="New Password"
                               required 
                               minlength="6"
                               autofocus>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>New Password
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm Password"
                               required 
                               minlength="6">
                        <label for="confirm_password">
                            <i class="fas fa-lock me-2"></i>Confirm Password
                        </label>
                    </div>

                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li id="lengthCheck">At least 6 characters long</li>
                            <li id="upperCheck">Contains at least one uppercase letter</li>
                            <li id="numberCheck">Contains at least one number</li>
                            <li id="specialCheck">Contains at least one special character</li>
                        </ul>
                    </div>

                    <button class="w-100 btn btn-primary mt-3" type="submit">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>

                    <div class="back-to-login">
                        <a href="<?= APP_URL ?>/auth" class="auth-link">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="copyright">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const form = document.getElementById('resetForm');
        
        // Real-time password validation
        password.addEventListener('input', validatePassword);
        
        function validatePassword() {
            const value = password.value;
            
            // Length check
            document.getElementById('lengthCheck').style.color = 
                value.length >= 6 ? 'var(--success-color)' : 'var(--text-muted)';
            
            // Uppercase check
            document.getElementById('upperCheck').style.color = 
                /[A-Z]/.test(value) ? 'var(--success-color)' : 'var(--text-muted)';
            
            // Number check
            document.getElementById('numberCheck').style.color = 
                /\d/.test(value) ? 'var(--success-color)' : 'var(--text-muted)';
            
            // Special character check
            document.getElementById('specialCheck').style.color = 
                /[!@#$%^&*(),.?":{}|<>]/.test(value) ? 'var(--success-color)' : 'var(--text-muted)';
        }
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            const value = password.value;
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Check all requirements
            if (value.length < 6 || 
                !/[A-Z]/.test(value) || 
                !/\d/.test(value) || 
                !/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                e.preventDefault();
                alert('Please meet all password requirements!');
                return;
            }
        });
    </script>
</body>
</html>
