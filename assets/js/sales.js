// Sales Management JavaScript

let products = [];
let formatter;

// Initialize sales page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize currency formatter
    formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: APP_CURRENCY
    });

    // Initialize Select2
    $('#product_search').select2({
        placeholder: 'Search for products...',
        allowClear: true,
        width: '100%'
    });

    $('#customer_id').select2({
        placeholder: 'Select customer...',
        allowClear: true,
        width: '100%'
    });

    // Initialize DateRangePicker
    $('input[name="date_range"]').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        }
    });

    $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});

// Product Management
function addProduct() {
    const select = document.getElementById('product_search');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        showAlert('Please select a product', 'warning');
        return;
    }

    const quantity = parseInt(document.getElementById('quantity').value);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const stock = parseInt(option.dataset.stock);

    if (quantity < 1) {
        showAlert('Quantity must be at least 1', 'warning');
        return;
    }

    if (quantity > stock) {
        showAlert(`Only ${stock} items available in stock`, 'warning');
        return;
    }

    const product = {
        id: option.value,
        name: option.dataset.name,
        sku: option.dataset.sku,
        price: parseFloat(option.dataset.price),
        quantity: quantity,
        discount: discount,
        total: (quantity * option.dataset.price) - discount
    };

    // Check if product already exists
    const existingIndex = products.findIndex(p => p.id === product.id);
    if (existingIndex !== -1) {
        if (products[existingIndex].quantity + quantity > stock) {
            showAlert(`Cannot add more items. Only ${stock} available in stock`, 'warning');
            return;
        }
        products[existingIndex].quantity += quantity;
        products[existingIndex].discount += discount;
        products[existingIndex].total = (products[existingIndex].quantity * products[existingIndex].price) - products[existingIndex].discount;
    } else {
        products.push(product);
    }

    updateTable();
    resetForm();
}

function removeProduct(index) {
    products.splice(index, 1);
    updateTable();
}

function updateTable() {
    const tbody = document.querySelector('#productsTable tbody');
    const noProducts = document.getElementById('noProducts');
    
    if (products.length === 0) {
        noProducts.style.display = '';
        document.getElementById('submitButton').disabled = true;
    } else {
        noProducts.style.display = 'none';
        document.getElementById('submitButton').disabled = false;
    }

    // Clear existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    // Add product rows
    let subtotal = 0;
    let totalDiscount = 0;

    products.forEach((product, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-bold">${product.name}</div>
                <small class="text-muted">${product.sku}</small>
            </td>
            <td>${product.quantity}</td>
            <td class="text-end">${formatter.format(product.price)}</td>
            <td class="text-end text-success">-${formatter.format(product.discount)}</td>
            <td class="text-end">${formatter.format(product.total)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        subtotal += product.quantity * product.price;
        totalDiscount += product.discount;
    });

    const finalTotal = subtotal - totalDiscount;

    // Update totals
    document.getElementById('subtotal').textContent = formatter.format(subtotal);
    document.getElementById('totalDiscount').textContent = formatter.format(totalDiscount);
    document.getElementById('finalTotal').textContent = formatter.format(finalTotal);

    // Update hidden inputs
    document.getElementById('inputTotalAmount').value = subtotal;
    document.getElementById('inputDiscountAmount').value = totalDiscount;
    document.getElementById('inputFinalAmount').value = finalTotal;

    // Update items JSON
    document.getElementById('saleItems').value = JSON.stringify(products.map(p => ({
        product_id: p.id,
        quantity: p.quantity,
        unit_price: p.price,
        discount_amount: p.discount,
        total_amount: p.total
    })));
}

function resetForm() {
    document.getElementById('product_search').value = '';
    document.getElementById('quantity').value = '1';
    document.getElementById('discount').value = '0';
    $('#product_search').trigger('change');
}

// Customer Management
async function refreshCustomers() {
    try {
        const response = await fetch(`${APP_URL}/customers/getActive`);
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('customer_id');
            const currentValue = select.value;
            
            // Clear existing options
            select.innerHTML = '<option value="">Choose Customer</option>';
            
            // Add new options
            data.customers.forEach(customer => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = customer.name + (customer.phone ? ` (${customer.phone})` : '');
                select.appendChild(option);
            });
            
            // Restore selected value if it still exists
            select.value = currentValue;
            $('#customer_id').trigger('change');
            
            showAlert('Customer list refreshed successfully', 'success');
        }
    } catch (error) {
        showAlert('Error refreshing customer list', 'error');
    }
}

// Form Validation
function validateSaleForm(e) {
    if (products.length === 0) {
        e.preventDefault();
        showAlert('Please add at least one product', 'warning');
        return false;
    }

    const customer = document.getElementById('customer_id').value;
    if (!customer) {
        e.preventDefault();
        showAlert('Please select a customer', 'warning');
        return false;
    }

    return true;
}

// Utility Functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.flash-message');
    if (container) {
        container.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}
