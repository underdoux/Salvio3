<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Notifications</h5>
        <div class="header-actions">
            <div class="btn-group">
                <a href="<?= base_url('notifications') ?>" 
                   class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-primary' ?>">
                    All
                </a>
                <a href="<?= base_url('notifications?status=unread') ?>" 
                   class="btn btn-sm <?= $status === 'unread' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Unread
                </a>
            </div>

            <button type="button" 
                    class="btn btn-sm btn-success ms-2"
                    onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i>
                Mark All as Read
            </button>

            <a href="<?= base_url('notifications/preferences') ?>" 
               class="btn btn-sm btn-light ms-2">
                <i class="fas fa-cog"></i>
                Preferences
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Notification Filters -->
        <div class="notification-filters p-3 border-bottom">
            <form action="<?= base_url('notifications') ?>" method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach ($templates['templates'] as $key => $template): ?>
                            <option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>>
                                <?= $template['title'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($status): ?>
                    <input type="hidden" name="status" value="<?= $status ?>">
                <?php endif; ?>
            </form>
        </div>

        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="text-center p-5">
                    <img src="<?= base_url('assets/img/empty-notifications.svg') ?>" 
                         alt="No notifications"
                         class="mb-3"
                         width="200">
                    <h5>No notifications found</h5>
                    <p class="text-muted">
                        You're all caught up! Check back later for new notifications.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= !$notification['read_at'] ? 'unread' : '' ?>"
                         data-id="<?= $notification['id'] ?>">
                        <div class="notification-icon bg-<?= $notification['color'] ?>">
                            <i class="fas fa-<?= $notification['icon'] ?>"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">
                                <?= $this->e($notification['title']) ?>
                            </h6>
                            <p class="notification-message">
                                <?= $this->e($notification['message']) ?>
                            </p>
                            <div class="notification-meta">
                                <span class="time">
                                    <i class="fas fa-clock"></i>
                                    <?= $this->timeAgo($notification['created_at']) ?>
                                </span>
                                <?php if ($notification['url']): ?>
                                    <a href="<?= base_url($notification['url']) ?>" class="action-link">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$notification['read_at']): ?>
                            <button type="button" 
                                    class="btn-mark-read"
                                    onclick="markAsRead(<?= $notification['id'] ?>)">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($page > 1 || count($notifications) === 20): ?>
                    <div class="pagination-wrapper p-3 border-top">
                        <nav aria-label="Notifications navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= base_url("notifications?page=" . ($page - 1) . ($type ? "&type={$type}" : "") . ($status ? "&status={$status}" : "")) ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if (count($notifications) === 20): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= base_url("notifications?page=" . ($page + 1) . ($type ? "&type={$type}" : "") . ($status ? "&status={$status}" : "")) ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notification-filters {
    background-color: var(--light);
}

.notifications-list {
    max-height: 600px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
    position: relative;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: var(--light);
}

.notification-item.unread {
    background-color: rgba(var(--primary-rgb), 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.notification-icon i {
    color: var(--white);
    font-size: 1.25rem;
}

.notification-content {
    flex: 1;
}

.notification-title {
    margin: 0 0 0.25rem;
    font-weight: 600;
}

.notification-message {
    margin: 0 0 0.5rem;
    color: var(--secondary);
}

.notification-meta {
    font-size: 0.875rem;
    color: var(--secondary);
}

.notification-meta .time {
    margin-right: 1rem;
}

.notification-meta i {
    margin-right: 0.25rem;
}

.action-link {
    color: var(--primary);
    text-decoration: none;
}

.action-link:hover {
    text-decoration: underline;
}

.btn-mark-read {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: var(--success);
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.notification-item:hover .btn-mark-read {
    opacity: 1;
}
</style>

<script>
function markAsRead(id) {
    fetch('<?= base_url('notifications/markAsRead/') ?>' + id, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.notification-item[data-id="${id}"]`);
            item.classList.remove('unread');
            item.querySelector('.btn-mark-read').remove();
        }
    });
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;

    fetch('<?= base_url('notifications/markAllAsRead') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
