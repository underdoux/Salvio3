<?php require VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= APP_URL ?>/categories" class="text-decoration-none">Categories</a>
            </li>
            <?php foreach ($breadcrumb as $item): ?>
                <li class="breadcrumb-item <?= $item['id'] === $category['id'] ? 'active' : '' ?>">
                    <?php if ($item['id'] !== $category['id']): ?>
                        <a href="<?= APP_URL ?>/categories/view/<?= $item['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($item['name']) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($item['name']) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2"><?= htmlspecialchars($category['name']) ?></h1>
            <?php if ($category['description']): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <a href="<?= APP_URL ?>/categories/edit/<?= $category['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit Category
            </a>
            <a href="<?= APP_URL ?>/products/create?category=<?= $category['id'] ?>" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Add Product
            </a>
        </div>
    </div>

    <!-- Products Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Products</h5>
                <span class="badge bg-primary"><?= $pagination['total'] ?> products</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <img src="<?= APP_URL ?>/assets/img/empty-box.svg" 
                         alt="No products" 
                         class="mb-4" 
                         style="width: 120px;">
                    <h5>No Products Found</h5>
                    <p class="text-muted mb-4">This category doesn't have any products yet.</p>
                    <a href="<?= APP_URL ?>/products/create?category=<?= $category['id'] ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px">Image</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?= APP_URL ?>/uploads/products/<?= $product['image'] ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                                 class="img-thumbnail"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/products/view/<?= $product['id'] ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($product['sku']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] <= $product['min_stock']): ?>
                                            <span class="text-danger">
                                                <?= $product['stock'] ?> units
                                            </span>
                                        <?php else: ?>
                                            <?= $product['stock'] ?> units
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= number_format($product['selling_price'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= APP_URL ?>/products/edit/<?= $product['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/products/view/<?= $product['id'] ?>" 
                                               class="btn btn-sm btn-outline-info"
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?= APP_URL ?>/categories/view/<?= $category['id'] ?>?page=<?= $pagination['page'] - 1 ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                                    <a class="page-link" 
                                       href="<?= APP_URL ?>/categories/view/<?= $category['id'] ?>?page=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?= APP_URL ?>/categories/view/<?= $category['id'] ?>?page=<?= $pagination['page'] + 1 ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
