<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Upcoming Installments</h1>
        <div class="btn-group">
            <a href="<?= url('payments/overdue') ?>" class="btn btn-outline-danger">
                <i class="fas fa-exclamation-circle"></i> Overdue Installments
            </a>
            <a href="<?= url('payments/bankAccounts') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-university"></i> Bank Accounts
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= url('payments/upcoming') ?>" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label">Show installments due within</label>
                </div>
                <div class="col-auto">
                    <select name="days" class="form-select" onchange="this.form.submit()">
                        <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 days</option>
                        <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 days</option>
                        <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 days</option>
                        <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 days</option>
                        <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 days</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Upcoming Installments -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($installments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No upcoming installments</p>
                    <p class="text-muted">No installments due within the next <?= $days ?> days</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Days Until Due</th>
                                <th class="text-end">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($installments as $installment): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('sales/view/' . $installment['sale_id']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($installment['invoice_number']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($installment['customer_name']) ?></div>
                                        <?php if ($installment['customer_phone']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($installment['customer_phone']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($installment['customer_email']): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($installment['customer_email']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= formatDate($installment['due_date']) ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = $installment['days_until_due'] <= 3 ? 'bg-danger' :
                                            ($installment['days_until_due'] <= 7 ? 'bg-warning' : 'bg-info');
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $installment['days_until_due'] ?> days
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= formatCurrency($installment['amount']) ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    onclick="showPaymentModal(<?= htmlspecialchars(json_encode($installment)) ?>)">
                                                <i class="fas fa-money-bill"></i> Record Payment
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="showReminderModal(<?= htmlspecialchars(json_encode($installment)) ?>)">
                                                <i class="fas fa-bell"></i> Send Reminder
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('payments/processInstallment') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="installment_id" id="installment_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" class="form-control" id="invoice_number" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input type="text" class="form-control" id="customer_name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="text" class="form-control" id="amount" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" id="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="mb-3 bank-details" style="display: none;">
                        <label for="bank_account_id" class="form-label">Bank Account</label>
                        <select class="form-select" name="bank_account_id" id="bank_account_id">
                            <option value="">Select Bank Account</option>
                            <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= htmlspecialchars($account['bank_name']) ?> - 
                                    <?= htmlspecialchars($account['account_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" 
                               class="form-control" 
                               name="payment_date" 
                               value="<?= date('Y-m-d') ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" 
                               class="form-control" 
                               name="reference_number" 
                               placeholder="Optional">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" 
                                  name="notes" 
                                  rows="2" 
                                  placeholder="Optional"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reminder Modal -->
<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Payment Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" id="reminder_customer_name" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Methods</label>
                    <div id="contact_methods"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Details</label>
                    <div id="payment_details" class="border rounded p-3 bg-light"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="sendReminder()">Send Reminder</button>
            </div>
        </div>
    </div>
</div>

<script>
const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
const reminderModal = new bootstrap.Modal(document.getElementById('reminderModal'));
const paymentMethodSelect = document.getElementById('payment_method');
const bankDetailsDiv = document.querySelector('.bank-details');

function showPaymentModal(installment) {
    document.getElementById('installment_id').value = installment.id;
    document.getElementById('invoice_number').value = installment.invoice_number;
    document.getElementById('customer_name').value = installment.customer_name;
    document.getElementById('amount').value = formatCurrency(installment.amount);
    paymentModal.show();
}

function showReminderModal(installment) {
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
            <strong>Amount:</strong> ${formatCurrency(installment.amount)}
        </div>
        <div>
            <strong>Due Date:</strong> ${formatDate(installment.due_date)}
        </div>
    `;
    
    reminderModal.show();
}

function sendReminder() {
    // TODO: Implement reminder sending functionality
    alert('Reminder functionality will be implemented in the notification module');
    reminderModal.hide();
}

paymentMethodSelect.addEventListener('change', function() {
    bankDetailsDiv.style.display = this.value === 'bank' ? 'block' : 'none';
    const bankAccountSelect = document.getElementById('bank_account_id');
    bankAccountSelect.required = this.value === 'bank';
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: '<?= APP_CURRENCY ?>'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
