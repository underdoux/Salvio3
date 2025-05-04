<?php require_once 'views/layout/header.php'; ?>

<div class="maintenance-container">
    <div class="maintenance-content">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Under Maintenance</h1>
        <p>We're currently performing scheduled maintenance to improve our services.</p>
        <div class="estimated-time">
            <i class="fas fa-clock"></i>
            <span>Expected completion: <?= isset($data['completion_time']) ? $data['completion_time'] : '30 minutes' ?></span>
        </div>
        
        <?php if(isset($data['message'])): ?>
            <div class="maintenance-message">
                <?= View::escape($data['message']) ?>
            </div>
        <?php endif; ?>
        
        <div class="maintenance-actions">
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-redo"></i> Check Again
            </button>
        </div>
    </div>
</div>

<style>
.maintenance-container {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.maintenance-content {
    text-align: center;
    background: #fff;
    padding: 3rem;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    width: 100%;
}

.maintenance-icon {
    font-size: 4rem;
    color: #f39c12;
    margin-bottom: 1.5rem;
    animation: wrench 2.5s ease infinite;
}

@keyframes wrench {
    0% {
        transform: rotate(-12deg);
    }
    8% {
        transform: rotate(12deg);
    }
    10% {
        transform: rotate(24deg);
    }
    18% {
        transform: rotate(-24deg);
    }
    20% {
        transform: rotate(-24deg);
    }
    28% {
        transform: rotate(24deg);
    }
    30% {
        transform: rotate(24deg);
    }
    38% {
        transform: rotate(-24deg);
    }
    40% {
        transform: rotate(-24deg);
    }
    48% {
        transform: rotate(24deg);
    }
    50% {
        transform: rotate(24deg);
    }
    58% {
        transform: rotate(-24deg);
    }
    60% {
        transform: rotate(-24deg);
    }
    68% {
        transform: rotate(24deg);
    }
    75% {
        transform: rotate(0deg);
    }
}

.maintenance-content h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 1rem;
}

.maintenance-content p {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.estimated-time {
    background: #fff8e1;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 2rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.estimated-time i {
    color: #f39c12;
}

.maintenance-message {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 4px;
    margin: 2rem 0;
    color: #666;
    font-style: italic;
}

.maintenance-actions {
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
    background-color: #f39c12;
    color: #fff;
}

.btn-primary:hover {
    background-color: #e67e22;
    transform: translateY(-1px);
}

.btn i {
    margin-right: 0.5rem;
}

/* Progress indicator */
.progress-indicator {
    margin: 2rem 0;
    height: 4px;
    background: #eee;
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #f39c12;
    width: 0;
    animation: progress 30s linear infinite;
}

@keyframes progress {
    0% {
        width: 0;
    }
    100% {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .maintenance-content {
        padding: 2rem;
    }
    
    .maintenance-icon {
        font-size: 3rem;
    }
    
    .maintenance-content h1 {
        font-size: 2rem;
    }
    
    .maintenance-content p {
        font-size: 1rem;
    }
    
    .estimated-time {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php require_once 'views/layout/footer.php'; ?>
