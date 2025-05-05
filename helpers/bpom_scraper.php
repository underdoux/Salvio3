<?php
/**
 * BPOM Scraper Helper
 * Handles scraping product data from BPOM website
 */
class BpomScraper {
    private $baseUrl = 'https://cekbpom.pom.go.id/';
    private $searchUrl = 'https://cekbpom.pom.go.id/index.php/home/produk/';
    private $curlHandle;
    private $lastError;

    public function __construct() {
        $this->curlHandle = curl_init();
        curl_setopt_array($this->curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT => 30
        ]);
    }

    /**
     * Search BPOM products
     * @param string $keyword Search keyword (product name or registration number)
     * @return array|false Array of products or false on failure
     */
    public function search($keyword) {
        try {
            // Get CSRF token first
            curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl);
            $response = curl_exec($this->curlHandle);
            if ($response === false) {
                throw new Exception('Failed to access BPOM website');
            }

            // Extract CSRF token
            $dom = new DOMDocument();
            @$dom->loadHTML($response);
            $xpath = new DOMXPath($dom);
            $csrfToken = $xpath->evaluate('string(//input[@name="csrf_token"]/@value)');
            if (!$csrfToken) {
                throw new Exception('Failed to get CSRF token');
            }

            // Prepare search data
            $postData = http_build_query([
                'csrf_token' => $csrfToken,
                'keyword' => $keyword,
                'category' => 'all'
            ]);

            // Perform search
            curl_setopt_array($this->curlHandle, [
                CURLOPT_URL => $this->searchUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Requested-With: XMLHttpRequest'
                ]
            ]);

            $response = curl_exec($this->curlHandle);
            if ($response === false) {
                throw new Exception('Search request failed');
            }

            // Parse results
            $results = json_decode($response, true);
            if (!$results || !isset($results['data'])) {
                throw new Exception('Invalid response format');
            }

            // Process and format results
            $products = [];
            foreach ($results['data'] as $item) {
                $products[] = [
                    'nomor_registrasi' => $item['nomor_registrasi'],
                    'nama_produk' => $item['nama_produk'],
                    'bentuk_sediaan' => $item['bentuk_sediaan'] ?? null,
                    'nama_pendaftar' => $item['nama_pendaftar'],
                    'komposisi' => $item['komposisi'] ?? null,
                    'kategori' => $item['kategori'] ?? null,
                    'tanggal_terbit' => $item['tanggal_terbit'] ?? null,
                    'scrape_source' => 'bpom_search'
                ];
            }

            return $products;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("BPOM Scraping Error: " . $this->lastError);
            return false;
        }
    }

    /**
     * Get product details by registration number
     * @param string $registrationNumber BPOM registration number
     * @return array|false Product details or false on failure
     */
    public function getProductDetails($registrationNumber) {
        try {
            $url = $this->baseUrl . 'produk/detail/' . urlencode($registrationNumber);
            curl_setopt($this->curlHandle, CURLOPT_URL, $url);
            
            $response = curl_exec($this->curlHandle);
            if ($response === false) {
                throw new Exception('Failed to get product details');
            }

            // Parse product details
            $dom = new DOMDocument();
            @$dom->loadHTML($response);
            $xpath = new DOMXPath($dom);

            // Extract product information
            $product = [
                'nomor_registrasi' => $registrationNumber,
                'nama_produk' => $this->extractText($xpath, '//td[contains(text(), "Nama Produk")]/following-sibling::td[1]'),
                'bentuk_sediaan' => $this->extractText($xpath, '//td[contains(text(), "Bentuk Sediaan")]/following-sibling::td[1]'),
                'nama_pendaftar' => $this->extractText($xpath, '//td[contains(text(), "Pendaftar")]/following-sibling::td[1]'),
                'komposisi' => $this->extractText($xpath, '//td[contains(text(), "Komposisi")]/following-sibling::td[1]'),
                'kategori' => $this->extractText($xpath, '//td[contains(text(), "Kategori")]/following-sibling::td[1]'),
                'tanggal_terbit' => $this->extractText($xpath, '//td[contains(text(), "Tanggal Terbit")]/following-sibling::td[1]'),
                'scrape_source' => 'bpom_detail'
            ];

            return array_filter($product);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("BPOM Scraping Error: " . $this->lastError);
            return false;
        }
    }

    /**
     * Extract text content from XPath query
     */
    private function extractText($xpath, $query) {
        $nodes = $xpath->query($query);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : null;
    }

    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }

    public function __destruct() {
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
        }
    }
}
