<?php
/**
 * Reports Controller
 * Handles report generation and management
 */
class ReportsController extends Controller {
    private $reportModel;
    private $analyticsModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->reportModel = $this->model('Report');
        $this->analyticsModel = $this->model('Analytics');
    }

    /**
     * Show reports dashboard
     */
    public function index() {
        $configurations = $this->reportModel->getAll(['status' => 'active']);
        $recentReports = $this->db->query("
            SELECT 
                rd.*,
                rc.name as report_name,
                u.name as user_name
            FROM report_downloads rd
            JOIN report_configurations rc ON rd.configuration_id = rc.id
            JOIN users u ON rd.user_id = u.id
            ORDER BY rd.downloaded_at DESC
            LIMIT 10
        ")->resultSet();

        $this->view->render('reports/index', [
            'title' => 'Reports - ' . APP_NAME,
            'configurations' => $configurations,
            'recentReports' => $recentReports,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show report configuration form
     */
    public function configure() {
        $this->requireAdmin();

        $this->view->render('reports/configure', [
            'title' => 'Configure Report - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Save report configuration
     */
    public function saveConfiguration() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('reports');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('reports/configure');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'type' => $this->getPost('type'),
            'description' => trim($this->getPost('description')),
            'parameters' => json_encode($this->getPost('parameters', [])),
            'schedule' => $this->getPost('schedule'),
            'recipients' => json_encode($this->getPost('recipients', [])),
            'created_by' => $this->getUserId()
        ];

        if ($this->reportModel->create($data)) {
            $this->logActivity('report', "Created report configuration: {$data['name']}");
            $this->setFlash('success', 'Report configuration saved successfully');
            $this->redirect('reports');
        } else {
            $this->setFlash('error', 'Failed to save report configuration');
            $this->redirect('reports/configure');
        }
    }

    /**
     * Generate report
     */
    public function generate($id = null) {
        if (!$id) {
            $this->redirect('reports');
            return;
        }

        $config = $this->reportModel->getById($id);
        if (!$config) {
            $this->setFlash('error', 'Report configuration not found');
            $this->redirect('reports');
            return;
        }

        $params = $this->getQuery('params', []);
        $format = $this->getQuery('format', 'html');

        // Generate report
        $data = $this->reportModel->generate($config, $params);
        if (!$data) {
            $this->setFlash('error', 'Failed to generate report');
            $this->redirect('reports');
            return;
        }

        // Track analytics
        $this->analyticsModel->trackEvent('report_generation', [
            'report_id' => $id,
            'report_type' => $config['type'],
            'format' => $format
        ]);

        // Handle different output formats
        switch ($format) {
            case 'json':
                header('Content-Type: application/json');
                echo json_encode($data);
                break;

            case 'csv':
                $this->downloadCsv($data, $config['name']);
                break;

            case 'pdf':
                $this->downloadPdf($data, $config['name']);
                break;

            default:
                $this->view->render('reports/view', [
                    'title' => $config['name'] . ' - ' . APP_NAME,
                    'config' => $config,
                    'data' => $data,
                    'params' => $params
                ]);
        }
    }

    /**
     * Schedule report generation
     */
    public function schedule($id = null) {
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('reports');
            return;
        }

        if ($this->reportModel->schedule($id)) {
            $this->logActivity('report', "Scheduled report #{$id}");
            $this->setFlash('success', 'Report scheduled successfully');
        } else {
            $this->setFlash('error', 'Failed to schedule report');
        }

        $this->redirect('reports');
    }

    /**
     * Show analytics dashboard
     */
    public function analytics() {
        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));

        // Get event analytics
        $events = $this->analyticsModel->getEventAnalytics('all', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // Get metric trends
        $metrics = [
            'sales' => $this->analyticsModel->getMetricTrends('total_sales'),
            'customers' => $this->analyticsModel->getMetricTrends('active_customers'),
            'products' => $this->analyticsModel->getMetricTrends('product_views')
        ];

        $this->view->render('reports/analytics', [
            'title' => 'Analytics - ' . APP_NAME,
            'events' => $events,
            'metrics' => $metrics,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Download report as CSV
     * @param array $data Report data
     * @param string $filename Base filename
     */
    private function downloadCsv($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys(reset($data)));
        }

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
    }

    /**
     * Download report as PDF
     * @param array $data Report data
     * @param string $filename Base filename
     */
    private function downloadPdf($data, $filename) {
        require_once 'helpers/pdf_generator.php';
        
        $pdf = new PdfGenerator();
        $pdf->setTitle($filename);
        $pdf->addData($data);
        $pdf->generate();
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        echo $pdf->output();
    }

    /**
     * Process scheduled reports
     * Called by cron job
     */
    public function processScheduled() {
        if (!$this->isCliRequest()) {
            die('This script can only be run from the command line');
        }

        $processed = $this->reportModel->processScheduled();
        echo "Processed {$processed} scheduled reports\n";
    }
}
