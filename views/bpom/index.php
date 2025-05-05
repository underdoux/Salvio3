<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">BPOM Database</h1>
        <?php if ($this->isAdmin()): ?>
            <a href="<?= url('bpom/import') ?>" class="btn btn-primary">
                <i class="fas fa-file-import"></i> Import Data
            </a>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-database fa-fw text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Total Records</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_records']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="fas fa-exclamation-circle fa-fw text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Expired</h6>
                            <h3 class="mb-0"><?= number_format($stats['expired_count']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-clock fa-fw text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Expiring Soon</h6>
                            <h3 class="mb-0"><?= number_format($stats['expiring_soon_count']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-sync fa-fw text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Last Updated</h6>
                            <h3 class="mb-0"><?= formatDate($stats['latest_record'], 'd M') ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Search BPOM Database</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="registration_number" class="form-label">Registration Number</label>
                            <input type="text" class="form-control" id="registration_number" placeholder="Enter BPOM registration number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-primary w-100" onclick="searchBPOM()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div id="searchResults" class="mt-4" style="display: none;">
                        <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Searching BPOM database...</p>
                        </div>
                        <div id="resultContent"></div>
                    </div>
                </div>
            </div>

            <!-- Search Tips -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Search Tips</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-info fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Registration Format</h6>
                                    <p class="text-muted mb-0">Enter the complete BPOM registration number without spaces or special characters.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-history text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Local Database</h6>
                                    <p class="text-muted mb-0">Results are first checked in our local database before querying BPOM website.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Expiring Registrations -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Expiring Registrations</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($expiringRegistrations)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="text-muted mb-0">No registrations expiring soon</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Registration</th>
                                        <th>Expires</th>
                                        <th>Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiringRegistrations as $reg): ?>
                                        <tr>
                                            <td>
                                                <div class="small fw-bold"><?= htmlspecialchars($reg['registration_number']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($reg['product_name']) ?></div>
                                            </td>
                                            <td><?= formatDate($reg['expired_date']) ?></td>
                                            <td>
                                                <?php
                                                $daysClass = match(true) {
                                                    $reg['days_remaining'] < 0 => 'danger',
                                                    $reg['days_remaining'] < 30 => 'warning',
                                                    default => 'info'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $daysClass ?>">
                                                    <?= $reg['days_remaining'] < 0 
                                                        ? 'Expired' 
                                                        : $reg['days_remaining'] . ' days' ?>
                                                </span>
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
    </div>
</div>

<script>
async function searchBPOM() {
    const registrationNumber = document.getElementById('registration_number').value.trim();
    if (!registrationNumber) {
        alert('Please enter a BPOM registration number');
        return;
    }

    const resultsDiv = document.getElementById('searchResults');
    const loadingDiv = document.getElementById('loadingIndicator');
    const contentDiv = document.getElementById('resultContent');

    resultsDiv.style.display = 'block';
    loadingDiv.style.display = 'block';
    contentDiv.innerHTML = '';

    try {
        const response = await fetch(`<?= url('bpom/search') ?>?id=${encodeURIComponent(registrationNumber)}`);
        const data = await response.json();

        if (data.success) {
            contentDiv.innerHTML = `
                <div class="alert alert-success">
                    <div class="d-flex align-items-center mb-3">
                        <div>
                            <h5 class="alert-heading mb-1">Product Found</h5>
                            <p class="mb-0 text-muted small">Source: ${data.source === 'local' ? 'Local Database' : 'BPOM Website'}</p>
                        </div>
                        <button type="button" class="btn btn-success btn-sm ms-auto" onclick="useData(${JSON.stringify(data.data)})">
                            Use This Data
                        </button>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Product Name:</strong></p>
                            <p class="mb-0">${data.data.product_name}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Registration:</strong></p>
                            <p class="mb-0">${data.data.registration_number}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Manufacturer:</strong></p>
                            <p class="mb-0">${data.data.manufacturer || '-'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Category:</strong></p>
                            <p class="mb-0">${data.data.category || '-'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Issue Date:</strong></p>
                            <p class="mb-0">${data.data.issued_date || '-'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Expiry Date:</strong></p>
                            <p class="mb-0">${data.data.expired_date || '-'}</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-warning">
                    <h5 class="alert-heading">No Results Found</h5>
                    <p class="mb-0">${data.message}</p>
                </div>
            `;
        }
    } catch (error) {
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5 class="alert-heading">Error</h5>
                <p class="mb-0">Failed to search BPOM database. Please try again later.</p>
            </div>
        `;
    } finally {
        loadingDiv.style.display = 'none';
    }
}

function useData(data) {
    // This function will be implemented based on where the BPOM search is being used
    // For example, in product creation/edit forms
    if (window.opener && window.opener.applyBPOMData) {
        window.opener.applyBPOMData(data);
        window.close();
    } else {
        alert('No parent window found to apply the data.');
    }
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
