<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Login - Salvio POS' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/theme.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .login-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
        }

        .login-form {
            padding: 0 2rem 2rem;
        }

        .remember-me {
            margin: 1rem 0;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .copyright {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            z-index: 4;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="auth-card">
            <div class="login-header">
                <a href="<?= APP_URL ?>" class="brand-logo">
                    <i class="fas fa-clinic-medical"></i>Salvio
                </a>
                <h1 class="h3 mb-3 fw-normal">Welcome Back!</h1>
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

            <div class="login-form">
                <form action="<?= APP_URL ?>/auth/login" method="post">
                    <?= $this->csrf() ?>
                    
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Username"
                               required 
                               autofocus>
                        <label for="username">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                    </div>

                    <div class="form-floating position-relative">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Password"
                               required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>

                    <div class="remember-me">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="remember" 
                                   id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <button class="w-100 btn btn-primary" type="submit">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign in
                    </button>

                    <div class="forgot-password">
                        <a href="<?= APP_URL ?>/auth/forgot" class="auth-link">
                            <i class="fas fa-key me-1"></i>Forgot password?
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
