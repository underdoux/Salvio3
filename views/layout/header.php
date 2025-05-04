<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($data['title']) ? $data['title'] . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom styles for specific pages -->
    <?php if(isset($data['css'])): ?>
        <?php foreach($data['css'] as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= isset($data['bodyClass']) ? $data['bodyClass'] : '' ?>">
    <?php if(isset($_SESSION['flash'])): ?>
        <div class="flash-messages">
            <?php foreach($_SESSION['flash'] as $flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
