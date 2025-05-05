<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="<?= APP_URL ?>/assets/css/theme.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        /* Layout specific styles */
        .layout-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--primary-color);
        }

        .sidebar-brand .brand-logo {
            font-size: 1.5rem;
            margin: 0;
            color: #ffffff;
        }

        .sidebar-brand .brand-logo:hover {
            color: #ffffff;
        }

        .nav-item {
            margin: 0.5rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
            background: rgba(13, 110, 253, 0.1);
        }

        .nav-link i {
            width: 1.5rem;
            font-size: 1.1rem;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-width: 0;
            transition: margin-left 0.3s ease;
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: var(--card-bg);
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-color);
            padding: 0.5rem;
            cursor: pointer;
        }

        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Content Area */
        .content {
            padding: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .content {
                padding: 1.5rem;
            }

            /* Overlay */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 1rem;
            }

            .header {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-brand">
                <a href="<?= APP_URL ?>" class="brand-logo">
                    <i class="fas fa-clinic-medical"></i>Salvio
                </a>
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('dashboard') ?>" href="<?= APP_URL ?>/dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('pos') ?>" href="<?= APP_URL ?>/pos">
                        <i class="fas fa-cash-register"></i>
                        <span>Point of Sale</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('categories') ?>" href="<?= APP_URL ?>/categories">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('products') ?>" href="<?= APP_URL ?>/products">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('sales') ?>" href="<?= APP_URL ?>/sales">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sales</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('customers') ?>" href="<?= APP_URL ?>/customers">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <?php if (Auth::hasRole('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('reports') ?>" href="<?= APP_URL ?>/reports">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $this->getActiveMenu('settings') ?>" href="<?= APP_URL ?>/settings">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay"></div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-right">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-body" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <span class="ms-2 d-none d-sm-inline"><?= Auth::name() ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/profile">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/auth/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content">
                <?= $this->getFlashMessages() ?>
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            const mainContent = document.querySelector('.main-content');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            menuToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);

            // Close sidebar when window is resized to desktop size
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992 && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
