<?php
/**
 * PDF Generator Helper
 * Uses TCPDF to generate PDF reports
 */
class PDFGenerator {
    private $pdf;
    private $defaultFont = 'helvetica';

    public function __construct() {
        // Include TCPDF library
        require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

        // Initialize TCPDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor(APP_NAME);
        $this->pdf->SetTitle('Dashboard Report');

        // Set default header data
        $this->pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, APP_NAME, 'Dashboard Report');

        // Set header and footer fonts
        $this->pdf->setHeaderFont(Array($this->defaultFont, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array($this->defaultFont, '', PDF_FONT_SIZE_DATA));

        // Set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Set default font subsetting mode
        $this->pdf->setFontSubsetting(true);

        // Set font
        $this->pdf->SetFont($this->defaultFont, '', 10);
    }

    /**
     * Generate dashboard report
     */
    public function generateDashboardReport($data, $startDate, $endDate) {
        // Add a page
        $this->pdf->AddPage();

        // Report header
        $this->pdf->SetFont($this->defaultFont, 'B', 16);
        $this->pdf->Cell(0, 10, 'Dashboard Report', 0, 1, 'C');
        $this->pdf->SetFont($this->defaultFont, '', 10);
        $this->pdf->Cell(0, 10, "Period: {$startDate} to {$endDate}", 0, 1, 'C');
        $this->pdf->Ln(10);

        // Sales Statistics
        $this->addSection('Sales Statistics', [
            ['Total Sales', format_currency($data['salesStats']['total_sales'])],
            ['Total Orders', number_format($data['salesStats']['total_orders'])],
            ['Average Order', format_currency($data['salesStats']['average_order'])],
            ['Growth', $data['salesStats']['sales_growth'] . '%']
        ]);

        // Top Products
        $this->addSection('Top Selling Products', $data['topProducts'], [
            ['Product', 80],
            ['Quantity', 30],
            ['Amount', 40]
        ], function($item) {
            return [
                $item['name'],
                number_format($item['quantity']),
                format_currency($item['total_amount'])
            ];
        });

        // Payment Statistics
        $this->addSection('Payment Statistics', $data['paymentStats'], [
            ['Method', 50],
            ['Count', 30],
            ['Amount', 40],
            ['Percentage', 30]
        ], function($item) use ($data) {
            $total = array_sum(array_column($data['paymentStats'], 'amount'));
            $percentage = ($item['amount'] / $total) * 100;
            return [
                $item['method'],
                number_format($item['count']),
                format_currency($item['amount']),
                number_format($percentage, 1) . '%'
            ];
        });

        // Customer Statistics
        $this->addSection('Customer Statistics', [
            ['New Customers', number_format($data['customerStats']['new_customers'])],
            ['Total Customers', number_format($data['customerStats']['total_customers'])],
            ['Average Value', format_currency($data['customerStats']['average_value'])],
            ['Growth', $data['customerStats']['customer_growth'] . '%']
        ]);
    }

    /**
     * Add a section to the report
     */
    private function addSection($title, $data, $columns = null, $callback = null) {
        $this->pdf->SetFont($this->defaultFont, 'B', 12);
        $this->pdf->Cell(0, 10, $title, 0, 1);
        $this->pdf->SetFont($this->defaultFont, '', 10);

        if (is_array($columns)) {
            // Table header
            $this->pdf->SetFillColor(240, 240, 240);
            foreach ($columns as $col) {
                $this->pdf->Cell($col[1], 7, $col[0], 1, 0, 'L', true);
            }
            $this->pdf->Ln();

            // Table data
            $this->pdf->SetFillColor(255, 255, 255);
            foreach ($data as $row) {
                $values = $callback ? $callback($row) : $row;
                foreach ($values as $i => $value) {
                    $this->pdf->Cell($columns[$i][1], 6, $value, 1);
                }
                $this->pdf->Ln();
            }
        } else {
            // Key-value pairs
            foreach ($data as $row) {
                $this->pdf->Cell(60, 6, $row[0] . ':', 0);
                $this->pdf->Cell(0, 6, $row[1], 0);
                $this->pdf->Ln();
            }
        }

        $this->pdf->Ln(10);
    }

    /**
     * Output the PDF
     */
    public function output($filename = 'report.pdf', $destination = 'I') {
        $this->pdf->Output($filename, $destination);
    }

    /**
     * Add chart image to PDF
     */
    public function addChart($chartImage, $width = 180) {
        $this->pdf->Image($chartImage, null, null, $width);
        $this->pdf->Ln(10);
    }

    /**
     * Add page break
     */
    public function addPage() {
        $this->pdf->AddPage();
    }

    /**
     * Add text
     */
    public function addText($text, $fontSize = 10, $style = '') {
        $this->pdf->SetFont($this->defaultFont, $style, $fontSize);
        $this->pdf->Write(0, $text);
        $this->pdf->Ln();
    }

    /**
     * Add table
     */
    public function addTable($headers, $data) {
        // Calculate column widths
        $width = 190; // Total available width
        $colWidth = $width / count($headers);

        // Headers
        $this->pdf->SetFillColor(240, 240, 240);
        foreach ($headers as $header) {
            $this->pdf->Cell($colWidth, 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();

        // Data
        $this->pdf->SetFillColor(255, 255, 255);
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $this->pdf->Cell($colWidth, 6, $cell, 1, 0, 'L');
            }
            $this->pdf->Ln();
        }
        $this->pdf->Ln(5);
    }
}
