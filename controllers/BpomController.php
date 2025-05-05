<?php
/**
 * BPOM Controller
 * Handles BPOM product reference operations
 */
class BpomController extends Controller {
    private $bpomModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->bpomModel = $this->model('BpomReference');
    }

    /**
     * Show BPOM search page
     */
    public function index() {
        $search = $this->getQuery('search', '');
        $refresh = $this->getQuery('refresh', false);
        $results = [];

        if (!empty($search)) {
            $results = $this->bpomModel->search($search, $refresh);
        }

        $this->view->render('bpom/index', [
            'title' => 'BPOM Products - ' . APP_NAME,
            'search' => $search,
            'results' => $results
        ]);
    }

    /**
     * Show import page
     */
    public function import() {
        $this->requireAdmin();
        
        $this->view->render('bpom/import', [
            'title' => 'Import BPOM Data - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Handle CSV import
     */
    public function importCsv() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('bpom/import');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('bpom/import');
            return;
        }

        // Handle file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Please select a valid CSV file');
            $this->redirect('bpom/import');
            return;
        }

        $file = $_FILES['csv_file'];
        $tempPath = $file['tmp_name'];

        // Validate file type
        $mimeType = mime_content_type($tempPath);
        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
            $this->setFlash('error', 'Invalid file type. Please upload a CSV file');
            $this->redirect('bpom/import');
            return;
        }

        // Process import
        $results = $this->bpomModel->importFromCsv($tempPath);

        if ($results['success'] > 0) {
            $message = "{$results['success']} products imported successfully.";
            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} products failed.";
            }
            $this->setFlash('success', $message);
            $this->logActivity('bpom', "Imported {$results['success']} BPOM products");
        } else {
            $this->setFlash('error', 'Failed to import products: ' . implode(', ', $results['errors']));
        }

        $this->redirect('bpom/import');
    }

    /**
     * Export BPOM data to CSV
     */
    public function export() {
        $this->requireAdmin();

        $filename = 'bpom_products_' . date('Y-m-d_His') . '.csv';
        $filepath = UPLOAD_PATH . '/temp/' . $filename;

        // Create temp directory if it doesn't exist
        if (!file_exists(UPLOAD_PATH . '/temp')) {
            mkdir(UPLOAD_PATH . '/temp', 0755, true);
        }

        if ($this->bpomModel->exportToCsv($filepath)) {
            // Send file to browser
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            readfile($filepath);
            unlink($filepath); // Delete temp file
            exit;
        }

        $this->setFlash('error', 'Failed to export BPOM data');
        $this->redirect('bpom');
    }

    /**
     * Search BPOM products via AJAX
     */
    public function search() {
        if (!$this->isAjax()) {
            $this->redirect('bpom');
            return;
        }

        $keyword = $this->getQuery('keyword', '');
        $refresh = $this->getQuery('refresh', false);

        if (empty($keyword)) {
            $this->jsonResponse(['success' => false, 'message' => 'Search keyword is required']);
            return;
        }

        $results = $this->bpomModel->search($keyword, $refresh);
        
        $this->jsonResponse([
            'success' => true,
            'source' => $results['source'],
            'data' => $results['data']
        ]);
    }

    /**
     * Get product details via AJAX
     */
    public function getProduct() {
        if (!$this->isAjax()) {
            $this->redirect('bpom');
            return;
        }

        $registrationNumber = $this->getQuery('registration_number', '');
        $refresh = $this->getQuery('refresh', false);

        if (empty($registrationNumber)) {
            $this->jsonResponse(['success' => false, 'message' => 'Registration number is required']);
            return;
        }

        $product = $this->bpomModel->getByRegistrationNumber($registrationNumber, $refresh);
        
        if ($product) {
            $this->jsonResponse([
                'success' => true,
                'data' => $product
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }
    }
}
