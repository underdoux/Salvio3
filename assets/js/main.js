// Flash Message Handler
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelector('.flash-messages');
    if (flashMessages) {
        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const alerts = flashMessages.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    }
});

// Form Validation Helper
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const errors = {};

    // Clear previous errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    // Validate each field
    Object.keys(rules).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        const fieldRules = rules[fieldName];
        const value = field.value.trim();

        // Required check
        if (fieldRules.required && !value) {
            errors[fieldName] = `${fieldRules.label || fieldName} is required`;
            isValid = false;
        }

        // Minimum length check
        if (fieldRules.minLength && value.length < fieldRules.minLength) {
            errors[fieldName] = `${fieldRules.label || fieldName} must be at least ${fieldRules.minLength} characters`;
            isValid = false;
        }

        // Maximum length check
        if (fieldRules.maxLength && value.length > fieldRules.maxLength) {
            errors[fieldName] = `${fieldRules.label || fieldName} must not exceed ${fieldRules.maxLength} characters`;
            isValid = false;
        }

        // Pattern check
        if (fieldRules.pattern && !fieldRules.pattern.test(value)) {
            errors[fieldName] = fieldRules.message || `${fieldRules.label || fieldName} is invalid`;
            isValid = false;
        }

        // Custom validation
        if (fieldRules.validate && !fieldRules.validate(value)) {
            errors[fieldName] = fieldRules.message || `${fieldRules.label || fieldName} is invalid`;
            isValid = false;
        }

        // Display error if any
        if (errors[fieldName]) {
            field.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = errors[fieldName];
            field.parentNode.appendChild(feedback);
        }
    });

    return isValid;
}

// AJAX Helper
async function fetchApi(url, options = {}) {
    try {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'An error occurred');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Debounce Helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Password Toggle Helper
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Confirmation Dialog Helper
function confirmAction(message, callback) {
    if (window.confirm(message)) {
        callback();
    }
}

// Format currency helper
function formatCurrency(amount, currency = 'IDR') {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Date formatter helper
function formatDate(date, format = 'long') {
    const options = {
        short: { day: 'numeric', month: 'short', year: 'numeric' },
        long: { day: 'numeric', month: 'long', year: 'numeric' },
        withTime: { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    };
    
    return new Date(date).toLocaleDateString('id-ID', options[format]);
}
