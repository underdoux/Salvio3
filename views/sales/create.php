<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <form id="saleForm" action="<?= url('sales/store') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="items" id="saleItems">

        <div class="row">
            <div class="col-lg-8">
                <!-- Products Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Add Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="product_search" class="form-label">Search Products</label>
                                <select class="form-select" id="product_search">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>" 
                                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-sku="<?= htmlspecialchars($product['sku']) ?>"
                                                data-price="<?= $product['selling_price'] ?>"
                                                data-stock="<?= $product['stock'] ?>">
                                            <?= htmlspecialchars($product['name']) ?> 
                                            (<?= htmlspecialchars($product['sku']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" min="1" value="1">
                            </div>
                            <div class="col-md-2">
                                <label for="discount" class="form-label">Discount</label>
                                <input type="number" class="form-control" id="discount" min="0" value="0" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary w-100" onclick="addProduct()">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="noProducts">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No products added yet
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end fw-bold">Subtotal:</td>
                                        <td class="text-end fw-bold" id="subtotal">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end fw-bold">Total Discount:</td>
                                        <td class="text-end fw-bold text-success" id="totalDiscount">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end fw-bold">Final Total:</td>
                                        <td class="text-end fw-bold" id="finalTotal">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" placeholder="Enter any additional notes or remarks"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Customer Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Select Customer</label>
                            <select class="form-select" name="customer_id" id="customer_id" required>
                                <option value="">Choose Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>">
                                        <?= htmlspecialchars($customer['name']) ?>
                                        <?php if (!empty($customer['phone'])): ?>
                                            (<?= htmlspecialchars($customer['phone']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-center">
                            <a href="<?= url('customers/create') ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-user-plus"></i> New Customer
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshCustomers()">
                                <i class="fas fa-sync"></i> Refresh List
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="total_amount" id="inputTotalAmount">
                        <input type="hidden" name="discount_amount" id="inputDiscountAmount">
                        <input type="hidden" name="final_amount" id="inputFinalAmount">

                        <div class="mb-3">
                            <label for="payment_type" class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_type" id="payment_type" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="installment">Installment</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" name="payment_status" id="payment_status" required>
                                <option value="paid">Paid</option>
                                <option value="partial">Partially Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitButton" disabled>
                                <i class="fas fa-save"></i> Complete Sale
                            </button>
                            <a href="<?= url('sales') ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let products = [];
const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: '<?= APP_CURRENCY ?>'
});

// Initialize select2 for product search
$(document).ready(function() {
    $('#product_search').select2({
        placeholder: 'Search for products...',
        allowClear: true
    });

    $('#customer_id').select2({
        placeholder: 'Select customer...',
        allowClear: true
    });
});

function addProduct() {
    const select = document.getElementById('product_search');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        alert('Please select a product');
        return;
    }

    const quantity = parseInt(document.getElementById('quantity').value);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const stock = parseInt(option.dataset.stock);

    if (quantity < 1) {
        alert('Quantity must be at least 1');
        return;
    }

    if (quantity > stock) {
        alert(`Only ${stock} items available in stock`);
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
            alert(`Cannot add more items. Only ${stock} available in stock`);
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

async function refreshCustomers() {
    try {
        const response = await fetch('<?= url('customers/getActive') ?>');
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
        }
    } catch (error) {
        alert('Error refreshing customer list');
    }
}

// Form validation
document.getElementById('saleForm').addEventListener('submit', function(e) {
    if (products.length === 0) {
        e.preventDefault();
        alert('Please add at least one product');
        return;
    }

    const customer = document.getElementById('customer_id').value;
    if (!customer) {
        e.preventDefault();
        alert('Please select a customer');
        return;
    }
});
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
