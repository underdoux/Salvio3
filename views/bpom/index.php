<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">BPOM Products</h1>
        <?php if ($this->isAdmin()): ?>
            <div class="btn-group">
                <a href="<?= url('bpom/import') ?>" class="btn btn-primary">
                    <i class="fas fa-file-import"></i> Import Data
                </a>
                <a href="<?= url('bpom/export') ?>" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export Data
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= url('bpom') ?>" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by product name or registration number..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <button type="submit" 
                                    name="refresh" 
                                    value="1" 
                                    class="btn btn-outline-secondary" 
                                    title="Refresh from BPOM website">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <?php if (!empty($search)): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($results['data'])): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="h5 text-muted">No products found</p>
                        <p class="text-muted">Try searching with a different keyword</p>
                    </div>
                <?php else: ?>
                    <?php if ($results['source'] === 'local'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Results from local database. 
                            <button type="submit" 
                                    form="searchForm" 
                                    name="refresh" 
                                    value="1" 
                                    class="btn btn-link">
                                Refresh from BPOM website
                            </button>
                        </div>
                    <?php elseif ($results['source'] === 'bpom'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            Results fetched from BPOM website
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Registration Number</th>
                                    <th>Product Name</th>
                                    <th>Form</th>
                                    <th>Manufacturer</th>
                                    <th>Category</th>
                                    <th>Issue Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['data'] as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($product['nomor_registrasi']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($product['nama_produk']) ?></td>
                                        <td><?= htmlspecialchars($product['bentuk_sediaan'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($product['nama_pendaftar']) ?></td>
                                        <td><?= htmlspecialchars($product['kategori'] ?? '-') ?></td>
                                        <td><?= $product['tanggal_terbit'] ? formatDate($product['tanggal_terbit']) : '-' ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    onclick="showDetails('<?= $product['nomor_registrasi'] ?>')"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Search Instructions -->
        <div class="text-center py-5">
            <i class="fas fa-database fa-3x text-muted mb-3"></i>
            <p class="h5 text-muted">Search BPOM Product Database</p>
            <p class="text-muted">Enter product name or registration number to search</p>
        </div>
    <?php endif; ?>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="productLoading">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading product details...</p>
                </div>
                <div id="productDetails" style="display: none;">
                    <table class="table">
                        <tr>
                            <th width="30%">Registration Number</th>
                            <td id="detail_nomor_registrasi"></td>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td id="detail_nama_produk"></td>
                        </tr>
                        <tr>
                            <th>Form</th>
                            <td id="detail_bentuk_sediaan"></td>
                        </tr>
                        <tr>
                            <th>Manufacturer</th>
                            <td id="detail_nama_pendaftar"></td>
                        </tr>
                        <tr>
                            <th>Composition</th>
                            <td id="detail_komposisi"></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td id="detail_kategori"></td>
                        </tr>
                        <tr>
                            <th>Issue Date</th>
                            <td id="detail_tanggal_terbit"></td>
                        </tr>
                    </table>
                </div>
                <div class="alert alert-danger" id="productError" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="refreshDetails()">
                    <i class="fas fa-sync-alt"></i> Refresh from BPOM
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRegistrationNumber = '';
const productModal = new bootstrap.Modal(document.getElementById('productModal'));

function showDetails(registrationNumber) {
    currentRegistrationNumber = registrationNumber;
    
    // Reset modal state
    document.getElementById('productLoading').style.display = 'block';
    document.getElementById('productDetails').style.display = 'none';
    document.getElementById('productError').style.display = 'none';
    
    // Show modal
    productModal.show();
    
    // Load details
    loadProductDetails(false);
}

function refreshDetails() {
    if (!currentRegistrationNumber) return;
    loadProductDetails(true);
}

function loadProductDetails(refresh = false) {
    document.getElementById('productLoading').style.display = 'block';
    document.getElementById('productDetails').style.display = 'none';
    document.getElementById('productError').style.display = 'none';

    fetch(`${APP_URL}/bpom/getProduct?registration_number=${currentRegistrationNumber}&refresh=${refresh ? 1 : 0}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProductDetails(data.data);
            } else {
                throw new Error(data.message || 'Failed to load product details');
            }
        })
        .catch(error => {
            document.getElementById('productLoading').style.display = 'none';
            document.getElementById('productError').style.display = 'block';
            document.getElementById('productError').textContent = error.message;
        });
}

function displayProductDetails(product) {
    // Update each field
    Object.keys(product).forEach(key => {
        const element = document.getElementById(`detail_${key}`);
        if (element) {
            element.textContent = product[key] || '-';
        }
    });

    // Show details
    document.getElementById('productLoading').style.display = 'none';
    document.getElementById('productDetails').style.display = 'block';
}
</script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
