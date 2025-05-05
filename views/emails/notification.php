<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        /* Email styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
            background-color: #f5f8fa;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .email-header {
            text-align: center;
            padding: 20px;
            background-color: #007bff;
            border-radius: 8px 8px 0 0;
        }

        .email-header img {
            max-width: 200px;
            height: auto;
        }

        .email-body {
            padding: 30px;
            background-color: #ffffff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .email-title {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            margin: 0;
        }

        .email-message {
            margin: 20px 0;
            color: #333;
        }

        .email-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }

        .email-button:hover {
            background-color: #0056b3;
        }

        .email-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #007bff;
            text-decoration: none;
        }

        .email-disclaimer {
            font-size: 12px;
            color: #6c757d;
            margin-top: 20px;
        }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .container {
                padding: 10px;
            }

            .email-header,
            .email-body {
                padding: 15px;
            }

            .email-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-header">
            <img src="<?= base_url('assets/img/logo-white.png') ?>" 
                 alt="<?= APP_NAME ?>" 
                 style="max-width: 200px; height: auto;">
            <h1 class="email-title"><?= $title ?></h1>
        </div>

        <div class="email-body">
            <div class="email-message">
                <?= $message ?>
            </div>

            <?php if (isset($action_url)): ?>
                <a href="<?= $action_url ?>" class="email-button">
                    <?= $action_text ?? 'View Details' ?>
                </a>
            <?php endif; ?>

            <div class="email-footer">
                <div class="social-links">
                    <a href="https://twitter.com/youraccount">Twitter</a>
                    <a href="https://facebook.com/youraccount">Facebook</a>
                    <a href="https://instagram.com/youraccount">Instagram</a>
                </div>

                <p>
                    &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
                </p>

                <div class="email-disclaimer">
                    This email was sent to you because you are a registered user of <?= APP_NAME ?>.
                    If you did not request this email, please ignore it or 
                    <a href="<?= base_url('contact') ?>">contact support</a>.
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($pixel_tracking)): ?>
        <img src="<?= $pixel_tracking ?>" alt="" width="1" height="1" style="display:none;">
    <?php endif; ?>
</body>
</html>
