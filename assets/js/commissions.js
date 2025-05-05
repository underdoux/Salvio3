// Commissions & Pricing Module JavaScript

class CommissionManager {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        // Initialize date range picker
        this.initializeDateRange();
        
        // Initialize commission charts
        this.initializeCharts();
        
        // Initialize form validation
        this.initializeValidation();
        
        // Initialize commission payment processing
        this.initializePaymentProcessing();
    }

    initializeDateRange() {
        const dateRange = document.querySelector('input[name="date_range"]');
        if (dateRange) {
            this.dateRangePicker = new DateRangePicker(dateRange, {
                format: 'yyyy-mm-dd',
                maxDate: new Date(),
                autoclose: true,
                todayHighlight: true
            });
        }
    }

    initializeCharts() {
        // Commission Trends Chart
        const trendsCanvas = document.getElementById('commissionTrends');
        if (trendsCanvas) {
            this.charts.trends = new Chart(trendsCanvas, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Commission Amount',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Commission Distribution Chart
        const distributionCanvas = document.getElementById('commissionDistribution');
        if (distributionCanvas) {
            this.charts.distribution = new Chart(distributionCanvas, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }

    updateCharts(data) {
        if (this.charts.trends) {
            this.charts.trends.data.labels = data.trends.labels;
            this.charts.trends.data.datasets[0].data = data.trends.values;
            this.charts.trends.update();
        }

        if (this.charts.distribution) {
            this.charts.distribution.data.labels = data.distribution.labels;
            this.charts.distribution.data.datasets[0].data = data.distribution.values;
            this.charts.distribution.update();
        }
    }

    initializeValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    initializePaymentProcessing() {
        const processButton = document.getElementById('processCommissions');
        if (processButton) {
            processButton.addEventListener('click', () => this.processSelectedCommissions());
        }

        // Handle select all checkbox
        const selectAll = document.getElementById('selectAllCommissions');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const checkboxes = document.querySelectorAll('.commission-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                this.updateSelectedTotal();
            });
        }

        // Handle individual checkboxes
        const checkboxes = document.querySelectorAll('.commission-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateSelectedTotal());
        });
    }

    processSelectedCommissions() {
        const selectedIds = Array.from(document.querySelectorAll('.commission-checkbox:checked'))
            .map(checkbox => checkbox.value);

        if (selectedIds.length === 0) {
            this.showAlert('Please select commissions to process', 'warning');
            return;
        }

        const form = document.getElementById('processCommissionsForm');
        const idsInput = document.createElement('input');
        idsInput.type = 'hidden';
        idsInput.name = 'commission_ids';
        idsInput.value = selectedIds.join(',');
        form.appendChild(idsInput);
        form.submit();
    }

    updateSelectedTotal() {
        const selectedCheckboxes = document.querySelectorAll('.commission-checkbox:checked');
        const total = Array.from(selectedCheckboxes)
            .reduce((sum, checkbox) => sum + parseFloat(checkbox.dataset.amount), 0);
        
        const totalElement = document.getElementById('selectedCommissionTotal');
        if (totalElement) {
            totalElement.textContent = this.formatCurrency(total);
        }
    }

    async getCommissionDetails(saleId) {
        try {
            const response = await fetch(`${APP_URL}/commissions/getDetails?sale_id=${saleId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showCommissionDetails(data.data);
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to load commission details', 'error');
        }
    }

    showCommissionDetails(details) {
        const modal = new bootstrap.Modal(document.getElementById('commissionDetailsModal'));
        const content = document.getElementById('commissionDetailsContent');
        
        let html = `
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        details.forEach(detail => {
            html += `
                <tr>
                    <td>${detail.rate_type}</td>
                    <td>${detail.rate}%</td>
                    <td>${this.formatCurrency(detail.commission_amount)}</td>
                    <td>
                        <span class="status-badge ${detail.status}">
                            ${detail.status}
                        </span>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        content.innerHTML = html;
        modal.show();
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: APP_CURRENCY
        }).format(amount);
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.alert-container');
        if (container) {
            container.appendChild(alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
}

// Initialize commission manager when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.commissionManager = new CommissionManager();
});
