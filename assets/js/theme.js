/**
 * Theme JavaScript
 * Handles interactive functionality for the application
 */

(function() {
    'use strict';

    // Initialize theme when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeTheme();
    });

    /**
     * Initialize theme functionality
     */
    function initializeTheme() {
        initializeSidebar();
        initializeDropdowns();
        initializeAlerts();
        initializeFormValidation();
        initializeDataTables();
        setupAjaxDefaults();
    }

    /**
     * Initialize sidebar functionality
     */
    function initializeSidebar() {
        // Toggle sidebar on menu button click
        const menuToggles = document.querySelectorAll('.menu-toggle, .sidebar-toggle');
        menuToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                // Store preference
                localStorage.setItem('sidebar-collapsed', 
                    document.body.classList.contains('sidebar-collapsed'));
            });
        });

        // Restore sidebar state from localStorage
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }

        // Auto-collapse sidebar on small screens
        const mediaQuery = window.matchMedia('(max-width: 768px)');
        function handleScreenSize(e) {
            if (e.matches) {
                document.body.classList.add('sidebar-collapsed');
            }
        }
        mediaQuery.addListener(handleScreenSize);
        handleScreenSize(mediaQuery);
    }

    /**
     * Initialize dropdown functionality
     */
    function initializeDropdowns() {
        // Toggle dropdown on click
        document.addEventListener('click', function(e) {
            const dropdown = e.target.closest('.dropdown');
            if (!dropdown) {
                // Close all dropdowns when clicking outside
                document.querySelectorAll('.dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
                return;
            }

            // Toggle clicked dropdown
            const isActive = dropdown.classList.contains('show');
            document.querySelectorAll('.dropdown.show').forEach(d => {
                d.classList.remove('show');
            });
            if (!isActive) {
                dropdown.classList.add('show');
            }
        });
    }

    /**
     * Initialize alert functionality
     */
    function initializeAlerts() {
        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.classList.contains('alert-persistent')) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });

        // Add close button functionality
        document.querySelectorAll('.alert .close').forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        });
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        });
    }

    /**
     * Initialize DataTables if present
     */
    function initializeDataTables() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.datatable').DataTable({
                responsive: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Search...'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                dom: '<"datatable-header"fl><"datatable-scroll"t><"datatable-footer"ip>',
                ordering: true,
                processing: true
            });
        }
    }

    /**
     * Setup AJAX defaults
     */
    function setupAjaxDefaults() {
        if (typeof $ !== 'undefined') {
            // Add CSRF token to all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            // Show loading indicator for AJAX requests
            $(document).ajaxStart(function() {
                showLoading();
            }).ajaxStop(function() {
                hideLoading();
            });

            // Handle AJAX errors
            $(document).ajaxError(function(event, jqXHR, settings, error) {
                if (jqXHR.status === 401) {
                    // Unauthorized - redirect to login
                    window.location.href = BASE_URL + '/auth/login';
                } else if (jqXHR.status === 403) {
                    // Forbidden
                    showError('Access denied');
                } else if (jqXHR.status === 422) {
                    // Validation error
                    const errors = jqXHR.responseJSON.errors;
                    let message = '<ul>';
                    for (let field in errors) {
                        message += `<li>${errors[field][0]}</li>`;
                    }
                    message += '</ul>';
                    showError(message);
                } else {
                    // Generic error
                    showError('An error occurred. Please try again.');
                }
            });
        }
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        if (!document.getElementById('loading-indicator')) {
            const loading = document.createElement('div');
            loading.id = 'loading-indicator';
            loading.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(loading);
        }
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        const loading = document.getElementById('loading-indicator');
        if (loading) {
            loading.remove();
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.querySelector('.content').insertAdjacentElement('afterbegin', alert);
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.querySelector('.content').insertAdjacentElement('afterbegin', alert);
    }

    // Export functions to global scope
    window.theme = {
        showLoading,
        hideLoading,
        showError,
        showSuccess
    };
})();
