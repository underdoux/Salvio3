/**
 * Dashboard JavaScript
 * Handles dynamic functionality for the dashboard
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeDashboard();
    });

    /**
     * Initialize dashboard functionality
     */
    function initializeDashboard() {
        initializeChartUpdates();
        initializeTableSorting();
        initializeNotifications();
        initializeExport();
        setupRefreshTimer();
    }

    /**
     * Initialize chart updates
     */
    function initializeChartUpdates() {
        const dateRangeForm = document.getElementById('dateRangeForm');
        if (!dateRangeForm) return;

        // Update charts when date range changes
        dateRangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateDashboardData();
        });
    }

    /**
     * Update dashboard data via AJAX
     */
    function updateDashboardData() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Show loading state
        document.querySelectorAll('.card').forEach(card => {
            card.classList.add('loading');
        });

        // Fetch updated data
        fetch(`${BASE_URL}/dashboard/getSalesReport`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ start_date: startDate, end_date: endDate })
        })
        .then(response => response.json())
        .then(data => {
            updateCharts(data);
            updateStatistics(data);
            updateTables(data);
        })
        .catch(error => {
            console.error('Error updating dashboard:', error);
            theme.showError('Failed to update dashboard data');
        })
        .finally(() => {
            // Remove loading state
            document.querySelectorAll('.card').forEach(card => {
                card.classList.remove('loading');
            });
        });
    }

    /**
     * Update chart data
     */
    function updateCharts(data) {
        // Update sales chart
        if (window.salesChart) {
            window.salesChart.data.labels = data.salesChart.labels;
            window.salesChart.data.datasets[0].data = data.salesChart.values;
            window.salesChart.update();
        }

        // Update payment methods chart
        if (window.paymentChart) {
            window.paymentChart.data.labels = data.paymentStats.map(item => item.method);
            window.paymentChart.data.datasets[0].data = data.paymentStats.map(item => item.amount);
            window.paymentChart.update();
        }
    }

    /**
     * Update statistics cards
     */
    function updateStatistics(data) {
        // Update total sales
        updateStatCard('total-sales', data.salesStats.total_sales, data.salesStats.sales_growth);
        
        // Update new customers
        updateStatCard('new-customers', data.customerStats.new_customers, data.customerStats.customer_growth);
        
        // Update products sold
        updateStatCard('products-sold', data.salesStats.total_items, data.salesStats.items_growth);
        
        // Update average order
        updateStatCard('average-order', data.salesStats.average_order, data.salesStats.average_growth);
    }

    /**
     * Update a single statistics card
     */
    function updateStatCard(id, value, growth) {
        const card = document.getElementById(id);
        if (!card) return;

        const numberEl = card.querySelector('.stats-number');
        const changeEl = card.querySelector('.stats-change');

        if (numberEl) {
            numberEl.textContent = formatNumber(value);
        }

        if (changeEl) {
            const icon = growth >= 0 ? 'arrow-up' : 'arrow-down';
            const color = growth >= 0 ? 'text-success' : 'text-danger';
            changeEl.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${Math.abs(growth)}% from previous period
            `;
            changeEl.className = `stats-change ${color}`;
        }
    }

    /**
     * Update data tables
     */
    function updateTables(data) {
        // Update top products table
        updateTableContent('top-products', data.topProducts, product => `
            <tr>
                <td>${escapeHtml(product.name)}</td>
                <td>${formatNumber(product.quantity)}</td>
                <td>${formatCurrency(product.total_amount)}</td>
            </tr>
        `);

        // Update recent sales table
        updateTableContent('recent-sales', data.recentSales, sale => `
            <tr>
                <td>
                    <a href="${BASE_URL}/sales/view/${sale.id}">
                        ${escapeHtml(sale.invoice_number)}
                    </a>
                </td>
                <td>${escapeHtml(sale.customer_name)}</td>
                <td>${formatCurrency(sale.total_amount)}</td>
                <td>
                    <span class="badge badge-${sale.status_color}">
                        ${escapeHtml(sale.status)}
                    </span>
                </td>
            </tr>
        `);
    }

    /**
     * Update table content
     */
    function updateTableContent(id, data, rowTemplate) {
        const table = document.getElementById(id);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        tbody.innerHTML = data.map(rowTemplate).join('');
    }

    /**
     * Initialize table sorting
     */
    function initializeTableSorting() {
        document.querySelectorAll('table.sortable').forEach(table => {
            table.querySelectorAll('th[data-sort]').forEach(header => {
                header.addEventListener('click', () => {
                    const column = header.dataset.sort;
                    const direction = header.classList.contains('sort-asc') ? 'desc' : 'asc';
                    
                    // Remove sort classes from all headers
                    table.querySelectorAll('th').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                    });
                    
                    // Add sort class to clicked header
                    header.classList.add(`sort-${direction}`);
                    
                    // Sort table
                    sortTable(table, column, direction);
                });
            });
        });
    }

    /**
     * Sort table
     */
    function sortTable(table, column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`td[data-${column}]`).dataset[column];
            const bValue = b.querySelector(`td[data-${column}]`).dataset[column];
            
            if (direction === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });
        
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * Initialize notifications
     */
    function initializeNotifications() {
        // Handle notification clicks
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const link = this.querySelector('a');
                if (link) {
                    link.click();
                }
            });
        });
    }

    /**
     * Initialize export functionality
     */
    function initializeExport() {
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const format = this.dataset.format;
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                window.location.href = `${BASE_URL}/dashboard/export?format=${format}&start_date=${startDate}&end_date=${endDate}`;
            });
        });
    }

    /**
     * Setup auto-refresh timer
     */
    function setupRefreshTimer() {
        // Refresh dashboard data every 5 minutes
        setInterval(updateDashboardData, 5 * 60 * 1000);
    }

    /**
     * Format number with thousands separator
     */
    function formatNumber(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return DEFAULT_CURRENCY + ' ' + formatNumber(amount.toFixed(2));
    }

    /**
     * Escape HTML special characters
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
