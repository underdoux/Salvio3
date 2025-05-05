<?php
/**
 * Investors Controller
 * Handles investor management and profit distributions
 */
class InvestorsController extends Controller {
    private $investorModel;
    private $profitCalculationModel;
    private $costModel;

    public function __construct() {
        parent::__construct();
        $this->requireAdmin();
        $this->investorModel = $this->model('Investor');
        $this->profitCalculationModel = $this->model('ProfitCalculation');
        $this->costModel = $this->model('Cost');
    }

    /**
     * Show investors list
     */
    public function index() {
        $investors = $this->investorModel->getAll();
        $totalCapital = array_sum(array_column($investors, 'current_capital'));
        
        foreach ($investors as &$investor) {
            $investor['capital_percentage'] = $totalCapital > 0 
                ? ($investor['current_capital'] / $totalCapital) * 100 
                : 0;
        }

        $this->view->render('investors/index', [
            'title' => 'Investors - ' . APP_NAME,
            'investors' => $investors,
            'totalCapital' => $totalCapital,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show investor creation form
     */
    public function create() {
        $this->view->render('investors/create', [
            'title' => 'Add Investor - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process investor creation
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('investors');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('investors/create');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'initial_capital' => floatval($this->getPost('initial_capital')),
            'ownership_percentage' => floatval($this->getPost('ownership_percentage')),
            'join_date' => $this->getPost('join_date'),
            'bank_name' => trim($this->getPost('bank_name')),
            'bank_account' => trim($this->getPost('bank_account')),
            'bank_holder' => trim($this->getPost('bank_holder')),
            'notes' => trim($this->getPost('notes')),
            'reference_number' => trim($this->getPost('reference_number'))
        ];

        // Validate ownership percentage
        if (!$this->investorModel->validateOwnership($data['ownership_percentage'])) {
            $this->setFlash('error', 'Total ownership percentage cannot exceed 100%');
            $this->redirect('investors/create');
            return;
        }

        if ($this->investorModel->createWithCapital($data)) {
            $this->logActivity('investor', "Added new investor: {$data['name']}");
            $this->setFlash('success', 'Investor added successfully');
            $this->redirect('investors');
        } else {
            $this->setFlash('error', 'Failed to add investor');
            $this->redirect('investors/create');
        }
    }

    /**
     * Show investor details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('investors');
            return;
        }

        $investor = $this->investorModel->getById($id);
        if (!$investor) {
            $this->setFlash('error', 'Investor not found');
            $this->redirect('investors');
            return;
        }

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));

        $transactions = $this->investorModel->getTransactions($id, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $summary = $this->investorModel->getSummary($id, $startDate, $endDate);

        $this->view->render('investors/view', [
            'title' => $investor['name'] . ' - ' . APP_NAME,
            'investor' => $investor,
            'transactions' => $transactions,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Record capital transaction
     */
    public function recordTransaction() {
        if (!$this->isPost()) {
            $this->redirect('investors');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('investors');
            return;
        }

        $data = [
            'investor_id' => $this->getPost('investor_id'),
            'type' => $this->getPost('type'),
            'amount' => floatval($this->getPost('amount')),
            'transaction_date' => $this->getPost('transaction_date'),
            'reference_number' => trim($this->getPost('reference_number')),
            'notes' => trim($this->getPost('notes'))
        ];

        if ($this->investorModel->recordTransaction($data)) {
            $this->logActivity('investor', "Recorded {$data['type']} transaction for investor #{$data['investor_id']}");
            $this->setFlash('success', 'Transaction recorded successfully');
        } else {
            $this->setFlash('error', 'Failed to record transaction');
        }

        $this->redirect('investors/view/' . $data['investor_id']);
    }

    /**
     * Show profit calculations
     */
    public function profits() {
        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));

        $calculations = $this->profitCalculationModel->getAll([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $summary = $this->profitCalculationModel->getPeriodSummary($startDate, $endDate);
        $investors = $this->investorModel->getAll(['status' => 'active']);

        $this->view->render('investors/profits', [
            'title' => 'Profit Calculations - ' . APP_NAME,
            'calculations' => $calculations,
            'summary' => $summary,
            'investors' => $investors,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Calculate period profits
     */
    public function calculateProfits() {
        if (!$this->isPost()) {
            $this->redirect('investors/profits');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('investors/profits');
            return;
        }

        $startDate = $this->getPost('start_date');
        $endDate = $this->getPost('end_date');

        $calculation = $this->profitCalculationModel->calculatePeriod($startDate, $endDate);
        if (!$calculation) {
            $this->setFlash('error', 'Failed to calculate profits');
            $this->redirect('investors/profits');
            return;
        }

        $calculation['calculated_by'] = $this->getUserId();
        $calculation['notes'] = trim($this->getPost('notes'));

        if ($this->profitCalculationModel->createWithDistributions($calculation)) {
            $this->logActivity('profit', "Calculated profits for period {$startDate} to {$endDate}");
            $this->setFlash('success', 'Profit calculation completed successfully');
        } else {
            $this->setFlash('error', 'Failed to save profit calculation');
        }

        $this->redirect('investors/profits');
    }

    /**
     * Process profit distributions
     */
    public function processDistributions() {
        if (!$this->isPost()) {
            $this->redirect('investors/profits');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('investors/profits');
            return;
        }

        $calculationId = $this->getPost('calculation_id');
        $distributions = json_decode($this->getPost('distributions'), true);

        if ($this->profitCalculationModel->processDistributions($calculationId, $distributions)) {
            $this->logActivity('profit', "Processed profit distributions for calculation #{$calculationId}");
            $this->setFlash('success', 'Profit distributions processed successfully');
        } else {
            $this->setFlash('error', 'Failed to process profit distributions');
        }

        $this->redirect('investors/profits');
    }
}
