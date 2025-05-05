<div class="col-lg-8">
    <!-- Products Selection -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Add Products</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="product_search" class="form-label">Search Products</label>
                    <div class="product-search-wrapper">
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
                <table class="table table-hover product-table" id="productsTable">
                    <thead class="table-light">
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
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <p class="mb-0">No products added yet</p>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
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
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="fas fa-sticky-note me-2"></i>Additional Notes</h5>
        </div>
        <div class="card-body">
            <textarea class="form-control" name="notes" rows="3" placeholder="Enter any additional notes or remarks"></textarea>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <!-- Customer Selection -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Customer Details</h5>
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
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0"><i class="fas fa-money-bill me-2"></i>Payment Details</h5>
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
