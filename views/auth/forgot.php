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
                <p>Enter your email to receive reset instructions</p>
            </div>

            <?= $this->getFlash() ?>

            <form action="<?= base_url('auth/sendReset') ?>" method="POST" class="auth-form">
                <?= $this->csrf() ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           autofocus
                           class="form-control"
                           placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        Send Reset Link
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
</body>
</html>
