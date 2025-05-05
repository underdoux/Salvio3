<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Page specific CSS -->
    <?= $this->getSection('css', '') ?>
</head>
<body class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="app-logo">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="<?= APP_NAME ?>">
            </div>
            <button class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav">
                <li class="nav-item <?= url_is('dashboard*') ? 'active' : '' ?>">
                    <a href="<?= base_url('dashboard') ?>" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item <?= url_is('sales*') ? 'active' : '' ?>">
                    <a href="<?= base_url('sales') ?>" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sales</span>
                    </a>
                </li>

                <li class="nav-item <?= url_is('products*') ? 'active' : '' ?>">
                    <a href="<?= base_url('products') ?>" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>

                <li class="nav-item <?= url_is('categories*') ? 'active' : '' ?>">
                    <a href="<?= base_url('categories') ?>" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>

                <li class="nav-item <?= url_is('customers*') ? 'active' : '' ?>">
                    <a href="<?= base_url('customers') ?>" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>

                <?php if ($this->auth->isAdmin()): ?>
                    <li class="nav-item <?= url_is('suppliers*') ? 'active' : '' ?>">
                        <a href="<?= base_url('suppliers') ?>" class="nav-link">
                            <i class="fas fa-truck"></i>
                            <span>Suppliers</span>
                        </a>
                    </li>

                    <li class="nav-item <?= url_is('users*') ? 'active' : '' ?>">
                        <a href="<?= base_url('users') ?>" class="nav-link">
                            <i class="fas fa-user-cog"></i>
                            <span>Users</span>
                        </a>
                    </li>

                    <li class="nav-item <?= url_is('reports*') ? 'active' : '' ?>">
                        <a href="<?= base_url('reports') ?>" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>

                    <li class="nav-item <?= url_is('settings*') ? 'active' : '' ?>">
                        <a href="<?= base_url('settings') ?>" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title">
                    <?= $title ?? APP_NAME ?>
                </div>
            </div>

            <div class="header-right">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn-icon" data-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="badge" id="notification-count">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="dropdown-header">
                            <h6>Notifications</h6>
                            <a href="<?= base_url('notifications') ?>">View All</a>
                        </div>
                        <div class="dropdown-body" id="notification-list">
                            <div class="text-center p-3">
                                <p>No new notifications</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn-icon" data-toggle="dropdown">
                        <img src="<?= base_url('uploads/users/' . ($this->auth->user()['photo'] ?? 'default.png')) ?>" 
                             alt="<?= $this->auth->user()['name'] ?>"
                             class="avatar">
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="<?= base_url('profile') ?>" class="dropdown-item">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="<?= base_url('profile/settings') ?>" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url('auth/logout') ?>" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <?= $this->getFlash() ?>
            <?= $this->getSection('content') ?>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>
    
    <!-- Page specific JavaScript -->
    <?= $this->getSection('js', '') ?>

    <script>
        // Initialize notifications
        function initNotifications() {
            $.get('<?= base_url('notifications/unread') ?>', function(data) {
                if (data.count > 0) {
                    $('#notification-count').text(data.count).show();
                    let html = '';
                    data.notifications.forEach(function(notification) {
                        html += `
                            <a href="${notification.url}" class="dropdown-item">
                                <div class="notification-item">
                                    <div class="icon">
                                        <i class="fas fa-${notification.icon}"></i>
                                    </div>
                                    <div class="details">
                                        <p>${notification.message}</p>
                                        <small>${notification.time_ago}</small>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    $('#notification-list').html(html);
                }
            });
        }

        // Initialize tooltips and popovers
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
            
            // Toggle sidebar
            $('.sidebar-toggle, .menu-toggle').on('click', function() {
                $('body').toggleClass('sidebar-collapsed');
            });

            // Initialize notifications
            initNotifications();
            setInterval(initNotifications, 60000); // Check every minute
        });
    </script>
</body>
</html>
