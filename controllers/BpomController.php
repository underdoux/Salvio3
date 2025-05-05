<?php
/**
 * BPOM Controller
 * Handles BPOM data scraping and management
 */
class BpomController extends Controller {
    private $bpomModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->bpomModel = $this->model('BpomReference');
        $this->productModel = $this->model('Product');
    }

    /**
     * BPOM search interface
     */
    public function index() {
        // Get statistics
        $stats = $this->bpomModel->getStats();
        
        // Get expiring registrations
        $expiringRegistrations = $this->bpomModel->getExpiringRegistrations();

        $this->view->render('bpom/index', [
            'title' => 'BPOM Search - ' . APP_NAME,
            'stats' => $stats,
            'expiringRegistrations' => $expiringRegistrations
        ]);
    }

    /**
     * Search BPOM database
     */
    public function search() {
        $id = $this->getQuery('id');
        
        if (empty($id)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Registration number is required'
            ]);
            return;
        }

        try {
            // First check local database
            $result = $this->bpomModel->getByRegistrationNumber($id);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'source' => 'local',
                    'data' => $result
                ]);
                return;
            }

            // If not found locally, scrape from BPOM website
            $data = $this->scrapeBpomData($id);
            
            if ($data) {
                // Save to database
                $this->bpomModel->updateOrCreate($data);
                
                $this->jsonResponse([
                    'success' => true,
                    'source' => 'scraped',
                    'data' => $data
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Product not found in BPOM database'
                ]);
            }
        } catch (Exception $e) {
            $this->logError('BPOM scraping error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error searching BPOM database'
            ]);
        }
    }

    /**
     * Bulk import interface
     */
    public function import() {
        $this->requireAdmin();
        
        $this->view->render('bpom/import', [
            'title' => 'Import BPOM Data - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process bulk import
     */
    public function processImport() {
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
        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Please select a valid file');
            $this->redirect('bpom/import');
            return;
        }

        try {
            $imported = 0;
            $failed = 0;
            
            // Read CSV file
            $handle = fopen($file['tmp_name'], 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $registrationNumber = trim($row[0] ?? '');
                if (empty($registrationNumber)) continue;

                try {
                    $data = $this->scrapeBpomData($registrationNumber);
                    if ($data && $this->bpomModel->updateOrCreate($data)) {
                        $imported++;
                    } else {
                        $failed++;
                    }
                    
                    // Respect rate limiting
                    sleep(2);
                } catch (Exception $e) {
                    $failed++;
                    $this->logError("Error importing BPOM data for {$registrationNumber}: " . $e->getMessage());
                }
            }
            
            fclose($handle);

            $this->setFlash('success', "Import completed. Imported: {$imported}, Failed: {$failed}");
        } catch (Exception $e) {
            $this->setFlash('error', 'Error processing import: ' . $e->getMessage());
        }

        $this->redirect('bpom/import');
    }

    /**
     * Scrape data from BPOM website
     * @param string $registrationNumber BPOM registration number
     * @return array|null Scraped data or null if not found
     */
    private function scrapeBpomData($registrationNumber) {
        $url = BPOM_SEARCH_URL;
        $ch = curl_init();

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'search' => $registrationNumber
            ]),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Failed to access BPOM website");
        }

        // Create DOM document
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);

        // Extract data
        $data = [
            'registration_number' => $registrationNumber,
            'product_name' => $this->extractText($xpath, '//td[contains(text(), "Nama Produk")]/following-sibling::td[1]'),
            'manufacturer' => $this->extractText($xpath, '//td[contains(text(), "Pendaftar")]/following-sibling::td[1]'),
            'category' => $this->extractText($xpath, '//td[contains(text(), "Kategori")]/following-sibling::td[1]'),
            'issued_date' => $this->parseDate($this->extractText($xpath, '//td[contains(text(), "Tanggal Terbit")]/following-sibling::td[1]')),
            'expired_date' => $this->parseDate($this->extractText($xpath, '//td[contains(text(), "Masa Berlaku")]/following-sibling::td[1]')),
            'status' => 'active'
        ];

        // Validate required fields
        if (empty($data['product_name'])) {
            return null;
        }

        return $data;
    }

    /**
     * Extract text from XPath query
     */
    private function extractText($xpath, $query) {
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return '';
    }

    /**
     * Parse date from Indonesian format
     */
    private function parseDate($dateString) {
        if (empty($dateString)) return null;

        $months = [
            'Januari' => '01',
            'Februari' => '02',
            'Maret' => '03',
            'April' => '04',
            'Mei' => '05',
            'Juni' => '06',
            'Juli' => '07',
            'Agustus' => '08',
            'September' => '09',
            'Oktober' => '10',
            'November' => '11',
            'Desember' => '12'
        ];

        foreach ($months as $indo => $num) {
            $dateString = str_replace($indo, $num, $dateString);
        }

        $date = DateTime::createFromFormat('d m Y', $dateString);
        return $date ? $date->format('Y-m-d') : null;
    }

    /**
     * Clean up old records
     */
    public function cleanup() {
        $this->requireAdmin();

        $days = (int)($this->getQuery('days', 365));
        $deleted = $this->bpomModel->cleanupOldRecords($days);

        $this->jsonResponse([
            'success' => true,
            'message' => "{$deleted} old records deleted",
            'deleted' => $deleted
        ]);
    }
}
