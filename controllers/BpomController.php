<?php
/**
 * BPOM Controller
 * Handles BPOM data scraping and API
 */
require_once 'helpers/bpom_scraper.php';

class BpomController extends Controller {
    private $bpomModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->bpomModel = $this->model('BpomReference');
    }

    /**
     * Search BPOM data by product name or registration number
     */
    public function search() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
            return;
        }

        $query = trim($this->getGet('query'));
        if (empty($query)) {
            $this->json(['error' => 'Query parameter is required']);
            return;
        }

        $results = bpom_search($query);
        if ($results === null) {
            $this->json(['error' => 'Failed to fetch data from BPOM']);
            return;
        }

        $this->json($results);
    }

    /**
     * Get BPOM product details by registration number
     */
    public function details($regNo = null) {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
            return;
        }

        if (!$regNo) {
            $this->json(['error' => 'Registration number is required']);
            return;
        }

        $details = bpom_get_details($regNo);
        if ($details === null) {
            $this->json(['error' => 'Failed to fetch product details']);
            return;
        }

        $this->json($details);
    }

    /**
     * Store BPOM data in database
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('dashboard');
            return;
        }

        $data = [
            'registration_number' => $this->getPost('registration_number'),
            'name' => $this->getPost('name'),
            'category' => $this->getPost('category'),
            'ingredients' => $this->getPost('ingredients'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (empty($data['registration_number']) || empty($data['name'])) {
            $this->setFlash('error', 'Registration number and name are required');
            $this->redirect('bpom/search');
            return;
        }

        if ($this->bpomModel->insert($data)) {
            $this->setFlash('success', 'BPOM data saved successfully');
        } else {
            $this->setFlash('error', 'Failed to save BPOM data');
        }

        $this->redirect('bpom/search');
    }
}
