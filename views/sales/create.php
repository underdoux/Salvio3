<?php require_once VIEW_PATH . '/layout/header.php'; ?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">New Sale</h1>
        <a href="<?= url('sales') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Sales
        </a>
    </div>

    <!-- Flash Messages -->
    <div class="flash-message"></div>

    <form id="saleForm" action="<?= url('sales/store') ?>" method="POST" onsubmit="return validateSaleForm(event)">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="items" id="saleItems">

        <div class="row g-4">
            <?php require_once VIEW_PATH . '/sales/_form_content.php'; ?>
        </div>
    </form>
</div>

<script>
// Define global variables for sales.js
const APP_CURRENCY = '<?= APP_CURRENCY ?>';
const APP_URL = '<?= APP_URL ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/sales.js"></script>

<?php require_once VIEW_PATH . '/layout/footer.php'; ?>
