<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<h1>Product Management</h1>

<form method="get" action="<?= url('product') ?>" class="search-form">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
    <button type="submit">Search</button>
    <a href="<?= url('product/create') ?>" class="btn btn-primary">Add New Product</a>
</form>

<?php if (!empty($products)): ?>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>SKU</th>
            <th>Barcode</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Min Stock</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= htmlspecialchars($product['id']) ?></td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['sku']) ?></td>
            <td><?= htmlspecialchars($product['barcode']) ?></td>
            <td>
                <?php
                $categoryName = '';
                foreach ($categories as $category) {
                    if ($category['id'] == $product['category_id']) {
                        $categoryName = $category['name'];
                        break;
                    }
                }
                echo htmlspecialchars($categoryName);
                ?>
            </td>
            <td><?= htmlspecialchars($product['stock']) ?></td>
            <td><?= htmlspecialchars($product['min_stock']) ?></td>
            <td><?= htmlspecialchars($product['status']) ?></td>
            <td>
                <a href="<?= url('product/edit/' . $product['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="<?= url('product/delete/' . $product['id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$totalPages = ceil($totalProducts / $perPage);
if ($totalPages > 1):
?>
<nav>
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('product?page=' . $p . '&search=' . urlencode($search)) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<p>No products found.</p>
<?php endif; ?>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
