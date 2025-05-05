<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create New Customer</h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('customers/store') ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <small class="text-muted">For sending invoices and notifications</small>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                                <small class="text-muted">For order updates and reminders</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            <small class="text-muted">Complete delivery address</small>
                        </div>

                        <!-- Additional Information -->
                        <div class="mb-3">
                            <label class="form-label d-block">Preferred Contact Method</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_email" value="email" checked>
                                <label class="form-check-label" for="contact_email">Email</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_phone" value="phone">
                                <label class="form-check-label" for="contact_phone">Phone</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="contact_preference" id="contact_whatsapp" value="whatsapp">
                                <label class="form-check-label" for="contact_whatsapp">WhatsApp</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="marketing_consent" name="marketing_consent" value="1">
                                <label class="form-check-label" for="marketing_consent">
                                    Customer agrees to receive marketing communications
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="text-end">
                            <a href="<?= url('customers') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Customer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-info fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Customer Information</h6>
                                    <p class="text-muted mb-0">Collect accurate contact information to ensure smooth communication and delivery.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt text-success fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Data Privacy</h6>
                                    <p class="text-muted mb-0">Ensure customer consent before storing and using their personal information.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
