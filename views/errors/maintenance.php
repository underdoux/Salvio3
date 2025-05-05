<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <style>
        .maintenance-page {
            text-align: center;
            padding: 2rem;
        }
        
        .maintenance-illustration {
            max-width: 400px;
            margin: 0 auto 2rem;
        }
        
        .maintenance-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 0 0 1rem;
        }
        
        .maintenance-message {
            font-size: 1.25rem;
            color: var(--dark);
            margin: 1rem 0 2rem;
            line-height: 1.6;
        }
        
        .maintenance-details {
            color: var(--secondary);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .maintenance-timer {
            background: var(--light);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        .timer-value {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
            font-family: monospace;
        }

        .timer-label {
            color: var(--secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .notification-form {
            max-width: 400px;
            margin: 2rem auto;
            padding: 1.5rem;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .notification-form h3 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .notification-form .form-group {
            margin-bottom: 1rem;
        }

        .notification-form .form-control {
            width: 100%;
            padding: 0.75rem;
        }

        .social-links {
            margin-top: 2rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            color: var(--dark);
            margin: 0 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .social-links a:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-message {
                font-size: 1rem;
            }

            .timer-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="maintenance-page">
                <div class="maintenance-illustration">
                    <img src="<?= base_url('assets/img/maintenance.svg') ?>" 
                         alt="Maintenance Illustration"
                         width="400">
                </div>

                <h1 class="maintenance-title">System Maintenance</h1>
                
                <p class="maintenance-message">
                    We're currently performing scheduled maintenance to improve our services.
                </p>
                
                <div class="maintenance-details">
                    <p>
                        Our team is working hard to complete the maintenance as quickly as possible.
                        We expect to be back online in:
                    </p>
                </div>

                <div class="maintenance-timer">
                    <div class="timer-value" id="maintenanceTimer">00:00:00</div>
                    <div class="timer-label">Estimated Time Remaining</div>
                </div>

                <div class="notification-form">
                    <h3>Get Notified When We're Back</h3>
                    <form id="notificationForm" onsubmit="return submitNotification(event)">
                        <div class="form-group">
                            <input type="email" 
                                   class="form-control" 
                                   placeholder="Enter your email"
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            Notify Me
                        </button>
                    </form>
                </div>

                <div class="social-links">
                    <a href="#" target="_blank" title="Follow us on Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" target="_blank" title="Follow us on Facebook">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" target="_blank" title="Follow us on Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Set the maintenance end time (3 hours from now by default)
        const endTime = new Date(Date.now() + 3 * 60 * 60 * 1000);

        // Update timer every second
        function updateTimer() {
            const now = new Date();
            const diff = endTime - now;

            if (diff <= 0) {
                document.getElementById('maintenanceTimer').textContent = 'Maintenance Complete';
                location.reload(); // Refresh the page
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            document.getElementById('maintenanceTimer').textContent = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        // Update timer immediately and every second
        updateTimer();
        setInterval(updateTimer, 1000);

        // Handle notification form submission
        function submitNotification(event) {
            event.preventDefault();
            const email = event.target.querySelector('input[type="email"]').value;
            
            // TODO: Implement notification system
            alert('Thank you! We\'ll notify you when the system is back online.');
            event.target.reset();
            
            return false;
        }

        // Check system status every minute
        function checkStatus() {
            fetch('/status')
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    }
                })
                .catch(() => {
                    // Continue checking
                });
        }
        setInterval(checkStatus, 60000);
    </script>
</body>
</html>
