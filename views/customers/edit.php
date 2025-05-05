<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Customer: <?= htmlspecialchars($customer['name']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('customers/update/' . $customer['id']) ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>">
                                <small class="text-muted">For sending invoices and notifications</small>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
                                <small class="text-muted">For order updates and reminders</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                            <small class="text-muted">Complete delivery address</small>
                        </div>

                        <!-- Additional Information -->
                        <div class="mb-3">
                            <label class="form-label d-block">Preferred Contact Method</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_email" value="email" 
                                       <?= ($customer['contact_preference'] ?? 'email') === 'email' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="contact_email">Email</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_phone" value="phone"
                                       <?= ($customer['contact_preference'] ?? '') === 'phone' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="contact_phone">Phone</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_whatsapp" value="whatsapp"
                                       <?= ($customer['contact_preference'] ?? '') === 'whatsapp' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="contact_whatsapp">WhatsApp</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="marketing_consent" name="marketing_consent" value="1"
                                       <?= ($customer['marketing_consent'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="marketing_consent">
                                    Customer agrees to receive marketing communications
                                </label>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <hr>

                        <div class="text-end">
                            <a href="<?= url('customers') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Customer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Customer Statistics -->
            <?php if (!empty($customer['sales_history'])): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h3 class="mb-0"><?= number_format(count($customer['sales_history'])) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Total Spent</h6>
                                <h3 class="mb-0"><?= formatCurrency(array_sum(array_column($customer['sales_history'], 'final_amount'))) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-1">Average Order Value</h6>
                                <h3 class="mb-0">
                                    <?= formatCurrency(
                                        count($customer['sales_history']) > 0 
                                            ? array_sum(array_column($customer['sales_history'], 'final_amount')) / count($customer['sales_history'])
                                            : 0
                                    ) ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Phone number formatting
document.getElementById('phone').addEventListener('input', function(e) {
    let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
    e.target.value = !x[2] ? x[1] : !x[3] ? `${x[1]}-${x[2]}` : `${x[1]}-${x[2]}-${x[3]}`;
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    
    if (!email && !phone) {
        e.preventDefault();
        alert('Please provide either an email address or phone number for contact purposes.');
    }
});
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
