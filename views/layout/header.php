<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? View::escape($title) : APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .auth-box {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-logo img {
            max-width: 150px;
            height: auto;
        }
        .flash-message {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php if (Session::hasFlash('success') || Session::hasFlash('error')): ?>
        <div class="flash-message container mt-3">
            <?php if (Session::hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= View::escape(Session::getFlash('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= View::escape(Session::getFlash('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
