<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error - <?= APP_NAME ?></title>
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
            color: var(--warning);
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

        .error-actions {
            margin: 2rem 0;
        }

        .error-actions .back-link {
            margin: 0 0.5rem;
        }

        .technical-details {
            margin-top: 2rem;
            text-align: left;
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            display: none;
        }

        .technical-details.show {
            display: block;
        }

        .technical-details pre {
            margin: 1rem 0 0;
            padding: 1rem;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .toggle-details {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0;
            font-size: 0.875rem;
            margin-top: 1rem;
        }

        .toggle-details:hover {
            text-decoration: underline;
        }

        .support-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .support-info h3 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .support-info p {
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .support-info .error-id {
            font-family: monospace;
            background: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="error-page">
                <div class="error-illustration">
                    <img src="<?= base_url('assets/img/500.svg') ?>" 
                         alt="500 Illustration"
                         width="300">
                </div>

                <h1 class="error-code">500</h1>
                <h2 class="error-message">Internal Server Error</h2>
                
                <p class="error-details">
                    Something went wrong on our end. Our team has been notified and is working to fix the issue.
                    Please try again later.
                </p>

                <div class="error-actions">
                    <a href="<?= base_url() ?>" class="back-link">
                        <i class="fas fa-home"></i>
                        Back to Home
                    </a>
                    
                    <a href="javascript:location.reload()" class="back-link">
                        <i class="fas fa-sync"></i>
                        Try Again
                    </a>
                </div>

                <?php if (DEBUG && isset($error)): ?>
                    <button class="toggle-details" onclick="toggleTechnicalDetails()">
                        <i class="fas fa-code"></i>
                        Show Technical Details
                    </button>

                    <div class="technical-details" id="technicalDetails">
                        <h3>Error Details</h3>
                        <p><strong>Message:</strong> <?= $this->e($error['message']) ?></p>
                        <p><strong>File:</strong> <?= $this->e($error['file']) ?></p>
                        <p><strong>Line:</strong> <?= $error['line'] ?></p>
                        <?php if (isset($error['trace'])): ?>
                            <h4>Stack Trace:</h4>
                            <pre><?= $this->e($error['trace']) ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="support-info">
                    <h3>Need Help?</h3>
                    <p>
                        If this problem persists, please contact our support team and provide the following error ID:
                        <br>
                        <span class="error-id"><?= $errorId ?? date('YmdHis') . '-' . substr(uniqid(), -6) ?></span>
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
        // Toggle technical details
        function toggleTechnicalDetails() {
            const details = document.getElementById('technicalDetails');
            details.classList.toggle('show');
            const button = document.querySelector('.toggle-details');
            button.textContent = details.classList.contains('show') ? 'Hide Technical Details' : 'Show Technical Details';
        }

        // Track 500 errors
        if (typeof gtag !== 'undefined') {
            gtag('event', 'error_500', {
                'event_category': 'Error',
                'event_label': window.location.pathname,
                'error_id': '<?= $errorId ?? "" ?>'
            });
        }
    </script>
</body>
</html>
