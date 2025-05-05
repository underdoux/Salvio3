<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $code ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <style>
        .error-page {
            text-align: center;
            padding: 2rem;
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

        .stack-trace {
            text-align: left;
            background: var(--light);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-top: 2rem;
            overflow-x: auto;
        }

        .stack-trace pre {
            margin: 0;
            font-family: monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="error-page">
                <h1 class="error-code"><?= $code ?></h1>
                <h2 class="error-message">
                    <?php
                    switch ($code) {
                        case 404:
                            echo 'Page Not Found';
                            break;
                        case 403:
                            echo 'Access Denied';
                            break;
                        case 500:
                            echo 'Internal Server Error';
                            break;
                        default:
                            echo $message ?? 'An Error Occurred';
                    }
                    ?>
                </h2>
                
                <p class="error-details">
                    <?php
                    switch ($code) {
                        case 404:
                            echo 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.';
                            break;
                        case 403:
                            echo 'You do not have permission to access this resource.';
                            break;
                        case 500:
                            echo 'Something went wrong on our end. Please try again later.';
                            break;
                        default:
                            echo $message ?? 'Please try again or contact support if the problem persists.';
                    }
                    ?>
                </p>

                <?php if (isset($trace) && DEBUG): ?>
                    <div class="stack-trace">
                        <pre><?= $trace ?></pre>
                    </div>
                <?php endif; ?>

                <a href="<?= base_url(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') ?>" 
                   class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
