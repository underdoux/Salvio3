<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Category Management</h1>

<form method="get" action="<?= url('category') ?>" class="search-form">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search categories...">
    <button type="submit">Search</button>
    <a href="<?= url('category/create') ?>" class="btn btn-primary">Add New Category</a>
</form>

<?php if (!empty($categories)): ?>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $category): ?>
        <tr>
            <td><?= htmlspecialchars($category['id']) ?></td>
            <td><?= htmlspecialchars($category['name']) ?></td>
            <td><?= htmlspecialchars($category['description']) ?></td>
            <td><?= htmlspecialchars($category['status']) ?></td>
            <td>
                <a href="<?= url('category/edit/' . $category['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="<?= url('category/delete/' . $category['id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$totalPages = ceil($totalCategories / $perPage);
if ($totalPages > 1):
?>
<nav>
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('category?page=' . $p . '&search=' . urlencode($search)) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<p>No categories found.</p>
<?php endif; ?>

<?php $this->view('layout/footer'); ?>
