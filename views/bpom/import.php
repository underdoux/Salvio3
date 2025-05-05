<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Import BPOM Data</h1>
        <a href="<?= url('bpom') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to BPOM Products
        </a>
    </div>

    <!-- Import Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('bpom/importCsv') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">Select CSV File</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="csv_file" 
                                   name="csv_file" 
                                   accept=".csv" 
                                   required>
                            <div class="form-text">
                                File must be in CSV format with the following columns:
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Column</th>
                                        <th>Description</th>
                                        <th>Required</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Nomor Registrasi</td>
                                        <td>BPOM registration number</td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                    </tr>
                                    <tr>
                                        <td>Nama Produk</td>
                                        <td>Product name</td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                    </tr>
                                    <tr>
                                        <td>Bentuk Sediaan</td>
                                        <td>Product form/type</td>
                                        <td><span class="badge bg-secondary">No</span></td>
                                    </tr>
                                    <tr>
                                        <td>Nama Pendaftar</td>
                                        <td>Manufacturer/registrant name</td>
                                        <td><span class="badge bg-secondary">No</span></td>
                                    </tr>
                                    <tr>
                                        <td>Komposisi</td>
                                        <td>Product composition</td>
                                        <td><span class="badge bg-secondary">No</span></td>
                                    </tr>
                                    <tr>
                                        <td>Kategori</td>
                                        <td>Product category</td>
                                        <td><span class="badge bg-secondary">No</span></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Terbit</td>
                                        <td>Issue date (YYYY-MM-DD)</td>
                                        <td><span class="badge bg-secondary">No</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Important Notes:</h6>
                            <ul class="mb-0">
                                <li>First row should contain column headers</li>
                                <li>Use comma (,) as field separator</li>
                                <li>Text fields should be enclosed in double quotes (")</li>
                                <li>Date format should be YYYY-MM-DD</li>
                                <li>Existing products will be updated if registration number matches</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-import"></i> Import Data
                            </button>
                            <a href="<?= url('bpom/export') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Download Current Data as Template
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Sample CSV -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sample CSV Format</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code>Nomor Registrasi,Nama Produk,Bentuk Sediaan,Nama Pendaftar,Komposisi,Kategori,Tanggal Terbit
"NA18150123456","Sample Product","Tablet","PT Example","Paracetamol 500mg","Obat Bebas","2023-01-01"
"NA18150123457","Another Product","Sirup","PT Example","Ibuprofen 200mg","Obat Bebas Terbatas","2023-01-02"</code></pre>
                </div>
            </div>

            <!-- Tips -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tips</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-file-excel text-success"></i> Using Excel?</h6>
                        <ol class="mb-0">
                            <li>Enter data in Excel</li>
                            <li>Go to File > Save As</li>
                            <li>Choose "CSV (Comma delimited)"</li>
                            <li>Click Save</li>
                        </ol>
                    </div>
                    <div>
                        <h6><i class="fas fa-check-circle text-primary"></i> Best Practices</h6>
                        <ul class="mb-0">
                            <li>Verify data before import</li>
                            <li>Backup existing data</li>
                            <li>Test with small batch first</li>
                            <li>Check results after import</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
