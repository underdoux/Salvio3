<?php
/**
 * PDF Generator Helper Class
 * Uses TCPDF to generate PDF documents
 */
class PdfGenerator {
    private $pdf;

    public function __construct() {
        require_once VENDOR_PATH . '/tcpdf/tcpdf.php';
        
        // Create new PDF document
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor(APP_NAME);
        $this->pdf->SetTitle('Invoice');
        
        // Set default header data
        $this->pdf->SetHeaderData('', 0, APP_NAME, '');
        
        // Set margins
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, 25);
        
        // Set default font
        $this->pdf->SetFont('helvetica', '', 10);
    }

    /**
     * Generate invoice PDF
     * @param array $sale Sale data with items
     */
    public function generateInvoice($sale) {
        // Add a page
        $this->pdf->AddPage();
        
        // Invoice header
        $this->pdf->SetFont('helvetica', 'B', 20);
        $this->pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, '#' . $sale['invoice_number'], 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Company and customer info
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(95, 5, 'From:', 0, 0);
        $this->pdf->Cell(95, 5, 'To:', 0, 1);
        
        $this->pdf->SetFont('helvetica', '', 10);
        // Company details
        $this->pdf->Cell(95, 5, APP_NAME, 0, 0);
        // Customer details
        $this->pdf->Cell(95, 5, $sale['customer_name'], 0, 1);
        
        $this->pdf->Cell(95, 5, 'Sales Person: ' . $sale['user_name'], 0, 0);
        if (!empty($sale['customer_email'])) {
            $this->pdf->Cell(95, 5, $sale['customer_email'], 0, 1);
        } else {
            $this->pdf->Ln();
        }
        
        $this->pdf->Cell(95, 5, 'Date: ' . formatDate($sale['created_at']), 0, 0);
        if (!empty($sale['customer_phone'])) {
            $this->pdf->Cell(95, 5, $sale['customer_phone'], 0, 1);
        } else {
            $this->pdf->Ln();
        }
        
        if (!empty($sale['customer_address'])) {
            $this->pdf->Cell(95, 5, '', 0, 0);
            $this->pdf->MultiCell(95, 5, $sale['customer_address'], 0, 'L');
        }
        
        $this->pdf->Ln(10);
        
        // Items table
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        // Table header
        $this->pdf->Cell(60, 7, 'Product', 1, 0, 'L', true);
        $this->pdf->Cell(25, 7, 'Quantity', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Unit Price', 1, 0, 'R', true);
        $this->pdf->Cell(35, 7, 'Discount', 1, 0, 'R', true);
        $this->pdf->Cell(35, 7, 'Total', 1, 1, 'R', true);
        
        // Table content
        $this->pdf->SetFont('helvetica', '', 10);
        foreach ($sale['items'] as $item) {
            $this->pdf->Cell(60, 6, $item['product_name'], 1);
            $this->pdf->Cell(25, 6, number_format($item['quantity']), 1, 0, 'C');
            $this->pdf->Cell(35, 6, formatCurrency($item['unit_price']), 1, 0, 'R');
            $this->pdf->Cell(35, 6, formatCurrency($item['discount_amount']), 1, 0, 'R');
            $this->pdf->Cell(35, 6, formatCurrency($item['total_amount']), 1, 1, 'R');
        }
        
        // Totals
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(155, 6, 'Subtotal:', 1, 0, 'R');
        $this->pdf->Cell(35, 6, formatCurrency($sale['total_amount']), 1, 1, 'R');
        
        if ($sale['discount_amount'] > 0) {
            $this->pdf->Cell(155, 6, 'Total Discount:', 1, 0, 'R');
            $this->pdf->Cell(35, 6, formatCurrency($sale['discount_amount']), 1, 1, 'R');
        }
        
        $this->pdf->Cell(155, 6, 'Final Total:', 1, 0, 'R');
        $this->pdf->Cell(35, 6, formatCurrency($sale['final_amount']), 1, 1, 'R');
        
        // Payment info
        $this->pdf->Ln(10);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 5, 'Payment Information:', 0, 1);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, 'Payment Method: ' . ucfirst(str_replace('_', ' ', $sale['payment_type'])), 0, 1);
        $this->pdf->Cell(0, 5, 'Payment Status: ' . ucfirst($sale['payment_status']), 0, 1);
        
        // Notes
        if (!empty($sale['notes'])) {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(0, 5, 'Notes:', 0, 1);
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->MultiCell(0, 5, $sale['notes'], 0, 'L');
        }
        
        // Terms and conditions
        $this->pdf->Ln(10);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 5, 'Terms & Conditions:', 0, 1);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->MultiCell(0, 4, "1. All prices are in " . APP_CURRENCY . "\n2. Payment is due upon receipt unless other terms are agreed upon\n3. Goods sold are not returnable unless defective\n4. Prices are subject to change without notice", 0, 'L');
        
        // Output PDF
        $filename = 'Invoice_' . $sale['invoice_number'] . '.pdf';
        $this->pdf->Output($filename, 'D');
    }
}
