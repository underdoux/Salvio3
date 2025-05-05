// BPOM Module JavaScript

class BpomManager {
    constructor() {
        this.currentRegistrationNumber = '';
        this.productModal = null;
        this.searchTimeout = null;
        this.searchDelay = 500; // ms delay for search
        
        this.init();
    }

    init() {
        // Initialize modal
        const modalElement = document.getElementById('productModal');
        if (modalElement) {
            this.productModal = new bootstrap.Modal(modalElement);
        }

        // Initialize search handlers
        this.initializeSearch();
        
        // Initialize import form handlers
        this.initializeImport();
    }

    initializeSearch() {
        const searchForm = document.querySelector('.bpom-search form');
        const searchInput = document.querySelector('.bpom-search input[name="search"]');
        
        if (searchForm && searchInput) {
            // Handle real-time search
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    if (e.target.value.length >= 3) {
                        this.performSearch(e.target.value);
                    }
                }, this.searchDelay);
            });

            // Handle form submission
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch(searchInput.value);
            });
        }
    }

    initializeImport() {
        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleImport(importForm);
            });

            // File input validation
            const fileInput = importForm.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    this.validateFile(e.target);
                });
            }
        }
    }

    async performSearch(keyword, refresh = false) {
        if (!keyword) return;

        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;

        try {
            resultsContainer.classList.add('bpom-loading');
            
            const response = await fetch(`${APP_URL}/bpom/search?keyword=${encodeURIComponent(keyword)}&refresh=${refresh ? 1 : 0}`);
            const data = await response.json();

            if (data.success) {
                this.displayResults(data);
            } else {
                throw new Error(data.message || 'Search failed');
            }

        } catch (error) {
            this.showError('Search failed: ' + error.message);
        } finally {
            resultsContainer.classList.remove('bpom-loading');
        }
    }

    displayResults(data) {
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;

        if (data.data.length === 0) {
            resultsContainer.innerHTML = this.getEmptyResultsHtml();
            return;
        }

        resultsContainer.innerHTML = `
            ${this.getSourceBadgeHtml(data.source)}
            ${this.getResultsTableHtml(data.data)}
        `;

        // Re-initialize tooltips
        const tooltips = resultsContainer.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    }

    getSourceBadgeHtml(source) {
        const badges = {
            local: '<div class="source-badge local mb-3"><i class="fas fa-database"></i> Results from local database</div>',
            bpom: '<div class="source-badge bpom mb-3"><i class="fas fa-cloud-download-alt"></i> Results from BPOM website</div>'
        };
        return badges[source] || '';
    }

    getResultsTableHtml(products) {
        return `
            <div class="table-responsive">
                <table class="table table-hover bpom-table">
                    <thead>
                        <tr>
                            <th>Registration Number</th>
                            <th>Product Name</th>
                            <th>Form</th>
                            <th>Manufacturer</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${products.map(product => this.getProductRowHtml(product)).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    getProductRowHtml(product) {
        return `
            <tr>
                <td><span class="registration-number">${product.nomor_registrasi}</span></td>
                <td>${this.escapeHtml(product.nama_produk)}</td>
                <td>${this.escapeHtml(product.bentuk_sediaan || '-')}</td>
                <td>${this.escapeHtml(product.nama_pendaftar)}</td>
                <td>${this.escapeHtml(product.kategori || '-')}</td>
                <td>
                    <button type="button" 
                            class="btn btn-sm btn-info" 
                            onclick="bpomManager.showDetails('${product.nomor_registrasi}')"
                            data-bs-toggle="tooltip"
                            title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    getEmptyResultsHtml() {
        return `
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="h5 text-muted">No products found</p>
                <p class="text-muted">Try searching with a different keyword</p>
            </div>
        `;
    }

    async showDetails(registrationNumber) {
        this.currentRegistrationNumber = registrationNumber;
        
        // Reset modal state
        document.getElementById('productLoading').style.display = 'block';
        document.getElementById('productDetails').style.display = 'none';
        document.getElementById('productError').style.display = 'none';
        
        // Show modal
        this.productModal.show();
        
        // Load details
        await this.loadProductDetails(false);
    }

    async loadProductDetails(refresh = false) {
        if (!this.currentRegistrationNumber) return;

        const loadingElement = document.getElementById('productLoading');
        const detailsElement = document.getElementById('productDetails');
        const errorElement = document.getElementById('productError');

        try {
            loadingElement.style.display = 'block';
            detailsElement.style.display = 'none';
            errorElement.style.display = 'none';

            const response = await fetch(
                `${APP_URL}/bpom/getProduct?registration_number=${this.currentRegistrationNumber}&refresh=${refresh ? 1 : 0}`
            );
            const data = await response.json();

            if (data.success) {
                this.displayProductDetails(data.data);
            } else {
                throw new Error(data.message || 'Failed to load product details');
            }

        } catch (error) {
            loadingElement.style.display = 'none';
            errorElement.style.display = 'block';
            errorElement.textContent = error.message;
        }
    }

    displayProductDetails(product) {
        // Update each field
        Object.keys(product).forEach(key => {
            const element = document.getElementById(`detail_${key}`);
            if (element) {
                element.textContent = product[key] || '-';
            }
        });

        // Show details
        document.getElementById('productLoading').style.display = 'none';
        document.getElementById('productDetails').style.display = 'block';
    }

    validateFile(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        // Check file type
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showError('Please select a CSV file');
            fileInput.value = '';
            return;
        }

        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('File size must be less than 5MB');
            fileInput.value = '';
            return;
        }
    }

    async handleImport(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        try {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                form.reset();
            } else {
                throw new Error(data.message || 'Import failed');
            }

        } catch (error) {
            this.showError('Import failed: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-file-import"></i> Import Data';
        }
    }

    showError(message) {
        const alert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        this.showAlert(alert);
    }

    showSuccess(message) {
        const alert = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        this.showAlert(alert);
    }

    showAlert(alertHtml) {
        const alertContainer = document.querySelector('.alert-container');
        if (alertContainer) {
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }
            }, 5000);
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Initialize BPOM manager when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.bpomManager = new BpomManager();
});
