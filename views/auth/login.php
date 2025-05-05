<?php
// Set layout for login page
$this->setLayout(null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="<?= APP_URL ?>/assets/css/theme.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card p-4">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <a href="<?= APP_URL ?>" class="brand-logo">
                            <i class="fas fa-clinic-medical"></i>Salvio
                        </a>
                    </div>

                    <h2 class="text-center mb-4">Welcome Back!</h2>

                    <?= $this->getFlashMessages() ?>

                    <!-- Login Form -->
                    <form action="<?= APP_URL ?>/auth/login" method="post" class="needs-validation" novalidate>
                        <?= $this->csrf() ?>
                        
                        <!-- Username -->
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username"
                                   autocomplete="username"
                                   required>
                            <label for="username">Username</label>
                        </div>

                        <!-- Password -->
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password"
                                   autocomplete="current-password"
                                   required>
                            <label for="password">Password</label>
                        </div>

                        <!-- Remember Me -->
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember" 
                                   value="1">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign in
                        </button>

                        <!-- Forgot Password Link -->
                        <div class="text-center mt-3">
                            <a href="<?= APP_URL ?>/auth/forgot" class="auth-link">
                                <i class="fas fa-key me-1"></i>Forgot password?
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Copyright -->
                <div class="text-center text-muted mt-3">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Form Validation -->
    <script>
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
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
</body>
</html>
