<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>User Management</h1>

<form method="get" action="<?= url('user') ?>" class="search-form">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users...">
    <button type="submit">Search</button>
    <a href="<?= url('user/create') ?>" class="btn btn-primary">Add New User</a>
</form>

<?php if (!empty($users)): ?>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td><?= htmlspecialchars($user['status']) ?></td>
            <td>
                <a href="<?= url('user/edit/' . $user['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="<?= url('user/delete/' . $user['id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$totalPages = ceil($totalUsers / $perPage);
if ($totalPages > 1):
?>
<nav>
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('user?page=' . $p . '&search=' . urlencode($search)) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<p>No users found.</p>
<?php endif; ?>

<?php $this->view('layout/footer'); ?>
