<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Forgot Password - Salvio POS' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/theme.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
        }

        .forgot-container {
            width: 100%;
            max-width: 420px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .forgot-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
        }

        .forgot-form {
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
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="auth-card">
            <div class="forgot-header">
                <a href="<?= APP_URL ?>" class="brand-logo">
                    <i class="fas fa-clinic-medical"></i>Salvio
                </a>
                <h1 class="h3 mb-3 fw-normal">Forgot Password</h1>
                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
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

            <div class="forgot-form">
                <form action="<?= APP_URL ?>/auth/forgot" method="post">
                    <?= $this->csrf() ?>
                    
                    <div class="form-floating">
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="name@example.com"
                               required 
                               autofocus>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email address
                        </label>
                    </div>

                    <button class="w-100 btn btn-primary" type="submit">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
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
</body>
</html>
