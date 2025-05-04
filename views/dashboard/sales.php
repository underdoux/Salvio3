<?php require_once 'views/layout/header.php'; ?>

<div class="dashboard-container">
    <!-- Stats Overview -->
    <div class="stats-grid">
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

        <!-- Monthly Sales Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-details">
                <h3>Monthly Sales</h3>
                <div class="stat-value">
                    <?= View::formatCurrency($data['stats']['month_sales']['amount'] ?? 0) ?>
                </div>
                <div class="stat-subtitle">
                    <?= number_format($data['stats']['month_sales']['count'] ?? 0) ?> transactions
                </div>
            </div>
        </div>

        <!-- Commission Card -->
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-details">
                <h3>Monthly Commission</h3>
                <div class="stat-value">
                    <?= View::formatCurrency($data['stats']['commission'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-grid">
        <!-- Recent Sales -->
        <div class="content-card sales-card">
            <div class="card-header">
                <h2><i class="fas fa-receipt"></i> Recent Sales</h2>
                <a href="<?= BASE_URL ?>/sales" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if(!empty($data['recent_sales'])): ?>
                    <div class="sales-list">
                        <?php foreach($data['recent_sales'] as $sale): ?>
                            <div class="sale-item">
                                <div class="sale-info">
                                    <div class="sale-header">
                                        <span class="sale-id">#<?= $sale->invoice_number ?></span>
                                        <span class="sale-time"><?= View::formatDate($sale->created_at, 'g:i A') ?></span>
                                    </div>
                                    <div class="customer-name">
                                        <i class="fas fa-user"></i>
                                        <?= View::escape($sale->customer_name) ?>
                                    </div>
                                </div>
                                <div class="sale-amount">
                                    <?= View::formatCurrency($sale->total_amount) ?>
                                </div>
                                <div class="sale-actions">
                                    <a href="<?= BASE_URL ?>/sales/view/<?= $sale->id ?>" 
                                       class="btn btn-sm btn-icon" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/sales/print/<?= $sale->id ?>" 
                                       class="btn btn-sm btn-icon" 
                                       title="Print Invoice">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-receipt"></i>
                        <p>No recent sales found</p>
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
                    <a href="<?= BASE_URL ?>/sales/create" class="quick-action primary">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Sale</span>
                    </a>
                    <a href="<?= BASE_URL ?>/customers/create" class="quick-action">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Customer</span>
                    </a>
                    <a href="<?= BASE_URL ?>/products" class="quick-action">
                        <i class="fas fa-boxes"></i>
                        <span>View Products</span>
                    </a>
                    <a href="<?= BASE_URL ?>/sales/reports" class="quick-action">
                        <i class="fas fa-chart-line"></i>
                        <span>My Reports</span>
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
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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

/* Sales List */
.sales-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.sale-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.sale-item:hover {
    background: #f0f0f0;
}

.sale-info {
    flex: 1;
}

.sale-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.25rem;
}

.sale-id {
    font-weight: 500;
    color: #333;
}

.sale-time {
    font-size: 0.875rem;
    color: #666;
}

.customer-name {
    font-size: 0.875rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sale-amount {
    font-weight: 500;
    color: #4CAF50;
    margin: 0 1rem;
}

.sale-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    background: #fff;
    color: #666;
    border: 1px solid #ddd;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #4CAF50;
    color: #fff;
    border-color: #4CAF50;
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

.quick-action.primary {
    background: #4CAF50;
    color: #fff;
}

.quick-action.primary:hover {
    background: #43a047;
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
    
    .sale-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .sale-amount {
        margin: 0;
    }
    
    .sale-actions {
        width: 100%;
        justify-content: flex-end;
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
