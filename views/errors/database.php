<?php require_once 'views/layout/header.php'; ?>

<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-database"></i>
        </div>
        <h1>Database Connection Error</h1>
        <p>We're having trouble connecting to our database. This issue has been logged and we're working on it.</p>
        
        <?php if(isset($data['showDetails']) && $data['showDetails'] && isset($data['error'])): ?>
            <div class="error-details">
                <h2>Technical Details</h2>
                <div class="error-message">
                    <?= View::escape($data['error']) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="troubleshooting">
            <h3>What you can try:</h3>
            <ul>
                <li><i class="fas fa-redo"></i> Refresh the page</li>
                <li><i class="fas fa-clock"></i> Wait a few minutes and try again</li>
                <li><i class="fas fa-home"></i> Return to the homepage</li>
            </ul>
        </div>
        
        <div class="error-actions">
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-redo"></i> Try Again
            </button>
            <a href="<?= BASE_URL ?>" class="btn btn-secondary">
                <i class="fas fa-home"></i> Homepage
            </a>
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
    max-width: 600px;
    width: 100%;
}

.error-icon {
    font-size: 4rem;
    color: #e74c3c;
    margin-bottom: 1.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.error-content h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 1rem;
}

.error-content p {
    color: #666;
    font-size: 1.1rem;
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
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
    font-family: monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.troubleshooting {
    text-align: left;
    margin: 2rem 0;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.troubleshooting h3 {
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.troubleshooting ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.troubleshooting li {
    margin-bottom: 0.75rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.troubleshooting li i {
    color: #3498db;
    width: 20px;
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
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #3498db;
    color: #fff;
}

.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.btn i {
    margin-right: 0.5rem;
}

@media (max-width: 480px) {
    .error-content {
        padding: 2rem;
    }
    
    .error-icon {
        font-size: 3rem;
    }
    
    .error-content h1 {
        font-size: 1.5rem;
    }
    
    .error-content p {
        font-size: 1rem;
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
