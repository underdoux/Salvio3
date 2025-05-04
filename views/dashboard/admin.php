<?php require_once 'views/layout/header.php'; ?>

<div class="dashboard-container">
    <!-- Stats Overview -->
    <div class="stats-grid">
        <!-- Users Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Total Users</h3>
                <div class="stat-value"><?= number_format($data['stats']['total_users'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Products Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <h3>Total Products</h3>
                <div class="stat-value"><?= number_format($data['stats']['total_products'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Today's Sales Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-details">
                <h3>Today's Sales</h3>
                <div class="stat-value">
                    <?= View::formatCurrency($data['stats']['today_sales']['amount'] ?? 0) ?>
                </div>
                <div class="stat-subtitle">
                    <?= number_format($data['stats']['today_sales']['count'] ?? 0) ?> transactions
                </div>
            </div>
        </div>

        <!-- Low Stock Alert Card -->
        <div class="stat-card <?= ($data['stats']['low_stock'] ?? 0) > 0 ? 'alert' : '' ?>">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h3>Low Stock Items</h3>
                <div class="stat-value"><?= number_format($data['stats']['low_stock'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="content-grid">
        <div class="content-card activities-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Activities</h2>
                <a href="<?= BASE_URL ?>/admin/activities" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if(!empty($data['recent_activities'])): ?>
                    <div class="activity-list">
                        <?php foreach($data['recent_activities'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php
                                    $icon = 'circle';
                                    switch($activity->action) {
                                        case 'LOGIN':
                                            $icon = 'sign-in-alt';
                                            break;
                                        case 'LOGOUT':
                                            $icon = 'sign-out-alt';
                                            break;
                                        case 'CREATE':
                                            $icon = 'plus';
                                            break;
                                        case 'UPDATE':
                                            $icon = 'edit';
                                            break;
                                        case 'DELETE':
                                            $icon = 'trash';
                                            break;
                                    }
                                    ?>
                                    <i class="fas fa-<?= $icon ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-header">
                                        <span class="activity-user"><?= View::escape($activity->user_name) ?></span>
                                        <span class="activity-time"><?= View::formatDate($activity->created_at, 'g:i A') ?></span>
                                    </div>
                                    <div class="activity-description">
                                        <?= View::escape($activity->description) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No recent activities found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card quick-actions-card">
            <div class="card-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="<?= BASE_URL ?>/users/create" class="quick-action">
                        <i class="fas fa-user-plus"></i>
                        <span>Add User</span>
                    </a>
                    <a href="<?= BASE_URL ?>/products/create" class="quick-action">
                        <i class="fas fa-box-open"></i>
                        <span>Add Product</span>
                    </a>
                    <a href="<?= BASE_URL ?>/reports/sales" class="quick-action">
                        <i class="fas fa-chart-bar"></i>
                        <span>Sales Report</span>
                    </a>
                    <a href="<?= BASE_URL ?>/settings" class="quick-action">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Layout */
.dashboard-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.alert {
    border-left: 4px solid #e74c3c;
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 1.5rem;
    color: #4CAF50;
}

.stat-card.alert .stat-icon i {
    color: #e74c3c;
}

.stat-details {
    flex: 1;
}

.stat-details h3 {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.stat-subtitle {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

.content-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h2 {
    font-size: 1.25rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-header h2 i {
    color: #4CAF50;
}

.card-body {
    padding: 1.5rem;
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-icon i {
    font-size: 0.875rem;
    color: #4CAF50;
}

.activity-details {
    flex: 1;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.activity-user {
    font-weight: 500;
    color: #333;
}

.activity-time {
    font-size: 0.875rem;
    color: #666;
}

.activity-description {
    font-size: 0.875rem;
    color: #666;
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
}

.quick-action:hover {
    background: #4CAF50;
    color: #fff;
    transform: translateY(-2px);
}

.quick-action i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.quick-action span {
    font-size: 0.875rem;
    font-weight: 500;
}

/* No Data State */
.no-data {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-data i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #ddd;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'views/layout/footer.php'; ?>
