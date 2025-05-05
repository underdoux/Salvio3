<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - <?= APP_NAME ?></title>
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
            color: var(--primary);
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

        .search-form {
            max-width: 400px;
            margin: 2rem auto;
        }

        .search-form .form-control {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="error-page">
                <div class="error-illustration">
                    <img src="<?= base_url('assets/img/404.svg') ?>" 
                         alt="404 Illustration"
                         width="300">
                </div>

                <h1 class="error-code">404</h1>
                <h2 class="error-message">Page Not Found</h2>
                
                <p class="error-details">
                    The page you are looking for might have been removed, 
                    had its name changed, or is temporarily unavailable.
                </p>

                <form action="<?= base_url('search') ?>" method="GET" class="search-form">
                    <div class="form-group">
                        <input type="text" 
                               name="q" 
                               class="form-control" 
                               placeholder="Search for pages..."
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </form>

                <div style="margin-top: 2rem;">
                    <a href="<?= base_url() ?>" class="back-link">
                        <i class="fas fa-home"></i>
                        Back to Home
                    </a>
                    
                    <a href="javascript:history.back()" class="back-link" style="margin-left: 1rem;">
                        <i class="fas fa-arrow-left"></i>
                        Go Back
                    </a>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Track 404 errors
        if (typeof gtag !== 'undefined') {
            gtag('event', 'error_404', {
                'event_category': 'Error',
                'event_label': window.location.pathname
            });
        }
    </script>
</body>
</html>
