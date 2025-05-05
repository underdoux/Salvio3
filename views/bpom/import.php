<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Import Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Import BPOM Data</h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('bpom/processImport') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="mb-4">
                            <label for="import_file" class="form-label">Import File</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv" required>
                            <small class="text-muted">Upload a CSV file containing BPOM registration numbers</small>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading">Import Guidelines</h6>
                            <ul class="mb-0">
                                <li>File must be in CSV format</li>
                                <li>First column should contain BPOM registration numbers</li>
                                <li>First row (header) will be skipped</li>
                                <li>Each registration number will be verified with BPOM website</li>
                                <li>Process may take some time depending on the number of records</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Important Notes</h6>
                            <ul class="mb-0">
                                <li>The system will pause 2 seconds between each request to respect BPOM's server</li>
                                <li>Large files will be processed in batches</li>
                                <li>Do not close the browser during import</li>
                                <li>Failed entries will be logged for review</li>
                            </ul>
                        </div>

                        <div class="text-end">
                            <a href="<?= url('bpom') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="importButton">
                                <i class="fas fa-file-import"></i> Start Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sample Format -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sample CSV Format</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>registration_number</th>
                                    <th>notes (optional)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>NA18150112345</td>
                                    <td>Product A</td>
                                </tr>
                                <tr>
                                    <td>NA18150112346</td>
                                    <td>Product B</td>
                                </tr>
                                <tr>
                                    <td>NA18150112347</td>
                                    <td>Product C</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="downloadSample()">
                            <i class="fas fa-download"></i> Download Sample CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Import Tips -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Import Tips</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-csv text-primary fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6>Prepare Your File</h6>
                            <p class="text-muted mb-0">Ensure your CSV file is properly formatted with registration numbers in the first column.</p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6>Be Patient</h6>
                            <p class="text-muted mb-0">Large imports may take time due to rate limiting and verification process.</p>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6>Avoid Duplicates</h6>
                            <p class="text-muted mb-0">System will update existing records if registration numbers match.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-danger" onclick="cleanupOldRecords()">
                            <i class="fas fa-broom"></i> Cleanup Old Records
                        </button>
                        <a href="<?= url('bpom') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Search BPOM
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submission
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('importButton').disabled = true;
    document.getElementById('importButton').innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Importing...
    `;
});

// Download sample CSV
function downloadSample() {
    const csv = 'registration_number,notes\nNA18150112345,Product A\nNA18150112346,Product B\nNA18150112347,Product C';
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'bpom_import_sample.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

// Cleanup old records
async function cleanupOldRecords() {
    if (!confirm('Are you sure you want to clean up old inactive records? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('<?= url('bpom/cleanup') ?>');
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Failed to clean up records: ' + data.message);
        }
    } catch (error) {
        alert('Error cleaning up records. Please try again later.');
    }
}

// File size validation
document.getElementById('import_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (file && file.size > maxSize) {
        alert('File size exceeds 5MB limit. Please choose a smaller file.');
        e.target.value = '';
    }
});
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
