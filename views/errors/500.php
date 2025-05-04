<?php require_once 'views/layout/header.php'; ?>

<div class="error-container">
    <div class="error-content">
        <div class="error-code">500</div>
        <h1>Internal Server Error</h1>
        <p>Sorry, something went wrong on our end. Please try again later.</p>
        
        <?php if(isset($data['showDetails']) && $data['showDetails'] && isset($data['exception'])): ?>
            <div class="error-details">
                <h2>Error Details</h2>
                <div class="error-message">
                    <?= View::escape($data['exception']->getMessage()) ?>
                </div>
                <div class="error-trace">
                    <pre><?= View::escape($data['exception']->getTraceAsString()) ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="error-actions">
            <a href="<?= BASE_URL ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Try Again
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
    max-width: 800px;
    width: 100%;
}

.error-code {
    font-size: 6rem;
    font-weight: bold;
    color: #e74c3c;
    line-height: 1;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
}

.error-content h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 1rem;
}

.error-content p {
    color: #666;
    margin-bottom: 2rem;
}

.error-details {
    text-align: left;
    margin: 2rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.error-details h2 {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 1rem;
}

.error-message {
    padding: 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 1rem;
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.error-trace {
    max-height: 300px;
    overflow-y: auto;
}

.error-trace pre {
    margin: 0;
    padding: 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-primary {
    background-color: #007bff;
    color: #fff;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn i {
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .error-content {
        padding: 2rem;
        margin: 1rem;
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
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php require_once 'views/layout/footer.php'; ?>
