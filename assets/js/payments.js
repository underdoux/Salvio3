// Payments Module JavaScript

class PaymentManager {
    constructor() {
        this.paymentModal = null;
        this.reminderModal = null;
        this.bankAccountModal = null;
        this.currentInstallment = null;
        this.dateRangePicker = null;
        
        this.init();
    }

    init() {
        // Initialize modals
        this.initializeModals();
        
        // Initialize payment method selection
        this.initializePaymentMethod();
        
        // Initialize date range picker
        this.initializeDateRange();
        
        // Initialize form validation
        this.initializeValidation();
    }

    initializeModals() {
        // Payment Modal
        const paymentModalElement = document.getElementById('paymentModal');
        if (paymentModalElement) {
            this.paymentModal = new bootstrap.Modal(paymentModalElement);
            
            // Handle payment method change
            const paymentMethodSelect = document.getElementById('payment_method');
            const bankDetailsDiv = document.querySelector('.bank-details');
            
            if (paymentMethodSelect && bankDetailsDiv) {
                paymentMethodSelect.addEventListener('change', () => {
                    bankDetailsDiv.style.display = 
                        paymentMethodSelect.value === 'bank' ? 'block' : 'none';
                    
                    const bankAccountSelect = document.getElementById('bank_account_id');
                    if (bankAccountSelect) {
                        bankAccountSelect.required = paymentMethodSelect.value === 'bank';
                    }
                });
            }
        }

        // Reminder Modal
        const reminderModalElement = document.getElementById('reminderModal');
        if (reminderModalElement) {
            this.reminderModal = new bootstrap.Modal(reminderModalElement);
        }

        // Bank Account Modal
        const bankAccountModalElement = document.getElementById('bankAccountModal');
        if (bankAccountModalElement) {
            this.bankAccountModal = new bootstrap.Modal(bankAccountModalElement);
        }
    }

    initializePaymentMethod() {
        const paymentMethodOptions = document.querySelectorAll('.payment-method-option');
        if (paymentMethodOptions.length) {
            paymentMethodOptions.forEach(option => {
                option.addEventListener('click', () => {
                    // Remove selected class from all options
                    paymentMethodOptions.forEach(opt => 
                        opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    option.classList.add('selected');
                    
                    // Update hidden input
                    const methodInput = document.getElementById('payment_method');
                    if (methodInput) {
                        methodInput.value = option.dataset.method;
                    }
                    
                    // Show/hide bank details
                    this.toggleBankDetails(option.dataset.method === 'bank');
                });
            });
        }
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

    showPaymentModal(installment) {
        this.currentInstallment = installment;
        
        // Update modal fields
        document.getElementById('installment_id').value = installment.id;
        document.getElementById('invoice_number').value = installment.invoice_number;
        document.getElementById('customer_name').value = installment.customer_name;
        document.getElementById('amount').value = this.formatCurrency(installment.amount);
        
        // Show modal
        this.paymentModal.show();
    }

    showReminderModal(installment) {
        this.currentInstallment = installment;
        
        // Update customer info
        document.getElementById('reminder_customer_name').value = installment.customer_name;
        
        // Setup contact methods
        const contactMethods = document.getElementById('contact_methods');
        let contactHtml = '';
        
        if (installment.customer_email) {
            contactHtml += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="email" id="email_reminder" checked>
                    <label class="form-check-label" for="email_reminder">
                        Send Email to ${installment.customer_email}
                    </label>
                </div>
            `;
        }
        
        if (installment.customer_phone) {
            contactHtml += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="sms" id="sms_reminder" checked>
                    <label class="form-check-label" for="sms_reminder">
                        Send SMS to ${installment.customer_phone}
                    </label>
                </div>
            `;
        }
        
        contactMethods.innerHTML = contactHtml;

        // Setup payment details
        const paymentDetails = document.getElementById('payment_details');
        paymentDetails.innerHTML = `
            <div class="mb-2">
                <strong>Invoice:</strong> ${installment.invoice_number}
            </div>
            <div class="mb-2">
                <strong>Amount:</strong> ${this.formatCurrency(installment.amount)}
            </div>
            <div>
                <strong>Due Date:</strong> ${this.formatDate(installment.due_date)}
            </div>
        `;
        
        // Show modal
        this.reminderModal.show();
    }

    showBankAccountModal(account = null) {
        const form = document.getElementById('bankAccountForm');
        const title = document.getElementById('modalTitle');
        
        if (account) {
            // Edit mode
            title.textContent = 'Edit Bank Account';
            form.action = `${APP_URL}/payments/updateBankAccount/${account.id}`;
            
            // Fill form fields
            Object.keys(account).forEach(key => {
                const input = document.getElementById(key);
                if (input) {
                    input.value = account[key];
                }
            });
        } else {
            // Create mode
            title.textContent = 'Add Bank Account';
            form.action = `${APP_URL}/payments/createBankAccount`;
            form.reset();
        }
        
        this.bankAccountModal.show();
    }

    toggleBankDetails(show) {
        const bankDetails = document.querySelector('.bank-details');
        if (bankDetails) {
            bankDetails.style.display = show ? 'block' : 'none';
            
            const bankAccountSelect = document.getElementById('bank_account_id');
            if (bankAccountSelect) {
                bankAccountSelect.required = show;
            }
        }
    }

    sendReminder() {
        if (!this.currentInstallment) return;
        
        const emailReminder = document.getElementById('email_reminder');
        const smsReminder = document.getElementById('sms_reminder');
        
        const methods = [];
        if (emailReminder && emailReminder.checked) methods.push('email');
        if (smsReminder && smsReminder.checked) methods.push('sms');
        
        if (methods.length === 0) {
            this.showAlert('Please select at least one reminder method', 'warning');
            return;
        }
        
        // TODO: Implement reminder sending functionality
        this.showAlert('Reminder functionality will be implemented in the notification module', 'info');
        this.reminderModal.hide();
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: APP_CURRENCY
        }).format(amount);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
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

// Initialize payment manager when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.paymentManager = new PaymentManager();
});
