<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Access Denied - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <style>
        .error-page {
            text-align: center;
            padding: 2rem;
        }
        
        .error-illustration {
            max-width: 300px;
            margin: 0 auto 2rem;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: var(--danger);
            margin: 0;
            line-height: 1;
        }
        
        .error-message {
            font-size: 1.5rem;
            color: var(--dark);
            margin: 1rem 0 2rem;
        }
        
        .error-details {
            color: var(--secondary);
            margin-bottom: 2rem;
        }
        
        .back-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease;
        }
        
        .back-link:hover {
            background: var(--primary-dark);
        }

        .login-link {
            background: var(--success);
        }

        .login-link:hover {
            background: var(--success-dark);
        }

        .contact-support {
            margin-top: 2rem;
            padding: 1rem;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .contact-support h3 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .contact-support p {
            color: var(--secondary);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="error-page">
                <div class="error-illustration">
                    <img src="<?= base_url('assets/img/403.svg') ?>" 
                         alt="403 Illustration"
                         width="300">
                </div>

                <h1 class="error-code">403</h1>
                <h2 class="error-message">Access Denied</h2>
                
                <p class="error-details">
                    You do not have permission to access this resource.
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        Please log in with appropriate credentials.
                    <?php endif; ?>
                </p>

                <div style="margin-top: 2rem;">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="<?= base_url('auth/login') ?>" class="back-link login-link">
                            <i class="fas fa-sign-in-alt"></i>
                            Log In
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?= base_url() ?>" class="back-link">
                        <i class="fas fa-home"></i>
                        Back to Home
                    </a>
                    
                    <a href="javascript:history.back()" class="back-link" style="margin-left: 1rem;">
                        <i class="fas fa-arrow-left"></i>
                        Go Back
                    </a>
                </div>

                <div class="contact-support">
                    <h3>Need Help?</h3>
                    <p>
                        If you believe you should have access to this resource,
                        please contact your system administrator or support team.
                    </p>
                    <a href="mailto:support@example.com" class="back-link">
                        <i class="fas fa-envelope"></i>
                        Contact Support
                    </a>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Track 403 errors
        if (typeof gtag !== 'undefined') {
            gtag('event', 'error_403', {
                'event_category': 'Error',
                'event_label': window.location.pathname
            });
        }
    </script>
</body>
</html>
