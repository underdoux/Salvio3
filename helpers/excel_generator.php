<?php
/**
 * Excel Generator Helper
 * Uses PhpSpreadsheet to generate Excel reports
 */
class ExcelGenerator {
    private $spreadsheet;
    private $sheet;
    private $row = 1;

    public function __construct() {
        // Include PhpSpreadsheet library
        require_once 'vendor/autoload.php';

        // Initialize PhpSpreadsheet
        $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();

        // Set default styles
        $this->sheet->getDefaultColumnDimension()->setWidth(15);
        $this->sheet->getDefaultRowDimension()->setRowHeight(20);
    }

    /**
     * Generate dashboard report
     */
    public function generateDashboardReport($data, $startDate, $endDate) {
        // Set document properties
        $this->spreadsheet->getProperties()
            ->setCreator(APP_NAME)
            ->setLastModifiedBy(APP_NAME)
            ->setTitle('Dashboard Report')
            ->setSubject('Dashboard Report')
            ->setDescription('Dashboard report generated on ' . date('Y-m-d H:i:s'));

        // Report header
        $this->sheet->setCellValue('A1', 'Dashboard Report');
        $this->sheet->setCellValue('A2', "Period: {$startDate} to {$endDate}");
        $this->sheet->mergeCells('A1:E1');
        $this->sheet->mergeCells('A2:E2');
        
        $this->styleHeader('A1:E2');
        $this->row = 4;

        // Sales Statistics
        $this->addSection('Sales Statistics', [
            ['Total Sales', format_currency($data['salesStats']['total_sales'])],
            ['Total Orders', number_format($data['salesStats']['total_orders'])],
            ['Average Order', format_currency($data['salesStats']['average_order'])],
            ['Growth', $data['salesStats']['sales_growth'] . '%']
        ]);

        // Top Products
        $this->addSection('Top Selling Products', $data['topProducts'], [
            'Product',
            'Quantity',
            'Amount'
        ], function($item) {
            return [
                $item['name'],
                number_format($item['quantity']),
                format_currency($item['total_amount'])
            ];
        });

        // Payment Statistics
        $this->addSection('Payment Statistics', $data['paymentStats'], [
            'Method',
            'Count',
            'Amount',
            'Percentage'
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

        // Auto-size columns
        foreach (range('A', $this->sheet->getHighestColumn()) as $col) {
            $this->sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Add a section to the report
     */
    private function addSection($title, $data, $headers = null, $callback = null) {
        // Add section title
        $this->sheet->setCellValue("A{$this->row}", $title);
        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->styleSectionHeader("A{$this->row}:E{$this->row}");
        $this->row++;

        if (is_array($headers)) {
            // Add table headers
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue("{$col}{$this->row}", $header);
                $col++;
            }
            $this->styleTableHeader("A{$this->row}:{$col}{$this->row}");
            $this->row++;

            // Add table data
            foreach ($data as $rowData) {
                $values = $callback ? $callback($rowData) : $rowData;
                $col = 'A';
                foreach ($values as $value) {
                    $this->sheet->setCellValue("{$col}{$this->row}", $value);
                    $col++;
                }
                $this->row++;
            }
        } else {
            // Add key-value pairs
            foreach ($data as $pair) {
                $this->sheet->setCellValue("A{$this->row}", $pair[0]);
                $this->sheet->setCellValue("B{$this->row}", $pair[1]);
                $this->row++;
            }
        }

        $this->row += 2;
    }

    /**
     * Style header cells
     */
    private function styleHeader($range) {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    /**
     * Style section header cells
     */
    private function styleSectionHeader($range) {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ]
        ]);
    }

    /**
     * Style table header cells
     */
    private function styleTableHeader($range) {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEEEEE']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]);
    }

    /**
     * Add chart
     */
    public function addChart($title, $labels, $values, $type = 'column') {
        $dataSeriesLabels = array_map(function($label) {
            return new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Worksheet!$B$1', null, 1, [$label]);
        }, $labels);

        $dataSeriesValues = array_map(function($value) {
            return new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', 'Worksheet!$B$1', null, 1, [$value]);
        }, $values);

        $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
            $type,
            null,
            range(0, count($dataSeriesLabels) - 1),
            $dataSeriesLabels,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesValues
        );

        $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
        $legend = new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_RIGHT, null, false);
        $title = new \PhpOffice\PhpSpreadsheet\Chart\Title($title);

        $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
            'chart1',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition("A{$this->row}");
        $chart->setBottomRightPosition("E" . ($this->row + 15));

        $this->sheet->addChart($chart);
        $this->row += 16;
    }

    /**
     * Output the Excel file
     */
    public function output($filename = 'report.xlsx') {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $writer->setIncludeCharts(true);
        $writer->save('php://output');
        exit;
    }
}
