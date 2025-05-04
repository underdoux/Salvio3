<?php require_once 'views/layout/header.php'; ?>

<div class="error-container">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        
        <div class="error-actions">
            <a href="<?= BASE_URL ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>
    </div>
</div>

<style>
.error-container {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.error-content {
    text-align: center;
    background: #fff;
    padding: 3rem;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 100%;
}

.error-code {
    font-size: 6rem;
    font-weight: bold;
    color: var(--primary-color);
    line-height: 1;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
}

.error-content h1 {
    font-size: 2rem;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.error-content p {
    color: #666;
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

@media (max-width: 480px) {
    .error-content {
        padding: 2rem;
    }
    
    .error-code {
        font-size: 4rem;
    }
    
    .error-content h1 {
        font-size: 1.5rem;
    }
    
    .error-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once 'views/layout/footer.php'; ?>
