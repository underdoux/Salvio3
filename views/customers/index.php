<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>Customer Management</h1>

<form method="get" action="<?= url('customer') ?>" class="search-form">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search customers...">
    <button type="submit">Search</button>
    <a href="<?= url('customer/create') ?>" class="btn btn-primary">Add New Customer</a>
</form>

<?php if (!empty($customers)): ?>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= htmlspecialchars($customer['id']) ?></td>
            <td><?= htmlspecialchars($customer['name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td><?= htmlspecialchars($customer['phone']) ?></td>
            <td><?= htmlspecialchars($customer['address']) ?></td>
            <td><?= htmlspecialchars($customer['status']) ?></td>
            <td>
                <a href="<?= url('customer/edit/' . $customer['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="<?= url('customer/delete/' . $customer['id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$totalPages = ceil($totalCustomers / $perPage);
if ($totalPages > 1):
?>
<nav>
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('customer?page=' . $p . '&search=' . urlencode($search)) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<p>No customers found.</p>
<?php endif; ?>

<?php $this->view('layout/footer'); ?>
