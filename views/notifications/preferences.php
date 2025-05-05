<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Notification Preferences</h5>
    </div>

    <div class="card-body">
        <?= $this->getFlash() ?>

        <form action="<?= base_url('notifications/updatePreferences') ?>" method="POST">
            <?= $this->csrf() ?>

            <!-- Notification Channels -->
            <div class="section mb-4">
                <h6 class="section-title">Notification Channels</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="email" 
                                   name="email"
                                   <?= $preferences['email'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email">
                                <i class="fas fa-envelope"></i>
                                Email Notifications
                            </label>
                            <small class="form-text text-muted d-block">
                                Receive notifications via email
                            </small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="browser" 
                                   name="browser"
                                   <?= $preferences['browser'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="browser">
                                <i class="fas fa-bell"></i>
                                Browser Notifications
                            </label>
                            <small class="form-text text-muted d-block">
                                Receive notifications in your browser
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Types -->
            <div class="section mb-4">
                <h6 class="section-title">Notification Types</h6>
                <p class="text-muted mb-3">
                    Select which types of notifications you want to receive
                </p>

                <div class="row">
                    <?php foreach ($templates['templates'] as $type => $template): ?>
                        <div class="col-md-6 mb-3">
                            <div class="notification-type-card">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="type_<?= $type ?>" 
                                           name="types[]"
                                           value="<?= $type ?>"
                                           <?= in_array($type, $preferences['types']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="type_<?= $type ?>">
                                        <i class="fas fa-<?= $template['icon'] ?>"></i>
                                        <?= $template['title'] ?>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <?= $template['message'] ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.section {
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1.5rem;
}

.section:last-child {
    border-bottom: none;
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--dark);
}

.notification-type-card {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: all 0.2s ease;
}

.notification-type-card:hover {
    background-color: var(--light);
}

.form-check-input:checked ~ .form-check-label {
    color: var(--primary);
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

/* Custom switch styling */
.form-switch {
    padding-left: 3rem;
}

.form-switch .form-check-input {
    width: 2.5rem;
    height: 1.25rem;
    margin-left: -3rem;
}

.form-switch .form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.form-switch .form-check-label i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }

    .notification-type-card {
        height: auto !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Request browser notification permission if needed
    const browserSwitch = document.getElementById('browser');
    browserSwitch.addEventListener('change', function() {
        if (this.checked && Notification.permission !== 'granted') {
            Notification.requestPermission().then(function(permission) {
                if (permission !== 'granted') {
                    browserSwitch.checked = false;
                    alert('Browser notifications permission denied');
                }
            });
        }
    });

    // Equal height cards
    function equalizeCards() {
        const cards = document.querySelectorAll('.notification-type-card');
        let maxHeight = 0;
        
        cards.forEach(card => {
            card.style.height = 'auto';
            maxHeight = Math.max(maxHeight, card.offsetHeight);
        });

        cards.forEach(card => {
            card.style.height = maxHeight + 'px';
        });
    }

    // Run on load and resize
    equalizeCards();
    window.addEventListener('resize', equalizeCards);
});
</script>
