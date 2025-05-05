<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bank Accounts</h1>
        <div class="btn-group">
            <a href="<?= url('payments/overdue') ?>" class="btn btn-outline-danger">
                <i class="fas fa-exclamation-circle"></i> Overdue Installments
            </a>
            <a href="<?= url('payments/upcoming') ?>" class="btn btn-outline-primary">
                <i class="fas fa-calendar"></i> Upcoming Installments
            </a>
            <?php if ($this->isAdmin()): ?>
                <button type="button" class="btn btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i> Add Bank Account
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= url('payments/bankAccounts') ?>" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" 
                           name="start_date" 
                           class="form-control" 
                           value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" 
                           name="end_date" 
                           class="form-control" 
                           value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                        <a href="<?= url('payments/bankAccounts') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bank Accounts -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($bankAccounts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-university fa-3x text-muted mb-3"></i>
                    <p class="h5 text-muted">No bank accounts found</p>
                    <?php if ($this->isAdmin()): ?>
                        <button type="button" class="btn btn-primary mt-3" onclick="showCreateModal()">
                            <i class="fas fa-plus"></i> Add Bank Account
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bank</th>
                                <th>Account Number</th>
                                <th>Account Name</th>
                                <th>Branch</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-end">Total Amount</th>
                                <th>Status</th>
                                <?php if ($this->isAdmin()): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $account): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($account['bank_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="font-monospace"><?= htmlspecialchars($account['account_number']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                                    <td><?= htmlspecialchars($account['branch'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?= number_format($account['transaction_count']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= formatCurrency($account['total_amount']) ?>
                                    </td>
                                    <td>
                                        <?php if ($account['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($this->isAdmin()): ?>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-info"
                                                        onclick="showEditModal(<?= htmlspecialchars(json_encode($account)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="toggleStatus(<?= $account['id'] ?>, '<?= $account['status'] ?>')">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="bankAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bankAccountForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="account_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <input type="text" 
                               class="form-control" 
                               name="bank_name" 
                               id="bank_name" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="account_number" class="form-label">Account Number</label>
                        <input type="text" 
                               class="form-control" 
                               name="account_number" 
                               id="account_number" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name</label>
                        <input type="text" 
                               class="form-control" 
                               name="account_name" 
                               id="account_name" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="branch" class="form-label">Branch</label>
                        <input type="text" 
                               class="form-control" 
                               name="branch" 
                               id="branch">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const bankAccountModal = new bootstrap.Modal(document.getElementById('bankAccountModal'));
const bankAccountForm = document.getElementById('bankAccountForm');

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Bank Account';
    bankAccountForm.reset();
    bankAccountForm.action = '<?= url('payments/createBankAccount') ?>';
    bankAccountModal.show();
}

function showEditModal(account) {
    document.getElementById('modalTitle').textContent = 'Edit Bank Account';
    document.getElementById('account_id').value = account.id;
    document.getElementById('bank_name').value = account.bank_name;
    document.getElementById('account_number').value = account.account_number;
    document.getElementById('account_name').value = account.account_name;
    document.getElementById('branch').value = account.branch || '';
    document.getElementById('status').value = account.status;
    
    bankAccountForm.action = '<?= url('payments/updateBankAccount/') ?>' + account.id;
    bankAccountModal.show();
}

function toggleStatus(id, currentStatus) {
    if (!confirm('Are you sure you want to ' + 
        (currentStatus === 'active' ? 'deactivate' : 'activate') + 
        ' this bank account?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('payments/updateBankAccount/') ?>' + id;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= $csrfToken ?>';
    form.appendChild(csrfInput);

    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = currentStatus === 'active' ? 'inactive' : 'active';
    form.appendChild(statusInput);

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
