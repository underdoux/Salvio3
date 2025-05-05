<?php
/**
 * BPOM Reference Model
 * Handles BPOM product reference data operations
 */
class BpomReference extends Model {
    protected $table = 'bpom_references';
    protected $fillable = [
        'nomor_registrasi',
        'nama_produk',
        'bentuk_sediaan',
        'nama_pendaftar',
        'komposisi',
        'kategori',
        'tanggal_terbit',
        'scrape_source'
    ];

    /**
     * Search BPOM products
     * @param string $keyword Search keyword
     * @param bool $refresh Whether to refresh data from BPOM website
     * @return array Search results
     */
    public function search($keyword, $refresh = false) {
        // First try to find in local database
        if (!$refresh) {
            $sql = "
                SELECT * FROM {$this->table}
                WHERE nomor_registrasi LIKE ? 
                OR nama_produk LIKE ?
                ORDER BY nama_produk ASC
            ";
            $searchTerm = "%{$keyword}%";
            $results = $this->db->query($sql)
                ->bind(1, $searchTerm)
                ->bind(2, $searchTerm)
                ->resultSet();

            if (!empty($results)) {
                return [
                    'source' => 'local',
                    'data' => $results
                ];
            }
        }

        // If not found locally or refresh requested, try BPOM website
        require_once HELPER_PATH . '/bpom_scraper.php';
        $scraper = new BpomScraper();
        $results = $scraper->search($keyword);

        if ($results) {
            // Save results to database
            foreach ($results as $product) {
                $this->saveProduct($product);
            }

            return [
                'source' => 'bpom',
                'data' => $results
            ];
        }

        return [
            'source' => 'none',
            'data' => []
        ];
    }

    /**
     * Get product by registration number
     * @param string $registrationNumber BPOM registration number
     * @param bool $refresh Whether to refresh data from BPOM website
     * @return array|null Product data
     */
    public function getByRegistrationNumber($registrationNumber, $refresh = false) {
        // First try to find in local database
        if (!$refresh) {
            $product = $this->db->query("
                SELECT * FROM {$this->table}
                WHERE nomor_registrasi = ?
            ")
            ->bind(1, $registrationNumber)
            ->single();

            if ($product) {
                return $product;
            }
        }

        // If not found locally or refresh requested, try BPOM website
        require_once HELPER_PATH . '/bpom_scraper.php';
        $scraper = new BpomScraper();
        $product = $scraper->getProductDetails($registrationNumber);

        if ($product) {
            return $this->saveProduct($product);
        }

        return null;
    }

    /**
     * Save or update product data
     * @param array $data Product data
     * @return array Saved product data
     */
    public function saveProduct($data) {
        // Check if product exists
        $existing = $this->db->query("
            SELECT id FROM {$this->table}
            WHERE nomor_registrasi = ?
        ")
        ->bind(1, $data['nomor_registrasi'])
        ->single();

        if ($existing) {
            // Update existing product
            $this->update($existing['id'], $data);
            return $this->getById($existing['id']);
        } else {
            // Create new product
            $id = $this->create($data);
            return $this->getById($id);
        }
    }

    /**
     * Import products from CSV file
     * @param string $file CSV file path
     * @return array Import results
     */
    public function importFromCsv($file) {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (($handle = fopen($file, "r")) !== false) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                $results['total']++;
                
                try {
                    $product = [
                        'nomor_registrasi' => $data[0],
                        'nama_produk' => $data[1],
                        'bentuk_sediaan' => $data[2] ?? null,
                        'nama_pendaftar' => $data[3] ?? null,
                        'komposisi' => $data[4] ?? null,
                        'kategori' => $data[5] ?? null,
                        'tanggal_terbit' => $data[6] ?? null,
                        'scrape_source' => 'csv_import'
                    ];

                    $this->saveProduct($product);
                    $results['success']++;

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$results['total']}: {$e->getMessage()}";
                }
            }
            fclose($handle);
        }

        return $results;
    }

    /**
     * Export products to CSV
     * @param string $file Output file path
     * @return bool Success status
     */
    public function exportToCsv($file) {
        try {
            $products = $this->db->query("
                SELECT 
                    nomor_registrasi,
                    nama_produk,
                    bentuk_sediaan,
                    nama_pendaftar,
                    komposisi,
                    kategori,
                    tanggal_terbit
                FROM {$this->table}
                ORDER BY nama_produk ASC
            ")->resultSet();

            if (($handle = fopen($file, "w")) !== false) {
                // Write header
                fputcsv($handle, [
                    'Nomor Registrasi',
                    'Nama Produk',
                    'Bentuk Sediaan',
                    'Nama Pendaftar',
                    'Komposisi',
                    'Kategori',
                    'Tanggal Terbit'
                ]);

                // Write data
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product['nomor_registrasi'],
                        $product['nama_produk'],
                        $product['bentuk_sediaan'],
                        $product['nama_pendaftar'],
                        $product['komposisi'],
                        $product['kategori'],
                        $product['tanggal_terbit']
                    ]);
                }

                fclose($handle);
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("BPOM Export Error: " . $e->getMessage());
            return false;
        }
    }
}
